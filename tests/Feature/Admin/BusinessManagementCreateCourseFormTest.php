<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessManagementCreateCourseFormTest extends TestCase
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
            'subdomain' => 'test-create-course',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_create_course_test',
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
            'email' => 'create-course-test@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => 'テスト教室',
        ]);
    }

    public function test_create_course_form_renders_when_course_is_null(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.create-course', [$this->business, $this->classroom]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.business.course-form');
        $response->assertViewHas('course', null);
        $response->assertSee('コース新規登録', false);
        $response->assertSee('name="open_date"', false);
        $response->assertSee('name="end_date"', false);
    }
}
