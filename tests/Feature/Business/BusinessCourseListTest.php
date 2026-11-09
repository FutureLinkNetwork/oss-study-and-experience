<?php

namespace Tests\Feature\Business;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCourseListTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_index_lists_courses_by_id_with_formatted_updated_at(): void
    {
        [$businessUser, $business, $classroom] = $this->createBusinessContext();

        $olderUpdatedAt = Carbon::parse('2024-03-01 08:05:30', 'Asia/Tokyo');
        $newerUpdatedAt = Carbon::parse('2026-09-24 13:08:45', 'Asia/Tokyo');

        $olderCourse = $this->createCourse($business, $classroom, '古い更新コース');
        $newerCourse = $this->createCourse($business, $classroom, '新しい更新コース');
        $missingUpdatedAtCourse = $this->createCourse($business, $classroom, '日時なしコース');

        CourseInfo::query()->whereKey($olderCourse->id)->update(['updated_at' => $olderUpdatedAt]);
        CourseInfo::query()->whereKey($newerCourse->id)->update(['updated_at' => $newerUpdatedAt]);
        CourseInfo::query()->whereKey($missingUpdatedAtCourse->id)->update(['updated_at' => null]);

        $response = $this->actingAs($businessUser)
            ->get('http://itami.localhost/business/courses');

        $response->assertOk();
        $response->assertSee('aria-sort="none"', false);
        $response->assertSee('sort=updated_at', false);
        $response->assertSee('direction=desc', false);
        $response->assertSee('24/03/01 08:05');
        $response->assertSee('26/09/24 13:08');
        $response->assertDontSee('08:05:30');
        $response->assertDontSee('13:08:45');

        $content = $response->getContent();
        $this->assertIsString($content);

        preg_match('/<thead[\s\S]*?<\/thead>/', $content, $headerMatches);
        $this->assertNotEmpty($headerMatches);
        $header = $headerMatches[0];
        $statePosition = strpos($header, '状態');
        $updatedPosition = strpos($header, '更新日時');
        $actionPosition = strpos($header, '操作');
        $this->assertNotFalse($statePosition);
        $this->assertNotFalse($updatedPosition);
        $this->assertNotFalse($actionPosition);
        $this->assertLessThan($updatedPosition, $statePosition);
        $this->assertLessThan($actionPosition, $updatedPosition);

        preg_match_all(
            '/<div class="text-sm font-medium text-gray-900">\s*([^<]+?)\s*<\/div>[\s\S]*?<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">\s*([^<]*?)\s*<\/td>/',
            $content,
            $rowMatches,
            PREG_SET_ORDER
        );

        $this->assertSame([
            ['古い更新コース', '24/03/01 08:05'],
            ['新しい更新コース', '26/09/24 13:08'],
            ['日時なしコース', '-'],
        ], array_map(
            fn (array $row): array => [$row[1], $row[2]],
            $rowMatches
        ));
    }

    public function test_updated_at_sort_applies_across_pages_and_keeps_nulls_last(): void
    {
        [$businessUser, $business, $classroom] = $this->createBusinessContext();

        $updatedAts = [];
        foreach (range(1, 11) as $number) {
            $course = $this->createCourse($business, $classroom, sprintf('コース%02d', $number));
            $updatedAts[$course->id] = Carbon::parse('2024-01-01 09:00:00', 'Asia/Tokyo')->addDays($number - 1);
            CourseInfo::query()->whereKey($course->id)->update([
                'updated_at' => $updatedAts[$course->id],
            ]);
        }

        $missingUpdatedAtCourse = $this->createCourse($business, $classroom, '日時なしコース');
        CourseInfo::query()->whereKey($missingUpdatedAtCourse->id)->update(['updated_at' => null]);

        $newestFirstPage = $this->actingAs($businessUser)
            ->get('http://itami.localhost/business/courses?sort=updated_at&direction=desc');
        $newestSecondPage = $this->actingAs($businessUser)
            ->get('http://itami.localhost/business/courses?sort=updated_at&direction=desc&page=2');
        $oldestSecondPage = $this->actingAs($businessUser)
            ->get('http://itami.localhost/business/courses?sort=updated_at&direction=asc&page=2');

        $newestFirstPage->assertOk();
        $newestFirstPage->assertSee('aria-sort="descending"', false);
        $newestFirstPage->assertSee('direction=asc', false);
        $newestFirstPage->assertSee('sort=updated_at&amp;direction=desc&amp;page=2', false);
        $newestFirstPage->assertSeeInOrder(['コース11', 'コース02']);
        $newestFirstPage->assertDontSee('コース01');
        $newestFirstPage->assertDontSee('日時なしコース');

        $newestSecondPage->assertOk();
        $newestSecondPage->assertSeeInOrder(['コース01', '日時なしコース']);
        $newestSecondPage->assertDontSee('コース11');
        $newestSecondPage->assertSee('sort=updated_at&amp;direction=desc&amp;page=1', false);

        $oldestSecondPage->assertOk();
        $oldestSecondPage->assertSee('aria-sort="ascending"', false);
        $oldestSecondPage->assertSeeInOrder(['コース11', '日時なしコース']);
        $oldestSecondPage->assertDontSee('コース01');
    }

    /**
     * @return array{0: User, 1: BusinessInfo, 2: ClassroomInfo}
     */
    private function createBusinessContext(): array
    {
        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'itami',
            'is_active' => true,
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'business_course_list_'.uniqid(),
            'email' => 'business-course-list-'.uniqid().'@example.com',
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => 'テスト代表',
            'representative_name_kana' => 'テストダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'business-course-list-info-'.uniqid().'@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'コース一覧教室',
        ]);

        return [$businessUser, $business, $classroom];
    }

    private function createCourse(BusinessInfo $business, ClassroomInfo $classroom, string $courseName): CourseInfo
    {
        return CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => $courseName,
            'price' => 1000,
            'is_active' => true,
        ]);
    }
}
