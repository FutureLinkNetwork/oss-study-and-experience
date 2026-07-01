<?php

namespace Tests\Feature;

use App\Mail\PaymentSummaryUpdatedMail;
use App\Models\BusinessInfo;
use App\Models\BusinessPaymentDownload;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\PaymentAggregate;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AggregateBusinessPaymentsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 前月のキャンセルされていない VoucherUsage が集計され、payment_aggregates に保存されること
     */
    public function test_aggregates_previous_month_usage_into_payment_aggregates(): void
    {
        $lastMonth = Carbon::today()->subMonth();
        $subdomain = Subdomain::factory()->create();
        $businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $userRole = Role::create([
            'name' => 'subdomain_user',
            'display_name' => '利用者',
            'is_global' => false,
            'level' => 10,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'agg_business_'.uniqid(),
            'email' => 'agg_biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'agg_user_'.uniqid(),
            'email' => 'agg_usr_'.uniqid().'@example.com',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Business',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'b1@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Test Classroom',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $course = CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'Test Course',
            'price' => 5000,
            'is_active' => 1,
        ]);

        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 5000,
            'used_at' => $lastMonth->copy()->day(15)->startOfDay(),
            'is_cancelled' => false,
        ]);

        Mail::fake();

        $this->artisan('app:aggregate-business-payments')
            ->assertSuccessful();

        $this->assertDatabaseCount('payment_aggregates', 1);
        $agg = PaymentAggregate::first();
        $this->assertNotNull($agg);
        $this->assertEquals($lastMonth->format('Y-m').'-01', $agg->target_month->format('Y-m-d'));
        $this->assertEquals($subdomain->id, $agg->subdomain_id);
        $this->assertEquals($business->id, $agg->business_info_id);
        $this->assertEquals($classroom->id, $agg->classroom_info_id);
        $this->assertEquals(1, $agg->application_count);
        $this->assertEquals(5000, $agg->total_amount);
        $this->assertTrue($agg->is_public_funds_transfer_target);

        $this->assertDatabaseCount('business_payment_downloads', 1);
        $download = BusinessPaymentDownload::first();
        $this->assertNotNull($download);
        $this->assertEquals($subdomain->id, $download->subdomain_id);
        $this->assertEquals($business->id, $download->business_info_id);
        $this->assertEquals($lastMonth->format('Y-m').'-01', $download->target_month->format('Y-m-d'));
        $this->assertNull($download->downloaded_at);

        Mail::assertSent(PaymentSummaryUpdatedMail::class, 1);
    }

    /**
     * キャンセル済みの VoucherUsage は集計対象外であること
     */
    public function test_excludes_cancelled_voucher_usages(): void
    {
        $lastMonth = Carbon::today()->subMonth();
        $subdomain = Subdomain::factory()->create();
        $businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $userRole = Role::create([
            'name' => 'subdomain_user',
            'display_name' => '利用者',
            'is_global' => false,
            'level' => 10,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'agg_business_'.uniqid(),
            'email' => 'agg_biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'agg_user_'.uniqid(),
            'email' => 'agg_usr_'.uniqid().'@example.com',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Business',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'b2@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Test Classroom',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $course = CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'Test Course',
            'price' => 3000,
            'is_active' => 1,
        ]);

        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 3000,
            'used_at' => $lastMonth->copy()->day(10)->startOfDay(),
            'is_cancelled' => true,
        ]);

        $this->artisan('app:aggregate-business-payments')
            ->assertSuccessful();

        $this->assertDatabaseCount('payment_aggregates', 0);
        $this->assertDatabaseCount('business_payment_downloads', 0);
    }

    /**
     * 対象月の集計は都度全削除のうえ再作成されるため、全件キャンセル後の再実行で payment_aggregates が残らないこと
     */
    public function test_second_run_after_cancel_clears_payment_aggregates_for_target_month(): void
    {
        $lastMonth = Carbon::today()->subMonth();
        $targetMonthDate = $lastMonth->copy()->startOfMonth()->toDateString();
        $subdomain = Subdomain::factory()->create();
        $businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $userRole = Role::create([
            'name' => 'subdomain_user',
            'display_name' => '利用者',
            'is_global' => false,
            'level' => 10,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'agg2_business_'.uniqid(),
            'email' => 'agg2_biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'agg2_user_'.uniqid(),
            'email' => 'agg2_usr_'.uniqid().'@example.com',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Business Clear',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'b-clear@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Test Classroom Clear',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $course = CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'Test Course Clear',
            'price' => 5000,
            'is_active' => 1,
        ]);

        $usage = VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 4000,
            'used_at' => $lastMonth->copy()->day(12)->startOfDay(),
            'is_cancelled' => false,
        ]);

        Mail::fake();

        $this->artisan('app:aggregate-business-payments', ['--skip-notification' => true])
            ->assertSuccessful();
        $this->assertDatabaseCount('payment_aggregates', 1);

        $usage->update(['is_cancelled' => true]);

        $this->artisan('app:aggregate-business-payments', ['--skip-notification' => true])
            ->assertSuccessful();
        $this->assertDatabaseCount('payment_aggregates', 0);
        $this->assertDatabaseMissing('payment_aggregates', [
            'target_month' => $targetMonthDate,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
        ]);
    }

    /**
     * 前月に未キャンセルの利用がなくても、対象月の既存 payment_aggregates は削除されること
     */
    public function test_deletes_all_payment_aggregates_for_target_month_before_rebuild(): void
    {
        $lastMonth = Carbon::today()->subMonth();
        $targetMonthDate = $lastMonth->copy()->startOfMonth()->toDateString();
        $subdomain = Subdomain::factory()->create();
        $businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'agg3_business_'.uniqid(),
            'email' => 'agg3_biz_'.uniqid().'@example.com',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Stale Only Biz',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'b-stale@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Stale Classroom',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);

        PaymentAggregate::create([
            'target_month' => $targetMonthDate,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'application_count' => 5,
            'total_amount' => 99999,
        ]);

        Mail::fake();

        $this->artisan('app:aggregate-business-payments', ['--skip-notification' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('payment_aggregates', 0);
        $this->assertDatabaseMissing('payment_aggregates', [
            'target_month' => $targetMonthDate,
            'total_amount' => 99999,
        ]);
    }

    public function test_sets_is_public_funds_transfer_target_from_business_master(): void
    {
        $lastMonth = Carbon::today()->subMonth();
        $subdomain = Subdomain::factory()->create();
        $businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $userRole = Role::create([
            'name' => 'subdomain_user',
            'display_name' => '利用者',
            'is_global' => false,
            'level' => 10,
            'is_active' => true,
        ]);

        $targetUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'agg_target_biz_'.uniqid(),
            'email' => 'agg_target_biz_'.uniqid().'@example.com',
        ]);
        $nonTargetUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'agg_non_target_biz_'.uniqid(),
            'email' => 'agg_non_target_biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'agg_consumer_'.uniqid(),
            'email' => 'agg_consumer_'.uniqid().'@example.com',
        ]);

        $targetBusiness = BusinessInfo::create([
            'user_id' => $targetUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Target Business',
            'business_name_kana' => 'ターゲット',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'target-b@example.com',
            'is_public_funds_transfer_target' => true,
            'apply' => 1,
            'is_active' => 1,
        ]);
        $nonTargetBusiness = BusinessInfo::create([
            'user_id' => $nonTargetUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Non Target Business',
            'business_name_kana' => 'ヒターゲット',
            'representative_name' => 'Rep2',
            'representative_name_kana' => 'ダイヒョウ2',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '2-2',
            'phone' => '0300000001',
            'email' => 'non-target-b@example.com',
            'is_public_funds_transfer_target' => false,
            'apply' => 1,
            'is_active' => 1,
        ]);

        $targetClassroom = ClassroomInfo::create([
            'business_info_id' => $targetBusiness->id,
            'classroom_name' => 'Target Classroom',
            'classroom_name_kana' => 'ターゲット',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $nonTargetClassroom = ClassroomInfo::create([
            'business_info_id' => $nonTargetBusiness->id,
            'classroom_name' => 'Non Target Classroom',
            'classroom_name_kana' => 'ヒターゲット',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $targetCourse = CourseInfo::create([
            'business_info_id' => $targetBusiness->id,
            'classroom_info_id' => $targetClassroom->id,
            'course_name' => 'Target Course',
            'price' => 3000,
            'is_active' => 1,
        ]);
        $nonTargetCourse = CourseInfo::create([
            'business_info_id' => $nonTargetBusiness->id,
            'classroom_info_id' => $nonTargetClassroom->id,
            'course_name' => 'Non Target Course',
            'price' => 4000,
            'is_active' => 1,
        ]);

        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $targetBusiness->id,
            'classroom_info_id' => $targetClassroom->id,
            'course_info_id' => $targetCourse->id,
            'amount' => 3000,
            'used_at' => $lastMonth->copy()->day(10)->startOfDay(),
            'is_cancelled' => false,
        ]);
        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $nonTargetBusiness->id,
            'classroom_info_id' => $nonTargetClassroom->id,
            'course_info_id' => $nonTargetCourse->id,
            'amount' => 4000,
            'used_at' => $lastMonth->copy()->day(11)->startOfDay(),
            'is_cancelled' => false,
        ]);

        Mail::fake();

        $this->artisan('app:aggregate-business-payments', ['--skip-notification' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('payment_aggregates', [
            'business_info_id' => $targetBusiness->id,
            'is_public_funds_transfer_target' => true,
            'total_amount' => 3000,
        ]);
        $this->assertDatabaseHas('payment_aggregates', [
            'business_info_id' => $nonTargetBusiness->id,
            'is_public_funds_transfer_target' => false,
            'total_amount' => 4000,
        ]);
    }
}
