<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessClassroomCourseListTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $adminUser;

    private BusinessInfo $business;

    private ClassroomInfo $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test-classroom-courses',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_classroom_course_list',
            'is_active' => true,
        ]);

        $this->business = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
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
            'email' => 'classroom-course-list@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => 'コース一覧教室',
            'use_map' => false,
        ]);
    }

    public function test_edit_classroom_lists_courses_by_id_with_formatted_updated_at(): void
    {
        $olderUpdatedAt = Carbon::parse('2024-03-01 08:05:30', 'Asia/Tokyo');
        $newerUpdatedAt = Carbon::parse('2026-09-24 13:08:45', 'Asia/Tokyo');

        $olderCourse = $this->createCourse('古い更新コース');
        $newerCourse = $this->createCourse('新しい更新コース');
        $missingUpdatedAtCourse = $this->createCourse('日時なしコース');

        CourseInfo::query()->whereKey($olderCourse->id)->update(['updated_at' => $olderUpdatedAt]);
        CourseInfo::query()->whereKey($newerCourse->id)->update(['updated_at' => $newerUpdatedAt]);
        CourseInfo::query()->whereKey($missingUpdatedAtCourse->id)->update(['updated_at' => null]);

        $path = route('admin.business.edit-classroom', [$this->business, $this->classroom], false);
        $response = $this->actingAs($this->adminUser)
            ->get('http://test-classroom-courses.localhost'.$path);

        $response->assertOk();
        $response->assertSee('data-course-updated-sort', false);
        $response->assertSee('aria-sort="none"', false);
        $response->assertSee('data-updated-at="'.$olderUpdatedAt->getTimestamp().'"', false);
        $response->assertSee('data-updated-at="'.$newerUpdatedAt->getTimestamp().'"', false);
        $response->assertSee('data-updated-at=""', false);
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
            '/data-updated-at="([^"]*)"[\s\S]*?<div class="text-sm font-medium text-gray-900">([^<]+)<\/div>[\s\S]*?<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">\s*¥[\d,]+<\/td>[\s\S]*?<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">\s*([^<]*?)\s*<\/td>/',
            $content,
            $rowMatches,
            PREG_SET_ORDER
        );

        $this->assertSame([
            [(string) $olderUpdatedAt->getTimestamp(), '古い更新コース', '24/03/01 08:05'],
            [(string) $newerUpdatedAt->getTimestamp(), '新しい更新コース', '26/09/24 13:08'],
            ['', '日時なしコース', '-'],
        ], array_map(
            fn (array $row): array => [$row[1], $row[2], $row[3]],
            $rowMatches
        ));
    }

    private function createCourse(string $courseName): CourseInfo
    {
        return CourseInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_info_id' => $this->classroom->id,
            'course_name' => $courseName,
            'price' => 1000,
            'is_active' => true,
        ]);
    }
}
