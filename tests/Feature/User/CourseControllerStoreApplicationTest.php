<?php

namespace Tests\Feature\User;

use App\Enums\CouponNotificationFrequency;
use App\Mail\ImmediateCouponAppliedMail;
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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CourseControllerStoreApplicationTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $user;

    private BusinessInfo $business;

    private ClassroomInfo $classroom;

    private CourseInfo $course;

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
    }

    /**
     * email_timing が immediate のとき、申し込み成功後に都度通知メールが送信されること
     */
    public function test_store_application_sends_immediate_notification_mail_when_email_timing_is_immediate(): void
    {
        Mail::fake();

        $url = 'http://www.localhost/user/course/'.$this->classroom->id.'/'.$this->course->id.'/application';
        $response = $this->actingAs($this->user)
            ->post($url, [
                'memo' => 'テストメモ',
            ]);

        $response->assertRedirect(route('user.course.show', $this->classroom->id));
        $response->assertSessionHas('success', 'クーポンでの申し込みが完了しました。');

        $this->assertDatabaseHas('voucher_usages', [
            'user_id' => $this->user->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => $this->course->id,
            'amount' => 1000,
            'is_cancelled' => false,
        ]);

        Mail::assertSent(ImmediateCouponAppliedMail::class);
    }

    /**
     * email_timing が daily のとき、申し込み成功しても都度通知メールは送信されないこと
     */
    public function test_store_application_does_not_send_mail_when_email_timing_is_daily(): void
    {
        Mail::fake();
        $this->business->update(['email_timing' => CouponNotificationFrequency::Daily->value]);

        $url = 'http://www.localhost/user/course/'.$this->classroom->id.'/'.$this->course->id.'/application';
        $response = $this->actingAs($this->user)
            ->post($url, ['memo' => '']);

        $response->assertRedirect(route('user.course.show', $this->classroom->id));
        $response->assertSessionHas('success');
        Mail::assertNotSent(ImmediateCouponAppliedMail::class);
    }

    /**
     * email_timing が none のとき、申し込み成功しても都度通知メールは送信されないこと
     */
    public function test_store_application_does_not_send_mail_when_email_timing_is_none(): void
    {
        Mail::fake();
        $this->business->update(['email_timing' => CouponNotificationFrequency::None->value]);

        $url = 'http://www.localhost/user/course/'.$this->classroom->id.'/'.$this->course->id.'/application';
        $response = $this->actingAs($this->user)
            ->post($url, ['memo' => '']);

        $response->assertRedirect(route('user.course.show', $this->classroom->id));
        $response->assertSessionHas('success');
        Mail::assertNotSent(ImmediateCouponAppliedMail::class);
    }

    /**
     * 事業者メールが未設定の場合、申し込みは成功しメールは送信されないこと
     */
    public function test_store_application_succeeds_without_sending_mail_when_business_email_is_empty(): void
    {
        Mail::fake();
        $this->business->update(['email' => '']);

        $url = 'http://www.localhost/user/course/'.$this->classroom->id.'/'.$this->course->id.'/application';
        $response = $this->actingAs($this->user)
            ->post($url, ['memo' => '']);

        $response->assertRedirect(route('user.course.show', $this->classroom->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voucher_usages', [
            'user_id' => $this->user->id,
            'classroom_info_id' => $this->classroom->id,
        ]);
        Mail::assertNotSent(ImmediateCouponAppliedMail::class);
    }

    /**
     * メール送信で例外が発生しても申し込みは成功しリダイレクトされること
     */
    public function test_store_application_succeeds_even_when_mail_send_throws(): void
    {
        $service = $this->createMock(\App\Services\ImmediateCouponAppliedNotificationService::class);
        $service->expects($this->once())
            ->method('sendIfImmediate')
            ->willThrowException(new \RuntimeException('Mail transport error'));
        $this->app->instance(\App\Services\ImmediateCouponAppliedNotificationService::class, $service);

        $url = 'http://www.localhost/user/course/'.$this->classroom->id.'/'.$this->course->id.'/application';
        $response = $this->actingAs($this->user)
            ->post($url, ['memo' => '']);

        $response->assertRedirect(route('user.course.show', $this->classroom->id));
        $response->assertSessionHas('success', 'クーポンでの申し込みが完了しました。');
        $this->assertDatabaseHas('voucher_usages', [
            'user_id' => $this->user->id,
            'classroom_info_id' => $this->classroom->id,
        ]);
    }

    /**
     * 金額指定利用が禁止された教室では、金額指定申込画面へのアクセスが404になること
     */
    public function test_application_with_amount_specified_returns_404_when_disallow_flag_is_true(): void
    {
        $this->classroom->update(['disallow_amount_specified_usage' => true]);

        $url = 'http://www.localhost/user/course/'.$this->classroom->id.'/-1/application';
        $response = $this->actingAs($this->user)->get($url);

        $response->assertNotFound();
    }

    /**
     * 金額指定利用が禁止された教室では、金額指定申込POSTが404になること
     */
    public function test_store_application_with_amount_specified_returns_404_when_disallow_flag_is_true(): void
    {
        $this->classroom->update(['disallow_amount_specified_usage' => true]);

        $url = 'http://www.localhost/user/course/'.$this->classroom->id.'/-1/application';
        $response = $this->actingAs($this->user)->post($url, [
            'amount' => 500,
            'memo' => '',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('voucher_usages', [
            'user_id' => $this->user->id,
            'classroom_info_id' => $this->classroom->id,
            'amount' => 500,
        ]);
    }

    /**
     * 金額指定利用が許可されている教室では、金額指定申込が成功すること
     */
    public function test_store_application_with_amount_specified_succeeds_when_allowed(): void
    {
        Mail::fake();
        $this->classroom->update(['disallow_amount_specified_usage' => false]);

        $url = 'http://www.localhost/user/course/'.$this->classroom->id.'/-1/application';
        $response = $this->actingAs($this->user)->post($url, [
            'amount' => 500,
            'memo' => 'メモ',
        ]);

        $response->assertRedirect(route('user.course.show', $this->classroom->id));
        $response->assertSessionHas('success', 'クーポンでの申し込みが完了しました。');

        $this->assertDatabaseHas('voucher_usages', [
            'user_id' => $this->user->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => null,
            'amount' => 500,
            'is_cancelled' => false,
        ]);
    }
}
