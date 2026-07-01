<?php

namespace Tests\Feature\Business;

use App\Models\BusinessInfo;
use App\Models\BusinessPaymentDownload;
use App\Models\ClassroomInfo;
use App\Models\PaymentAggregate;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインでは支払管理にアクセスできずリダイレクトされること
     */
    public function test_payments_index_requires_auth(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $response = $this->get('http://www.localhost/business/payments');

        $response->assertRedirect();
    }

    /**
     * 事業者ログイン後は支払管理一覧が表示されること
     */
    public function test_payments_index_displays_for_authenticated_business(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'biz_pay_'.uniqid(),
            'email' => 'biz_pay_'.uniqid().'@example.com',
        ]);
        BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'payments-business@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/business/payments');

        $response->assertStatus(200);
        $response->assertSee('支払管理');
    }

    /**
     * 未ダウンロードの支払いがある場合、支払管理一覧に注意メッセージが表示されること
     */
    public function test_payments_index_shows_undownloaded_message_when_applicable(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'biz_pay_'.uniqid(),
            'email' => 'biz_pay_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'payments-business@example.com',
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
        $targetMonth = Carbon::today()->subMonth()->format('Y-m').'-01';
        PaymentAggregate::create([
            'target_month' => $targetMonth,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'application_count' => 1,
            'total_amount' => 5000,
        ]);
        BusinessPaymentDownload::create([
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'target_month' => $targetMonth,
            'downloaded_at' => null,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/business/payments');

        $response->assertStatus(200);
        $response->assertSee('未確認の支払い明細があります');
    }

    /**
     * PDF初回ダウンロード時にdownloaded_atが記録されること
     */
    public function test_download_pdf_sets_downloaded_at_on_first_download(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'biz_pay_'.uniqid(),
            'email' => 'biz_pay_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'payments-business@example.com',
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
        $yearMonth = Carbon::today()->subMonth()->format('Y-m');
        $targetMonthDate = $yearMonth.'-01';
        PaymentAggregate::create([
            'target_month' => $targetMonthDate,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'application_count' => 1,
            'total_amount' => 5000,
        ]);
        $downloadRecord = BusinessPaymentDownload::create([
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'target_month' => $targetMonthDate,
            'downloaded_at' => null,
        ]);

        $tempPath = sys_get_temp_dir().'/test_payment_'.uniqid().'.pdf';
        $tempDir = dirname($tempPath);
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        file_put_contents($tempPath, '%PDF-1.4 dummy');
        $this->app->instance(
            \App\Services\PaymentNoticePdfService::class,
            \Mockery::mock(\App\Services\PaymentNoticePdfService::class, function ($mock) use ($tempPath) {
                $mock->shouldReceive('generate')->andReturn($tempPath);
            })
        );

        $response = $this->actingAs($user)->get('http://www.localhost/business/payments/'.$yearMonth.'/pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $downloadRecord->refresh();
        $this->assertNotNull($downloadRecord->downloaded_at);
    }

    /**
     * CSVダウンロード：認証済み事業者は指定月の明細CSVを取得できること
     */
    public function test_download_csv_returns_csv_for_authenticated_business(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'biz_csv_'.uniqid(),
            'email' => 'biz_csv_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'payments-csv@example.com',
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
        $yearMonth = Carbon::today()->subMonth()->format('Y-m');
        $targetMonthDate = $yearMonth.'-01';
        PaymentAggregate::create([
            'target_month' => $targetMonthDate,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'application_count' => 2,
            'total_amount' => 10000,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/business/payments/'.$yearMonth.'/csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=Shift_JIS');
        $body = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('教室名', $body);
        $this->assertStringContainsString('申込件数', $body);
        $this->assertStringContainsString('金額', $body);
        $this->assertStringContainsString('Test Classroom', $body);
        $this->assertStringContainsString('2', $body);
        $this->assertStringContainsString('10000', $body);
    }

    /**
     * CSVダウンロード：未ログインではリダイレクトされること
     */
    public function test_download_csv_requires_auth(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $yearMonth = Carbon::today()->subMonth()->format('Y-m');

        $response = $this->get('http://www.localhost/business/payments/'.$yearMonth.'/csv');

        $response->assertRedirect();
    }

    /**
     * CSVダウンロード：不正な年月ではリダイレクトされること
     */
    public function test_download_csv_rejects_invalid_year_month(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'biz_inv_'.uniqid(),
            'email' => 'biz_inv_'.uniqid().'@example.com',
        ]);
        BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'inv@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/business/payments/2025-13/csv');

        $response->assertRedirect();
        $response->assertSessionHas('error', '指定が不正です。');
    }

    /**
     * CSVダウンロード：該当データが無い場合はリダイレクトされること
     */
    public function test_download_csv_redirects_when_no_aggregates(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'biz_empty_'.uniqid(),
            'email' => 'biz_empty_'.uniqid().'@example.com',
        ]);
        BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'empty@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $yearMonth = Carbon::today()->subMonth()->format('Y-m');

        $response = $this->actingAs($user)->get('http://www.localhost/business/payments/'.$yearMonth.'/csv');

        $response->assertRedirect();
        $response->assertSessionHas('error', '該当する支払データがありません。');
    }

    /**
     * CSV初回ダウンロード時にdownloaded_atが記録されること
     */
    public function test_download_csv_sets_downloaded_at_on_first_download(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'biz_csv_dl_'.uniqid(),
            'email' => 'biz_csv_dl_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'csv_dl@example.com',
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
        $yearMonth = Carbon::today()->subMonth()->format('Y-m');
        $targetMonthDate = $yearMonth.'-01';
        PaymentAggregate::create([
            'target_month' => $targetMonthDate,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'application_count' => 1,
            'total_amount' => 5000,
        ]);
        $downloadRecord = BusinessPaymentDownload::create([
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'target_month' => $targetMonthDate,
            'downloaded_at' => null,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/business/payments/'.$yearMonth.'/csv');

        $response->assertStatus(200);
        $downloadRecord->refresh();
        $this->assertNotNull($downloadRecord->downloaded_at);
    }
}
