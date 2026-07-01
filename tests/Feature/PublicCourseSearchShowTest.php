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

class PublicCourseSearchShowTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

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

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'business_pcst',
            'is_active' => true,
        ]);

        $this->business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => '公開テスト事業者',
            'business_name_kana' => 'コウカイテストジギョウシャ',
            'representative_name' => '代表者',
            'representative_name_kana' => 'ダイヒョウシャ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => 'テスト1-1',
            'phone' => '072-123-4567',
            'email' => 'pcst-business@example.com',
            'email_timing' => CouponNotificationFrequency::Immediate->value,
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
        ]);

        $parentCategory = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親カテゴリPCST',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $category = CourseCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parentCategory->id,
            'name' => 'カテゴリPCST',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => '公開テスト教室',
            'classroom_name_kana' => 'コウカイテストキョウシツ',
            'classroom_representative_name' => '教室責任者',
            'classroom_postal_code' => '664-0001',
            'classroom_prefecture' => '兵庫県',
            'classroom_city' => '伊丹市',
            'classroom_address1' => 'テスト1-1',
            'classroom_email' => 'pcst-classroom@example.com',
            'business_hours' => '平日10:00〜20:00',
            'holiday' => '日曜・祝日',
            'classroom_introduction' => '紹介',
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'apply' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_search_lists_business_hours_and_holiday(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'www.localhost'])
            ->get('/course/search?tab=condition');

        $response->assertOk();
        $response->assertSee('平日10:00〜20:00', false);
        $response->assertSee('日曜・祝日', false);
        $response->assertSee('営業時間', false);
        $response->assertSee('定休日', false);
    }

    public function test_show_displays_business_hours_and_holiday(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'www.localhost'])
            ->get('/course/'.$this->classroom->id);

        $response->assertOk();
        $response->assertSee('平日10:00〜20:00', false);
        $response->assertSee('日曜・祝日', false);
        $response->assertSee('営業時間', false);
        $response->assertSee('定休日', false);
    }
}
