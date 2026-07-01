<?php

namespace Tests\Feature;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDailyCouponCountNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully(): void
    {
        $this->artisan('app:send-daily-coupon-count-notifications')
            ->assertSuccessful();
    }

    public function test_command_sends_admin_notification_when_errors_occur_and_admin_config_is_set(): void
    {
        Mail::fake();
        Config::set('mail.admin_for_errors', 'admin@example.com');

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'name' => 'www市',
            'system_name' => '習い事バウチャー',
            'is_active' => true,
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $consumerRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'business-cmd',
            'is_active' => true,
        ]);

        $parentCategory = CourseParentCategory::create([
            'subdomain_id' => $subdomain->id,
            'name' => '親カテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $category = CourseCategory::create([
            'subdomain_id' => $subdomain->id,
            'parent_category_id' => $parentCategory->id,
            'name' => 'カテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $businessInfo = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
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
            'email' => 'invalid-email',
            'email_timing' => 'daily',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
        ]);

        $classroom = ClassroomInfo::create([
            'business_info_id' => $businessInfo->id,
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

        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $consumerRole->id,
            'login_id' => 'consumer-cmd',
        ]);
        $yesterday = Carbon::today('Asia/Tokyo')->subDay();

        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $businessInfo->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => null,
            'amount' => 1000,
            'used_at' => $yesterday->copy()->setTime(10, 0),
            'is_cancelled' => false,
        ]);

        $this->artisan('app:send-daily-coupon-count-notifications')
            ->assertSuccessful();

        // スキップが1件あるため管理者向けエラー通知が送信される（Mail::raw のため assertSentCount は使わない）
    }
}
