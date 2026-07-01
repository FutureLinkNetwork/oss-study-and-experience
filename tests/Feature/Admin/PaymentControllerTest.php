<?php

namespace Tests\Feature\Admin;

use App\Models\AccountingReportDownload;
use App\Models\AdminDownload;
use App\Models\BusinessInfo;
use App\Models\BusinessPaymentDownload;
use App\Models\ClassroomInfo;
use App\Models\PaymentAggregate;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Services\PaymentNoticePdfService;
use App\Services\ZenginFormatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインでは支払集計一覧にアクセスできずログインへリダイレクトされること
     */
    public function test_payments_index_requires_auth(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $response = $this->get('http://www.localhost/admin/payments');

        $response->assertRedirect();
    }

    /**
     * 管理者ログイン後は支払集計一覧が表示されること
     */
    public function test_payments_index_displays_for_authenticated_admin(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_pay_'.uniqid(),
            'email' => 'admin_pay_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/payments');

        $response->assertStatus(200);
        $response->assertSee('支払集計');
    }

    /**
     * 選択月に会計用月次レポートがある場合、支払集計画面に会計レポートブロックとCSV/PDFリンクが表示されること
     */
    public function test_payments_index_shows_accounting_report_block_when_report_exists(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_acc_'.uniqid(),
            'email' => 'admin_acc_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Biz',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'biz@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Test Room',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $yearMonth = '2026-01';
        PaymentAggregate::create([
            'target_month' => $yearMonth.'-01',
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'application_count' => 1,
            'total_amount' => 10000,
        ]);
        $csvS3Key = "subdomain_{$subdomain->id}/accounting_reports/{$yearMonth}.csv";
        $pdfS3Key = "subdomain_{$subdomain->id}/accounting_reports/{$yearMonth}.pdf";
        Storage::disk('s3')->put($csvS3Key, 'dummy,csv');
        Storage::disk('s3')->put($pdfS3Key, 'dummy,pdf');
        AccountingReportDownload::create([
            'subdomain_id' => $subdomain->id,
            'target_month' => $yearMonth.'-01',
            'csv_s3_key' => $csvS3Key,
            'pdf_s3_key' => $pdfS3Key,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/payments?month='.$yearMonth);

        $response->assertStatus(200);
        $response->assertSee('会計用月次レポート');
        $response->assertSee('公金振替対象');
        $response->assertSee('公金振替対象外');
        $response->assertSee('申込月 '.Carbon::parse($yearMonth.'-01')->format('Y年n月'));
        $response->assertSee('CSVをダウンロード');
        $response->assertSee('PDFをダウンロード');
    }

    /**
     * admin_downloads にレコードがある場合、支払集計画面に最終更新時刻（最終作成日時）が表示されること
     */
    public function test_payments_index_shows_admin_download_last_created_at(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_dl_'.uniqid(),
            'email' => 'admin_dl_'.uniqid().'@example.com',
        ]);
        $lastCreated = Carbon::parse('2026-03-10 14:30:00');
        $download = AdminDownload::factory()->create([
            'subdomain_id' => $subdomain->id,
            'exported_at' => $lastCreated->copy()->subDay(),
            'summary' => '2026-03-09 0時時点 利用者CSV 全件',
            's3_key' => 'subdomain_1/beneficiary_exports/2026-03-09.csv',
        ]);
        $download->created_at = $lastCreated;
        $download->saveQuietly();

        $response = $this->actingAs($user)->get('http://www.localhost/admin/payments');

        $response->assertStatus(200);
        $response->assertSee('最終更新時刻: 2026-03-10 14:30');
    }

    /**
     * 選択月に会計用月次レポートが無い場合、支払集計画面に未生成メッセージが表示されること
     */
    public function test_payments_index_shows_ungenerated_message_when_no_accounting_report(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_no_acc_'.uniqid(),
            'email' => 'admin_no_acc_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Biz',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'biz@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Test Room',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $yearMonth = '2026-01';
        PaymentAggregate::create([
            'target_month' => $yearMonth.'-01',
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'application_count' => 1,
            'total_amount' => 10000,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/payments?month='.$yearMonth);

        $response->assertStatus(200);
        $response->assertSee('会計用月次レポート');
        $response->assertSee('この月の会計用月次レポートはまだ生成されていません。');
    }

    /**
     * 全銀ダウンロードは未ログインでリダイレクトされること
     */
    public function test_download_zengin_requires_auth(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $response = $this->get('http://www.localhost/admin/payments/download-zengin?month=2026-01');

        $response->assertRedirect();
    }

    /**
     * 依頼人情報が未設定のとき全銀ダウンロードでエラーメッセージ付きリダイレクトになること
     */
    public function test_download_zengin_redirects_when_zengin_header_not_configured(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_zengin_'.uniqid(),
            'email' => 'admin_zengin_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/payments/download-zengin?month=2026-01&category=target');

        $response->assertRedirect('http://www.localhost/admin/payments?month=2026-01');
        $response->assertSessionHas('error', '全銀用の依頼人情報が未設定です。システム管理で設定してください。');
    }

    /**
     * 該当月にデータが無いとき全銀ダウンロードでエラーメッセージ付きリダイレクトになること
     */
    public function test_download_zengin_redirects_when_no_data_for_month(): void
    {
        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'zengin_requester_code' => '1234567890',
            'zengin_requester_name' => 'TEST',
            'zengin_bank_code' => '0001',
            'zengin_bank_name' => 'TESTGINKO',
            'zengin_branch_code' => '001',
            'zengin_branch_name' => 'TESTTEN',
            'zengin_account_type' => '1',
            'zengin_account_number' => '1234567',
        ]);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_zengin2_'.uniqid(),
            'email' => 'admin_zengin2_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/payments/download-zengin?month=2026-01&category=target');

        $response->assertRedirect('http://www.localhost/admin/payments?month=2026-01');
        $response->assertSessionHas('error', 'この月は振込データがありません。');
    }

    public function test_download_zengin_redirects_when_category_missing(): void
    {
        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'zengin_requester_code' => '1234567890',
            'zengin_requester_name' => 'TEST',
            'zengin_bank_code' => '0001',
            'zengin_bank_name' => 'TESTGINKO',
            'zengin_branch_code' => '001',
            'zengin_branch_name' => 'TESTTEN',
            'zengin_account_type' => '1',
            'zengin_account_number' => '1234567',
        ]);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_zengin_cat_'.uniqid(),
            'email' => 'admin_zengin_cat_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/payments/download-zengin?month=2026-01');

        $response->assertRedirect('http://www.localhost/admin/payments?month=2026-01');
        $response->assertSessionHas('error');
    }

    /**
     * 依頼人情報と集計データがあるとき全銀ダウンロードでファイルが返ること
     */
    public function test_download_zengin_returns_file_when_configured_and_has_data(): void
    {
        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'zengin_requester_code' => '1234567890',
            'zengin_requester_name' => 'TEST',
            'zengin_bank_code' => '0001',
            'zengin_bank_name' => 'TESTGINKO',
            'zengin_branch_code' => '001',
            'zengin_branch_name' => 'TESTTEN',
            'zengin_account_type' => '1',
            'zengin_account_number' => '1234567',
        ]);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_zengin3_'.uniqid(),
            'email' => 'admin_zengin3_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Biz',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'biz@example.com',
            'bank_code' => '0001',
            'branch_code' => '001',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder_name' => 'ﾃｽﾄ',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Test Room',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        PaymentAggregate::create([
            'target_month' => '2026-01-01',
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'application_count' => 1,
            'total_amount' => 10000,
            'is_public_funds_transfer_target' => true,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/payments/download-zengin?month=2026-01&category=target');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('zengin_202601_target.txt', $response->headers->get('content-disposition'));
        $this->assertSame('text/plain; charset=Shift_JIS', $response->headers->get('content-type'));
    }

    public function test_download_zengin_filters_by_non_target_category(): void
    {
        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'zengin_requester_code' => '1234567890',
            'zengin_requester_name' => 'TEST',
            'zengin_bank_code' => '0001',
            'zengin_bank_name' => 'TESTGINKO',
            'zengin_branch_code' => '001',
            'zengin_branch_name' => 'TESTTEN',
            'zengin_account_type' => '1',
            'zengin_account_number' => '1234567',
        ]);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_zengin_nt_'.uniqid(),
            'email' => 'admin_zengin_nt_'.uniqid().'@example.com',
        ]);
        $targetBusiness = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Target Biz',
            'business_name_kana' => 'ターゲット',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'target@example.com',
            'bank_code' => '0001',
            'branch_code' => '001',
            'account_type' => '普通',
            'account_number' => '1111111',
            'account_holder_name' => 'ﾀｰｹﾞｯﾄ',
            'is_public_funds_transfer_target' => true,
            'apply' => 1,
            'is_active' => 1,
        ]);
        $nonTargetBusiness = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Non Target Biz',
            'business_name_kana' => 'ヒターゲット',
            'representative_name' => 'Rep2',
            'representative_name_kana' => 'ダイヒョウ2',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '2-2',
            'phone' => '0300000001',
            'email' => 'nontarget@example.com',
            'bank_code' => '0001',
            'branch_code' => '001',
            'account_type' => '普通',
            'account_number' => '2222222',
            'account_holder_name' => 'ﾋﾀｰｹﾞｯﾄ',
            'is_public_funds_transfer_target' => false,
            'apply' => 1,
            'is_active' => 1,
        ]);
        $targetClassroom = ClassroomInfo::create([
            'business_info_id' => $targetBusiness->id,
            'classroom_name' => 'Target Room',
            'classroom_name_kana' => 'ターゲット',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $nonTargetClassroom = ClassroomInfo::create([
            'business_info_id' => $nonTargetBusiness->id,
            'classroom_name' => 'Non Target Room',
            'classroom_name_kana' => 'ヒターゲット',
            'apply' => 1,
            'is_active' => 1,
        ]);
        PaymentAggregate::create([
            'target_month' => '2026-01-01',
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $targetBusiness->id,
            'classroom_info_id' => $targetClassroom->id,
            'application_count' => 1,
            'total_amount' => 10000,
            'is_public_funds_transfer_target' => true,
        ]);
        PaymentAggregate::create([
            'target_month' => '2026-01-01',
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $nonTargetBusiness->id,
            'classroom_info_id' => $nonTargetClassroom->id,
            'application_count' => 1,
            'total_amount' => 5000,
            'is_public_funds_transfer_target' => false,
        ]);

        $nonTargetResponse = $this->actingAs($user)->get('http://www.localhost/admin/payments/download-zengin?month=2026-01&category=non_target');
        $nonTargetResponse->assertStatus(200);
        $this->assertStringContainsString('zengin_202601_non_target.txt', $nonTargetResponse->headers->get('content-disposition'));

        $targetResponse = $this->actingAs($user)->get('http://www.localhost/admin/payments/download-zengin?month=2026-01&category=target');
        $targetResponse->assertStatus(200);
        $this->assertStringContainsString('zengin_202601_target.txt', $targetResponse->headers->get('content-disposition'));
    }

    /**
     * ZenginFormatService がヘッダー・データ・トレーラ・エンドの4種レコードを119桁で出力すること
     */
    public function test_zengin_format_service_builds_correct_record_structure(): void
    {
        $subdomain = Subdomain::factory()->create([
            'zengin_requester_code' => '1234567890',
            'zengin_requester_name' => 'TEST',
            'zengin_bank_code' => '0001',
            'zengin_bank_name' => 'TESTGINKO',
            'zengin_branch_code' => '001',
            'zengin_branch_name' => 'TESTTEN',
            'zengin_account_type' => '1',
            'zengin_account_number' => '1234567',
        ]);
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
            'login_id' => 'zengin_svc_'.uniqid(),
            'email' => 'zengin_svc_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test',
            'business_name_kana' => 'テスト',
            'representative_name' => 'T',
            'representative_name_kana' => 'T',
            'postal_code' => '1000000',
            'prefecture' => '東京都',
            'city' => '千代田区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'b@example.com',
            'bank_code' => '0001',
            'branch_code' => '001',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder_name' => 'ﾃｽﾄ',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $rows = new Collection([$business->id => 10000]);
        $businessesById = [$business->id => $business];

        $service = $this->app->make(ZenginFormatService::class);
        $body = $service->build($subdomain, '2026-01', $rows, $businessesById);

        $lines = array_values(array_filter(explode("\r\n", trim($body)), fn ($l) => $l !== ''));
        $this->assertGreaterThanOrEqual(4, count($lines));
        $this->assertStringStartsWith('1', $lines[0]);
        $this->assertStringStartsWith('2', $lines[1]);
        $this->assertStringStartsWith('8', $lines[count($lines) - 2]);
        $this->assertStringStartsWith('9', $lines[count($lines) - 1]);
        $this->assertStringContainsString('000000010000', $lines[count($lines) - 2]);
        $this->assertSame(120, mb_strlen($lines[0]), 'Header must be 119 chars');
    }

    /**
     * 支払通知PDFダウンロードは未ログインでリダイレクトされること
     */
    public function test_download_pdf_requires_auth(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $response = $this->get('http://www.localhost/admin/payments/pdf?month=2026-01&business_id=1');

        $response->assertRedirect();
    }

    /**
     * 管理者がPDFをダウンロードした場合、PDFが返り BusinessPaymentDownload.downloaded_at は更新されないこと
     */
    public function test_download_pdf_returns_pdf_and_does_not_update_business_payment_download(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);
        $adminUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_pdf_'.uniqid(),
            'email' => 'admin_pdf_'.uniqid().'@example.com',
        ]);
        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'biz_pdf_'.uniqid(),
            'email' => 'biz_pdf_'.uniqid().'@example.com',
        ]);
        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'テスト塾',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'biz@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Test Room',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $yearMonth = '2026-01';
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

        $tempPath = sys_get_temp_dir().'/admin_payment_'.uniqid().'.pdf';
        $tempDir = dirname($tempPath);
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        file_put_contents($tempPath, '%PDF-1.4 dummy');
        $this->app->instance(
            PaymentNoticePdfService::class,
            \Mockery::mock(PaymentNoticePdfService::class, function ($mock) use ($tempPath) {
                $mock->shouldReceive('generate')->andReturn($tempPath);
            })
        );

        $response = $this->actingAs($adminUser)->get('http://www.localhost/admin/payments/pdf?month='.$yearMonth.'&business_id='.$business->id);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);
        $downloadRecord->refresh();
        $this->assertNull($downloadRecord->downloaded_at, '管理者によるPDFダウンロードでは downloaded_at を更新しない');
    }
}
