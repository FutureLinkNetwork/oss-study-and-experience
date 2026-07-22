<?php

namespace Tests\Feature\Admin;

use App\Models\Beneficiary;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Subdomain $subdomain;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // サブドメインを作成
        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'is_active' => true,
        ]);

        // 管理者ロールを作成（レベル40以上・管理画面アクセス許可ロール）
        $this->adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 50,
            'is_active' => true,
        ]);

        // 管理者ユーザーを作成
        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->adminRole->id,
            'login_id' => 'admin1',
            'is_active' => true,
        ]);
    }

    /**
     * 利用者一覧が表示されることをテスト
     */
    public function test_beneficiaries_index_displays_correctly(): void
    {
        // 利用者を作成
        Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/beneficiaries');

        $response->assertStatus(200);
        $response->assertViewIs('admin.beneficiaries.index');
    }

    /**
     * 権限レベルが40未満のユーザーはアクセスできないことをテスト
     */
    public function test_beneficiaries_index_requires_level_40_or_above(): void
    {
        // レベル30のロール（管理画面非許可）を作成
        $lowLevelRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 30,
            'is_active' => true,
        ]);

        $lowLevelUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $lowLevelRole->id,
            'login_id' => 'business1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lowLevelUser)
            ->get('http://test.localhost/admin/beneficiaries');

        $response->assertStatus(403);
    }

    /**
     * 利用者詳細が表示されることをテスト
     */
    public function test_beneficiary_show_displays_correctly(): void
    {
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get("http://test.localhost/admin/beneficiaries/{$beneficiary->id}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.beneficiaries.show');
    }

    /**
     * 利用者情報が更新できることをテスト
     */
    public function test_beneficiary_can_be_updated(): void
    {
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->adminUser->id,
        ]);

        $updateData = [
            'certification_number' => 'TEST-001',
            'certification_date' => '2024-01-01',
            'status' => '決定通知書未送信',
            'guardian_name' => 'テスト保護者',
            'guardian_birth_date' => '1980-01-01',
            'guardian_address' => 'テスト住所',
            'guardian_phone' => '090-1234-5678',
            'guardian_email' => 'test@example.com',
            'child_name' => 'テスト児童',
            'child_birth_date' => '2010-01-01',
            'elementary_school_name' => 'テスト小学校',
            'grade' => '6年生',
            'child_address' => 'テスト児童住所',
            'application_date' => '2024-01-01',
            'remarks' => '運営メモ：連絡済み',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put("http://test.localhost/admin/beneficiaries/{$beneficiary->id}", $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'certification_number' => 'TEST-001',
            'guardian_name' => 'テスト保護者',
            'remarks' => '運営メモ：連絡済み',
        ]);

        $showResponse = $this->actingAs($this->adminUser)
            ->get("http://test.localhost/admin/beneficiaries/{$beneficiary->id}");
        $showResponse->assertOk();
        $showResponse->assertSee('運営メモ：連絡済み');
    }

    /**
     * 検索機能が動作することをテスト
     */
    public function test_beneficiaries_can_be_searched(): void
    {
        Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->adminUser->id,
            'certification_number' => 'SEARCH-001',
            'guardian_name' => '検索テスト',
        ]);

        Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->adminUser->id,
            'certification_number' => 'OTHER-001',
            'guardian_name' => 'その他',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/beneficiaries?certification_number=SEARCH-001');

        $response->assertStatus(200);
        $response->assertSee('SEARCH-001');
        $response->assertDontSee('OTHER-001');
    }

    /**
     * メール一括送信（送信待ち登録）が動作することをテスト
     */
    public function test_send_bulk_login_info_marks_beneficiaries_as_pending(): void
    {
        $beneficiary1 = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => null,
            'status' => '決定通知書未送信',
        ]);

        $beneficiary2 = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => null,
            'status' => '決定通知書未送信',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('http://test.localhost/admin/beneficiaries/send-bulk-login-info', [
                'status' => '決定通知書未送信',
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('http://test.localhost/admin/beneficiaries');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary1->id,
            'status' => '決定通知書送信待ち',
            'pending_voucher_issue' => false,
        ]);
        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary2->id,
            'status' => '決定通知書送信待ち',
            'pending_voucher_issue' => false,
        ]);
    }

    /**
     * クーポン付与チェックONで送信待ち登録し、pending_voucher_issue が立つことをテスト
     */
    public function test_send_bulk_login_info_with_issue_voucher_sets_pending_flag(): void
    {
        $this->subdomain->update([
            'voucher_amount' => 10000,
            'voucher_expiry' => 6,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => null,
            'status' => '決定通知書未送信',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('http://test.localhost/admin/beneficiaries/send-bulk-login-info', [
                'status' => '決定通知書未送信',
                'issue_voucher' => '1',
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('http://test.localhost/admin/beneficiaries');
        $response->assertSessionHas('success');
        $this->assertStringContainsString('クーポンを付与', session('success'));

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => '決定通知書送信待ち',
            'pending_voucher_issue' => true,
        ]);
    }

    /**
     * クーポン設定未完了でクーポン付与チェックONの場合は一括送信を中止する
     */
    public function test_send_bulk_login_info_with_issue_voucher_fails_when_settings_missing(): void
    {
        $this->subdomain->update([
            'voucher_amount' => null,
            'voucher_expiry' => null,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => null,
            'status' => '決定通知書未送信',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->from('http://test.localhost/admin/beneficiaries?status='.urlencode('決定通知書未送信'))
            ->post('http://test.localhost/admin/beneficiaries/send-bulk-login-info', [
                'status' => '決定通知書未送信',
                'issue_voucher' => '1',
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => '決定通知書未送信',
            'pending_voucher_issue' => false,
        ]);
    }

    /**
     * 一括送信が検索条件（guardian_name / child_id）を反映することをテスト
     */
    public function test_send_bulk_login_info_applies_search_filters(): void
    {
        $matched = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => null,
            'status' => '決定通知書未送信',
            'guardian_name' => '対象保護者',
            'child_id' => 'CHILD-MATCH',
        ]);

        $unmatchedByName = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => null,
            'status' => '決定通知書未送信',
            'guardian_name' => '別の保護者',
            'child_id' => 'CHILD-OTHER',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('http://test.localhost/admin/beneficiaries/send-bulk-login-info', [
                'status' => '決定通知書未送信',
                'guardian_name' => '対象保護者',
                'child_id' => 'CHILD-MATCH',
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('http://test.localhost/admin/beneficiaries');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $matched->id,
            'status' => '決定通知書送信待ち',
        ]);
        $this->assertDatabaseHas('beneficiaries', [
            'id' => $unmatchedByName->id,
            'status' => '決定通知書未送信',
        ]);
    }

    /**
     * メール送信対象がいない場合はエラーでリダイレクトすることをテスト
     */
    public function test_send_bulk_login_info_redirects_with_error_when_no_beneficiaries(): void
    {
        // 決定通知書未送信の利用者を作成しない
        Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'status' => '決定通知書送信済',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('http://test.localhost/admin/beneficiaries/send-bulk-login-info', [
                'status' => '決定通知書未送信',
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect('http://test.localhost/admin/beneficiaries');
        $response->assertSessionHas('error');
    }

    /**
     * CSV出力が検索条件を反映してダウンロードされることをテスト
     */
    public function test_export_returns_csv_with_filter_applied(): void
    {
        Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'certification_number' => 'EXPORT-CERT',
            'guardian_name' => 'CSV出力対象',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/beneficiaries/export?certification_number=EXPORT-CERT');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=Shift_JIS');
        $this->assertStringContainsString('EXPORT-CERT', $response->streamedContent());
    }

    /**
     * CSV出力はレベル40未満でアクセス不可
     */
    public function test_export_requires_level_40_or_above(): void
    {
        $lowLevelRole = Role::factory()->create(['name' => 'low', 'level' => 30, 'is_active' => true]);
        $lowUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $lowLevelRole->id,
            'login_id' => 'lowuser',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lowUser)
            ->get('http://test.localhost/admin/beneficiaries/export');

        $response->assertStatus(403);
    }
}
