<?php

namespace Tests\Feature\Business;

use App\Models\BankBranch;
use App\Models\BusinessInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessProfileControllerTest extends TestCase
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
            'name' => 'www',
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
            'email' => 'business-profile@example.com',
            'is_active' => true,
        ]);

        BankBranch::create([
            'management_code' => '12345678',
            'bank_code' => '0001',
            'bank_name' => 'テスト銀行',
            'bank_name_kana' => 'ﾃｽﾄ',
            'branch_code' => '001',
            'branch_name' => '本店',
            'branch_name_kana' => 'ﾎﾝﾃﾝ',
        ]);

        $this->businessInfo = BusinessInfo::create([
            'user_id' => $this->businessUser->id,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_title' => '代表取締役',
            'representative_title_kana' => 'ダイヒョウトリシマリヤク',
            'representative_family_name' => '山田',
            'representative_given_name' => '太郎',
            'representative_name' => '山田 太郎',
            'representative_family_name_kana' => 'ヤマダ',
            'representative_given_name_kana' => 'タロウ',
            'representative_name_kana' => 'ヤマダ タロウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'building_name' => 'テストビル101',
            'phone' => '072-123-4567',
            'fax' => '072-111-2222',
            'email' => 'business-profile@example.com',
            'website_url' => 'https://example.com/biz',
            'contact_person' => '担当一郎',
            'contact_phone' => '072-999-8888',
            'document_person' => '送付先宛名',
            'document_address' => '兵庫県伊丹市送付1-1',
            'business_hours' => '平日9:00-18:00',
            'holiday' => '土日祝',
            'bank_code' => '0001',
            'branch_code' => '001',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder_name' => 'ﾃｽﾄｶﾌﾞｼｷｶﾞｲｼﾔ',
            'email_timing' => 'immediate',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
        ]);
    }

    /**
     * 未ログインでは設定画面にアクセスできないこと
     */
    public function test_profile_edit_requires_auth(): void
    {
        $response = $this->get('http://www.localhost/business/profile/edit');

        $response->assertRedirect();
    }

    /**
     * ログイン後に設定画面が表示され businessInfo が渡されていること
     */
    public function test_profile_edit_displays_and_passes_business_info(): void
    {
        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/profile/edit');

        $response->assertStatus(200);
        $response->assertSee('メールアドレス');
        $response->assertSee('メール通知設定');
        $response->assertSee('クーポン受付メールの通知設定');
        $response->assertSee('都度');
        $response->assertSee('一日1回（9時頃）');
        $response->assertSee('通知しない');
        $this->assertEquals($this->businessInfo->id, $response->viewData('businessInfo')->id);
    }

    /**
     * メール通知設定を更新できること
     */
    public function test_update_notification_updates_email_timing(): void
    {
        $this->businessInfo->update(['email_timing' => 'immediate']);

        $response = $this->actingAs($this->businessUser)
            ->put('http://www.localhost/business/profile/notification', [
                'email_timing' => 'daily',
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect(route('business.profile.edit'));
        $response->assertSessionHas('success', 'メール通知設定を更新しました。');
        $this->businessInfo->refresh();
        $this->assertSame('daily', $this->businessInfo->email_timing);
    }

    /**
     * メール通知設定は immediate / daily / none のみ受け付けること
     */
    public function test_update_notification_validates_allowed_values(): void
    {
        $response = $this->actingAs($this->businessUser)
            ->put('http://www.localhost/business/profile/notification', [
                'email_timing' => 'invalid',
                '_token' => csrf_token(),
            ]);

        $response->assertSessionHasErrors('email_timing');
        $this->businessInfo->refresh();
        $this->assertSame('immediate', $this->businessInfo->email_timing);
    }

    /**
     * 未ログインではメール通知設定の更新ができないこと
     */
    public function test_update_notification_requires_auth(): void
    {
        $response = $this->put('http://www.localhost/business/profile/notification', [
            'email_timing' => 'daily',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
    }

    /**
     * 未ログインでは登録内容確認ページにアクセスできないこと
     */
    public function test_registration_confirm_requires_auth(): void
    {
        $response = $this->get('http://www.localhost/business/profile/registration/confirm');

        $response->assertRedirect();
    }

    /**
     * ログイン後に登録内容確認ページが表示され、代表者名結合・住所結合・銀行/支店名が表示されること
     */
    public function test_registration_confirm_displays_registration_details(): void
    {
        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/profile/registration/confirm');

        $response->assertStatus(200);
        $response->assertViewIs('business.profile.registration-confirm');
        $response->assertSee('登録内容の確認', false);
        $response->assertSee('法人', false);
        $response->assertSee('テスト事業者', false);
        $response->assertSee('山田　太郎', false);
        $response->assertSee('ヤマダ　タロウ', false);
        $response->assertSee('兵庫県伊丹市荻野1-1-1テストビル101', false);
        $response->assertSee('テスト銀行', false);
        $response->assertSee('本店', false);
        $response->assertSee('ﾃｽﾄｶﾌﾞｼｷｶﾞｲｼﾔ', false);
    }
}
