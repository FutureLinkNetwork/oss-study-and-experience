<?php

namespace Tests\Feature\Business;

use App\Models\BusinessInfo;
use App\Models\BusinessPaymentDownload;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ダウンロードの支払いがある場合、ダッシュボードに支払い管理の確認を促す表示が出ること
     */
    public function test_dashboard_shows_payment_notice_row_when_undownloaded(): void
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
            'login_id' => 'biz_dash_'.uniqid(),
            'email' => 'biz_dash_'.uniqid().'@example.com',
            'last_login_at' => now(),
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
            'email' => 'dashboard-business@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $targetMonth = Carbon::today()->subMonth()->format('Y-m').'-01';
        BusinessPaymentDownload::create([
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'target_month' => $targetMonth,
            'downloaded_at' => null,
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/business');

        $response->assertStatus(200);
        $response->assertSee('支払い管理のご確認をお願いします');
    }
}
