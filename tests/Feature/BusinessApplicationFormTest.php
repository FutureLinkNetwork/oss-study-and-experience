<?php

namespace Tests\Feature;

use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessApplicationFormTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
        ]);
    }

    /**
     * 事業者登録申請フォーム（入力画面）が表示され、一時保存ボタンが含まれること
     */
    public function test_business_application_form_displays_with_draft_save_button(): void
    {
        $response = $this->get('http://www.localhost/business_form');

        $response->assertStatus(200);
        $response->assertSee('事業者登録申請フォーム', false);
        $response->assertSee('一時保存', false);
        $response->assertSee('btn-draft-save', false);
    }

    /**
     * 申請完了ページが表示され、一時保存Cookie削除用のスクリプトが含まれること
     */
    public function test_business_application_complete_page_displays_and_clears_draft_cookies(): void
    {
        $response = $this->get('http://www.localhost/business_form/complete');

        $response->assertStatus(200);
        $response->assertSee('事業者登録申請が完了しました', false);
        $response->assertSee('business_application_draft', false);
    }

    /**
     * 口座名義（カナ）に許可外の文字を入れた場合にバリデーションエラーになること
     */
    public function test_account_holder_kana_rejects_invalid_characters(): void
    {
        $response = $this->post('http://www.localhost/business_form/confirm', [
            'account_holder' => 'あいうえお',
        ]);

        $response->assertSessionHasErrors('account_holder');
        $this->assertStringContainsString('半角カナ', session('errors')->first('account_holder'));
    }

    /**
     * 口座名義（カナ）に許可文字のみの場合は当該フィールドのバリデーションエラーにならないこと
     */
    public function test_account_holder_kana_accepts_valid_characters(): void
    {
        $response = $this->post('http://www.localhost/business_form/confirm', [
            'account_holder' => 'ｶﾌﾞｼｷｶﾞｲｼﾔ ( ) . ｰ/, ABC123',
        ]);

        $response->assertRedirect();
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertFalse($errors->has('account_holder'), '口座名義（カナ）は許可文字のみのためエラーにならないこと');
    }
}
