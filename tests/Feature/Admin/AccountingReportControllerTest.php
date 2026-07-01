<?php

namespace Tests\Feature\Admin;

use App\Models\AccountingReportDownload;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountingReportControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CSVダウンロード実行で csv_downloaded_at と csv_downloaded_by_user_id が更新され、CSV が返ること
     */
    public function test_download_csv_updates_record_and_returns_csv(): void
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
            'login_id' => 'dl_csv_'.uniqid(),
            'email' => 'dl_csv_'.uniqid().'@example.com',
        ]);

        $targetMonth = Carbon::today()->subMonth()->format('Y-m');
        $csvS3Key = "subdomain_{$subdomain->id}/accounting_reports/{$targetMonth}.csv";
        $pdfS3Key = "subdomain_{$subdomain->id}/accounting_reports/{$targetMonth}.pdf";
        Storage::disk('s3')->put($csvS3Key, 'dummy,csv,content');
        Storage::disk('s3')->put($pdfS3Key, 'dummy pdf');

        $record = AccountingReportDownload::create([
            'subdomain_id' => $subdomain->id,
            'target_month' => $targetMonth.'-01',
            'csv_s3_key' => $csvS3Key,
            'pdf_s3_key' => $pdfS3Key,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/accounting-reports/download-csv?month='.$targetMonth);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/csv');
        $this->assertSame('dummy,csv,content', $response->streamedContent());

        $record->refresh();
        $this->assertNotNull($record->csv_downloaded_at);
        $this->assertSame($user->id, $record->csv_downloaded_by_user_id);
        $this->assertNull($record->pdf_downloaded_at);
    }

    /**
     * PDFダウンロード実行で pdf_downloaded_at と pdf_downloaded_by_user_id が更新され、PDF が返ること
     */
    public function test_download_pdf_updates_record_and_returns_pdf(): void
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
            'login_id' => 'dl_pdf_'.uniqid(),
            'email' => 'dl_pdf_'.uniqid().'@example.com',
        ]);

        $targetMonth = Carbon::today()->subMonth()->format('Y-m');
        $csvS3Key = "subdomain_{$subdomain->id}/accounting_reports/{$targetMonth}.csv";
        $pdfS3Key = "subdomain_{$subdomain->id}/accounting_reports/{$targetMonth}.pdf";
        Storage::disk('s3')->put($csvS3Key, 'dummy,csv');
        Storage::disk('s3')->put($pdfS3Key, 'dummy pdf content');

        $record = AccountingReportDownload::create([
            'subdomain_id' => $subdomain->id,
            'target_month' => $targetMonth.'-01',
            'csv_s3_key' => $csvS3Key,
            'pdf_s3_key' => $pdfS3Key,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/accounting-reports/download-pdf?month='.$targetMonth);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertSame('dummy pdf content', $response->streamedContent());

        $record->refresh();
        $this->assertNotNull($record->pdf_downloaded_at);
        $this->assertSame($user->id, $record->pdf_downloaded_by_user_id);
        $this->assertNull($record->csv_downloaded_at);
    }

    /**
     * 申込月未指定でダウンロードすると支払集計へリダイレクトすること
     */
    public function test_download_csv_redirects_to_payments_when_month_missing(): void
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
            'login_id' => 'dl_no_month_'.uniqid(),
            'email' => 'dl_no_month_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/accounting-reports/download-csv');

        $response->assertRedirect(route('admin.payments.index'));
        $response->assertSessionHas('error', '申込月を指定してください。');
    }

    /**
     * 存在しない月でダウンロードすると支払集計（該当月付き）へエラーリダイレクトすること
     */
    public function test_download_redirects_to_payments_with_month_when_month_not_found(): void
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
            'login_id' => 'dl_nf_'.uniqid(),
            'email' => 'dl_nf_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/accounting-reports/download-csv?month=2020-01');

        $response->assertRedirect(route('admin.payments.index', ['month' => '2020-01']));
        $response->assertSessionHas('error');
    }

    public function test_download_csv_non_target_uses_non_target_columns(): void
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
            'login_id' => 'dl_csv_nt_'.uniqid(),
            'email' => 'dl_csv_nt_'.uniqid().'@example.com',
        ]);

        $targetMonth = Carbon::today()->subMonth()->format('Y-m');
        $csvS3Key = "subdomain_{$subdomain->id}/accounting_reports/{$targetMonth}_non_target.csv";
        Storage::disk('s3')->put($csvS3Key, 'non,target,csv');

        $record = AccountingReportDownload::create([
            'subdomain_id' => $subdomain->id,
            'target_month' => $targetMonth.'-01',
            'csv_s3_key_non_target' => $csvS3Key,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/accounting-reports/download-csv?month='.$targetMonth.'&category=non_target');

        $response->assertStatus(200);
        $this->assertSame('non,target,csv', $response->streamedContent());

        $record->refresh();
        $this->assertNotNull($record->csv_non_target_downloaded_at);
        $this->assertSame($user->id, $record->csv_non_target_downloaded_by_user_id);
    }
}
