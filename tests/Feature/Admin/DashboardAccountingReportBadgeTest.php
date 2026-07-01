<?php

namespace Tests\Feature\Admin;

use App\Models\AccountingReportDownload;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardAccountingReportBadgeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ダウンロードの会計用月次レポート（CSVまたはPDF）がある場合、ダッシュボードの支払集計カードにバッジで件数が表示されること
     */
    public function test_dashboard_shows_badge_count_on_payments_menu_for_undownloaded_accounting_reports(): void
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
            'login_id' => 'dash_badge_'.uniqid(),
            'email' => 'dash_badge_'.uniqid().'@example.com',
        ]);

        $targetMonth = Carbon::today()->subMonth()->format('Y-m').'-01';
        AccountingReportDownload::create([
            'subdomain_id' => $subdomain->id,
            'target_month' => $targetMonth,
            'csv_s3_key' => "subdomain_{$subdomain->id}/accounting_reports/".Carbon::parse($targetMonth)->format('Y-m').'.csv',
            'pdf_s3_key' => "subdomain_{$subdomain->id}/accounting_reports/".Carbon::parse($targetMonth)->format('Y-m').'.pdf',
        ]);
        AccountingReportDownload::create([
            'subdomain_id' => $subdomain->id,
            'target_month' => Carbon::today()->subMonths(2)->format('Y-m').'-01',
            'csv_s3_key' => "subdomain_{$subdomain->id}/accounting_reports/".Carbon::today()->subMonths(2)->format('Y-m').'.csv',
            'pdf_s3_key' => "subdomain_{$subdomain->id}/accounting_reports/".Carbon::today()->subMonths(2)->format('Y-m').'.pdf',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin');

        $response->assertStatus(200);
        $response->assertSee('支払集計');
        $response->assertSee('2');
    }

    /**
     * 未ダウンロードが0件でもダッシュボードは表示され支払集計メニューが表示されること（会計用月次レポートは統合のため単独メニューなし）
     */
    public function test_dashboard_displays_payments_menu_when_no_undownloaded_reports(): void
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
            'login_id' => 'dash_no_badge_'.uniqid(),
            'email' => 'dash_no_badge_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin');

        $response->assertStatus(200);
        $response->assertSee('支払集計');
    }
}
