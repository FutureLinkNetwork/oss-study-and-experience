<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessClassroomSubdomainScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{
     *     0: User,
     *     1: BusinessInfo,
     *     2: ClassroomInfo,
     *     3: ClassroomInfo,
     *     4: CourseInfo
     * }
     */
    private function createScopedFixtures(): array
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-classroom-scope',
            'is_active' => true,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other-classroom-scope',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_classroom_scope',
            'is_active' => true,
        ]);

        $sameBusiness = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $currentSubdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => '同一サブドメイン事業者',
            'business_name_kana' => 'ドウイツサブドメインジギョウシャ',
            'representative_name' => '同一代表',
            'representative_name_kana' => 'ドウイツダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-111-1111',
            'email' => 'same-classroom-scope@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $otherBusiness = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $otherSubdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => '他サブドメイン事業者',
            'business_name_kana' => 'タサブドメインジギョウシャ',
            'representative_name' => '他代表',
            'representative_name_kana' => 'タダイヒョウ',
            'postal_code' => '664-0002',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野2-2-2',
            'phone' => '072-222-2222',
            'email' => 'other-classroom-scope@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $sameClassroom = ClassroomInfo::create([
            'business_info_id' => $sameBusiness->id,
            'classroom_name' => '同一サブドメイン教室',
            'is_active' => true,
        ]);

        $otherClassroom = ClassroomInfo::create([
            'business_info_id' => $otherBusiness->id,
            'classroom_name' => '他サブドメイン教室',
            'is_active' => true,
        ]);

        $otherCourse = CourseInfo::create([
            'business_info_id' => $otherBusiness->id,
            'classroom_info_id' => $otherClassroom->id,
            'course_name' => '他サブドメインコース',
            'price' => 1000,
            'tax_type' => 'included',
            'is_active' => true,
            'grades' => ['小学1年'],
        ]);

        return [$adminUser, $sameBusiness, $sameClassroom, $otherClassroom, $otherCourse];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function url(string $name, array $params = []): string
    {
        return 'http://current-classroom-scope.localhost'.route($name, $params, false);
    }

    public function test_edit_classroom_rejects_classroom_from_another_subdomain(): void
    {
        [$adminUser, $sameBusiness, , $otherClassroom] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get($this->url('admin.business.edit-classroom', [$sameBusiness, $otherClassroom]));

        $response->assertStatus(403);
        $response->assertDontSee('他サブドメイン教室');
    }

    public function test_edit_classroom_allows_classroom_in_current_business(): void
    {
        [$adminUser, $sameBusiness, $sameClassroom] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get($this->url('admin.business.edit-classroom', [$sameBusiness, $sameClassroom]));

        $response->assertStatus(200);
        $response->assertSee('同一サブドメイン教室');
    }

    public function test_update_classroom_rejects_classroom_from_another_subdomain(): void
    {
        [$adminUser, $sameBusiness, , $otherClassroom] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->put($this->url('admin.business.update-classroom', [$sameBusiness, $otherClassroom]), [
                'classroom_name' => '改ざんされた教室名',
                'classroom_name_kana' => 'カイザンサレタキョウシツメイ',
                'classroom_representative_name' => '改ざん代表',
                'classroom_representative_name_kana' => 'カイザンダイヒョウ',
                'classroom_postal_code' => '664-0002',
                'classroom_prefecture' => '兵庫県',
                'classroom_city' => '伊丹市',
                'classroom_address1' => '荻野2-2-2',
                'classroom_phone' => '072-222-2222',
                'business_hours' => '9:00-18:00',
                'holiday' => '日曜',
                'service_type' => '教室型',
                'lesson_category' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('classroom_infos', [
            'id' => $otherClassroom->id,
            'classroom_name' => '他サブドメイン教室',
        ]);
    }

    public function test_edit_course_rejects_course_from_another_subdomain(): void
    {
        [$adminUser, $sameBusiness, $sameClassroom, , $otherCourse] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get($this->url('admin.business.edit-course', [$sameBusiness, $sameClassroom, $otherCourse]));

        $response->assertStatus(403);
        $response->assertDontSee('他サブドメインコース');
    }
}
