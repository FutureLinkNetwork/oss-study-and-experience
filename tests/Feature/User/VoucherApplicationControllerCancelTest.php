<?php

namespace Tests\Feature\User;

use App\Enums\CouponNotificationFrequency;
use App\Mail\ImmediateCouponCancelledMail;
use App\Models\Beneficiary;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseInfo;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VoucherApplicationControllerCancelTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $user;

    private BusinessInfo $business;

    private ClassroomInfo $classroom;

    private CourseInfo $course;

    private VoucherUsage $voucherUsage;

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
            'login_id' => 'user1',
            'name' => '利用者太郎',
            'display_name' => '利用者太郎',
            'email' => 'user@example.com',
            'is_active' => true,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->user->id,
        ]);

        Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $this->subdomain->id,
            'voucher_number' => 'VOUCHER-001',
            'issue_date' => Carbon::today()->subMonth(),
            'expiry_date' => Carbon::today()->addYear(),
            'amount' => 10000,
            'status' => 'unused',
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'business1',
            'is_active' => true,
        ]);

        $this->business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => '代表者',
            'representative_name_kana' => 'ダイヒョウシャ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => 'テスト1-1',
            'phone' => '072-123-4567',
            'email' => 'business@example.com',
            'email_timing' => CouponNotificationFrequency::Immediate->value,
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
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
            'name' => 'テストカテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => 'テスト教室',
            'classroom_name_kana' => 'テストキョウシツ',
            'classroom_representative_name' => '教室責任者',
            'classroom_postal_code' => '664-0001',
            'classroom_prefecture' => '兵庫県',
            'classroom_city' => '伊丹市',
            'classroom_address1' => 'テスト1-1',
            'classroom_email' => 'classroom@example.com',
            'business_hours' => '9:00-18:00',
            'holiday' => '日曜',
            'classroom_introduction' => '紹介文',
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'apply' => 1,
            'is_active' => 1,
        ]);

        $this->course = CourseInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_info_id' => $this->classroom->id,
            'course_name' => 'テストコース',
            'course_description' => '説明',
            'price' => 1000,
            'tax_type' => 'inclusive',
            'is_active' => true,
        ]);

        $this->voucherUsage = VoucherUsage::create([
            'user_id' => $this->user->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $this->business->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => $this->course->id,
            'amount' => 1000,
            'used_at' => now()->subHour(),
            'memo' => null,
            'is_cancelled' => false,
            'qr_flag' => false,
        ]);
    }

    /**
     * email_timing が immediate のとき、キャンセル成功後にキャンセル通知メールが送信されること
     */
    public function test_cancel_sends_immediate_notification_mail_when_email_timing_is_immediate(): void
    {
        Mail::fake();

        $url = 'http://www.localhost/user/applications/'.$this->voucherUsage->id.'/cancel';
        $response = $this->actingAs($this->user)->post($url);

        $response->assertRedirect(route('user.applications.index'));
        $response->assertSessionHas('success', '申込をキャンセルしました。');
        $this->voucherUsage->refresh();
        $this->assertTrue($this->voucherUsage->is_cancelled);

        Mail::assertSent(ImmediateCouponCancelledMail::class);
    }

    /**
     * email_timing が daily のとき、キャンセル成功してもキャンセル通知メールは送信されないこと
     */
    public function test_cancel_does_not_send_mail_when_email_timing_is_daily(): void
    {
        Mail::fake();
        $this->business->update(['email_timing' => CouponNotificationFrequency::Daily->value]);

        $url = 'http://www.localhost/user/applications/'.$this->voucherUsage->id.'/cancel';
        $response = $this->actingAs($this->user)->post($url);

        $response->assertRedirect(route('user.applications.index'));
        $response->assertSessionHas('success');
        Mail::assertNotSent(ImmediateCouponCancelledMail::class);
    }

    /**
     * email_timing が none のとき、キャンセル成功してもキャンセル通知メールは送信されないこと
     */
    public function test_cancel_does_not_send_mail_when_email_timing_is_none(): void
    {
        Mail::fake();
        $this->business->update(['email_timing' => CouponNotificationFrequency::None->value]);

        $url = 'http://www.localhost/user/applications/'.$this->voucherUsage->id.'/cancel';
        $response = $this->actingAs($this->user)->post($url);

        $response->assertRedirect(route('user.applications.index'));
        $response->assertSessionHas('success');
        Mail::assertNotSent(ImmediateCouponCancelledMail::class);
    }

    /**
     * キャンセル通知メール送信で例外が発生してもキャンセルは成功しリダイレクトされること
     */
    public function test_cancel_succeeds_even_when_mail_send_throws(): void
    {
        $service = $this->createMock(\App\Services\ImmediateCouponAppliedNotificationService::class);
        $service->expects($this->once())
            ->method('sendCancellationIfImmediate')
            ->willThrowException(new \RuntimeException('Mail transport error'));
        $this->app->instance(\App\Services\ImmediateCouponAppliedNotificationService::class, $service);

        $url = 'http://www.localhost/user/applications/'.$this->voucherUsage->id.'/cancel';
        $response = $this->actingAs($this->user)->post($url);

        $response->assertRedirect(route('user.applications.index'));
        $response->assertSessionHas('success', '申込をキャンセルしました。');
        $this->voucherUsage->refresh();
        $this->assertTrue($this->voucherUsage->is_cancelled);
    }
}
