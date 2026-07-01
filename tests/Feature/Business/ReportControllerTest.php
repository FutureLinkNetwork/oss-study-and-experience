<?php

namespace Tests\Feature\Business;

use App\Models\BusinessInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private Role $businessRole;

    private User $businessUser;

    private BusinessInfo $businessInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::create([
            'subdomain' => 'www',
            'name' => 'www市',
            'is_active' => true,
        ]);

        $this->businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);

        $this->businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->businessRole->id,
            'login_id' => 'business1',
            'name' => '事業者太郎',
            'display_name' => '事業者太郎',
            'email' => 'business-report@example.com',
            'is_active' => true,
        ]);

        $this->businessInfo = BusinessInfo::create([
            'user_id' => $this->businessUser->id,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => 'テスト代表',
            'representative_name_kana' => 'テストダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'business-report@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
        ]);
    }

    /**
     * 未ログインではレポートにアクセスできないこと
     */
    public function test_reports_index_requires_auth(): void
    {
        $response = $this->get('http://www.localhost/business/reports');

        $response->assertRedirect();
    }

    /**
     * ログイン後にレポート画面が表示されること
     */
    public function test_reports_index_displays_for_authenticated_business(): void
    {
        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/reports');

        $response->assertStatus(200);
        $response->assertSee('レポート');
        $response->assertSee('会計年度を選択');
    }

    /**
     * 年度を選択すると月別・教室別の表が表示されること（データ0件の場合は0件・0円）
     */
    public function test_reports_index_with_year_shows_tables_and_zero_when_no_data(): void
    {
        $currentFiscalYear = (int) (date('n') >= 4 ? date('Y') : date('Y') - 1);

        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/reports?year='.$currentFiscalYear);

        $response->assertStatus(200);
        $response->assertSee('月別内訳');
        $response->assertSee('教室別内訳');
        $response->assertSee('0件');
        $response->assertSee('¥0');
    }

    /**
     * 事業者情報がない場合はダッシュボードにリダイレクトされること
     */
    public function test_reports_index_redirects_when_no_business_info(): void
    {
        $userWithoutBusiness = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->businessRole->id,
            'login_id' => 'nobiz',
            'name' => '事業者なし',
            'display_name' => '事業者なし',
            'email' => 'nobiz@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($userWithoutBusiness)
            ->get('http://www.localhost/business/reports');

        $response->assertRedirect(route('business.dashboard'));
        $response->assertSessionHas('error', '事業者情報が見つかりません。');
    }
}
