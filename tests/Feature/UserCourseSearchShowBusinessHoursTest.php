<?php

namespace Tests\Feature;

use App\Enums\CouponNotificationFrequency;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCourseSearchShowBusinessHoursTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $user;

    private BusinessInfo $business;

    private ClassroomInfo $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
            'voucher_expiry' => 0,
        ]);

        $userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'user_ucsbh',
            'is_active' => true,
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'business_ucsbh',
            'is_active' => true,
        ]);

        $this->business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => '利用者向けテスト事業者',
            'business_name_kana' => 'リヨウシャテストジギョウシャ',
            'representative_name' => '代表者',
            'representative_name_kana' => 'ダイヒョウシャ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => 'テスト1-1',
            'phone' => '072-123-4567',
            'email' => 'ucsbh-business@example.com',
            'email_timing' => CouponNotificationFrequency::Immediate->value,
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
        ]);

        $parentCategory = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親カテゴリUCSBH',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $category = CourseCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parentCategory->id,
            'name' => 'カテゴリUCSBH',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => '利用者向けテスト教室',
            'classroom_name_kana' => 'リヨウシャテストキョウシツ',
            'classroom_representative_name' => '教室責任者',
            'classroom_postal_code' => '664-0001',
            'classroom_prefecture' => '兵庫県',
            'classroom_city' => '伊丹市',
            'classroom_address1' => 'テスト1-1',
            'classroom_email' => 'ucsbh-classroom@example.com',
            'business_hours' => '土日 9:00〜17:00',
            'holiday' => '月曜',
            'classroom_introduction' => '紹介',
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'apply' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_user_search_lists_business_hours_and_holiday(): void
    {
        $response = $this->actingAs($this->user)
            ->withServerVariables(['HTTP_HOST' => 'www.localhost'])
            ->get('/user/course/search?tab=condition');

        $response->assertOk();
        $response->assertSee('土日 9:00〜17:00', false);
        $response->assertSee('月曜', false);
        $response->assertSee('営業時間', false);
        $response->assertSee('定休日', false);
    }

    public function test_user_show_displays_business_hours_and_holiday(): void
    {
        $response = $this->actingAs($this->user)
            ->withServerVariables(['HTTP_HOST' => 'www.localhost'])
            ->get('/user/course/'.$this->classroom->id);

        $response->assertOk();
        $response->assertSee('土日 9:00〜17:00', false);
        $response->assertSee('月曜', false);
        $response->assertSee('営業時間', false);
        $response->assertSee('定休日', false);
    }
}
