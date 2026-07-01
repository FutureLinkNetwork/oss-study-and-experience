<?php

namespace Tests\Unit\Services;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use App\Services\DailyCouponCountNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DailyCouponCountNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private BusinessInfo $businessInfo;

    private ClassroomInfo $classroom;

    private Role $consumerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'name' => '伊丹市',
            'system_name' => '習い事バウチャー',
            'is_active' => true,
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $this->consumerRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'business-daily',
            'is_active' => true,
        ]);

        $parentCategory = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親カテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $category = CourseCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parentCategory->id,
            'name' => 'カテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $this->businessInfo = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => '代表',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'business-daily@example.com',
            'email_timing' => 'daily',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
        ]);

        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->businessInfo->id,
            'classroom_name' => 'テスト教室',
            'classroom_name_kana' => 'テストキョウシツ',
            'classroom_representative_name' => '責任者',
            'classroom_representative_name_kana' => 'セキニンシャ',
            'classroom_postal_code' => '664-0001',
            'classroom_prefecture' => '兵庫県',
            'classroom_city' => '伊丹市',
            'classroom_address1' => '荻野1-1-1',
            'classroom_email' => 'classroom@example.com',
            'business_hours' => '9:00-18:00',
            'holiday' => '日曜',
            'classroom_introduction' => '紹介',
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'apply' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_build_from_address_uses_subdomain_host(): void
    {
        Config::set('app.url', 'https://example.com');
        $service = new DailyCouponCountNotificationService;

        $address = $service->buildFromAddress($this->subdomain);

        $this->assertSame('no-reply@www.example.com', $address);
    }

    public function test_build_from_address_does_not_duplicate_subdomain_prefix(): void
    {
        Config::set('app.url', 'https://www.example.com');
        $service = new DailyCouponCountNotificationService;

        $address = $service->buildFromAddress($this->subdomain);

        $this->assertSame('no-reply@www.example.com', $address);
    }

    public function test_send_daily_notifications_sends_mail_when_there_is_count_yesterday(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->consumerRole->id,
            'login_id' => 'consumer1',
        ]);
        $yesterday = Carbon::today('Asia/Tokyo')->subDay();

        VoucherUsage::create([
            'user_id' => $user->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $this->businessInfo->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => null,
            'amount' => 1000,
            'used_at' => $yesterday->copy()->setTime(10, 0),
            'is_cancelled' => false,
        ]);

        $service = new DailyCouponCountNotificationService;
        $result = $service->sendDailyNotifications();

        $this->assertSame(1, $result['sent']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(0, $result['skipped']);
    }

    public function test_send_daily_notifications_does_not_send_when_count_is_zero(): void
    {
        Mail::fake();

        $service = new DailyCouponCountNotificationService;
        $result = $service->sendDailyNotifications();

        $this->assertSame(0, $result['sent']);
        Mail::assertSentCount(0);
    }

    public function test_send_daily_notifications_skips_business_with_invalid_email(): void
    {
        Mail::fake();

        $this->businessInfo->update(['email' => 'not-an-email']);
        $user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->consumerRole->id,
            'login_id' => 'consumer-invalid',
        ]);
        $yesterday = Carbon::today('Asia/Tokyo')->subDay();

        VoucherUsage::create([
            'user_id' => $user->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $this->businessInfo->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => null,
            'amount' => 1000,
            'used_at' => $yesterday->copy()->setTime(10, 0),
            'is_cancelled' => false,
        ]);

        $service = new DailyCouponCountNotificationService;
        $result = $service->sendDailyNotifications();

        $this->assertSame(0, $result['sent']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotEmpty($result['errors']);
        Mail::assertSentCount(0);
    }

    public function test_send_daily_notifications_excludes_cancelled_usage_from_count(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->consumerRole->id,
            'login_id' => 'consumer2',
        ]);
        $yesterday = Carbon::today('Asia/Tokyo')->subDay();

        VoucherUsage::create([
            'user_id' => $user->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $this->businessInfo->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => null,
            'amount' => 1000,
            'used_at' => $yesterday->copy()->setTime(10, 0),
            'is_cancelled' => true,
        ]);

        $service = new DailyCouponCountNotificationService;
        $result = $service->sendDailyNotifications();

        $this->assertSame(0, $result['sent']);
        Mail::assertSentCount(0);
    }

    public function test_send_daily_notifications_ignores_immediate_and_none_timing(): void
    {
        Mail::fake();

        $this->businessInfo->update(['email_timing' => 'immediate']);
        $user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->consumerRole->id,
            'login_id' => 'consumer3',
        ]);
        $yesterday = Carbon::today('Asia/Tokyo')->subDay();

        VoucherUsage::create([
            'user_id' => $user->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $this->businessInfo->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => null,
            'amount' => 1000,
            'used_at' => $yesterday->copy()->setTime(10, 0),
            'is_cancelled' => false,
        ]);

        $service = new DailyCouponCountNotificationService;
        $result = $service->sendDailyNotifications();

        $this->assertSame(0, $result['sent']);
        Mail::assertSentCount(0);
    }
}
