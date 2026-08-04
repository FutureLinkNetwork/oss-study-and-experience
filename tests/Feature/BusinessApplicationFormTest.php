<?php

namespace Tests\Feature;

use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Subdomain;
use App\Models\User;
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
     * 反社誓約事項がサブドメイン別のテキストファイルから読み込まれること
     */
    public function test_business_application_form_displays_antisocial_forces_text_from_subdomain_assets(): void
    {
        $expectedText = 'テスト用反社誓約事項 '.uniqid();
        $textPath = public_path('subdomain_assets/'.$this->subdomain->subdomain.'/text/antisocial_forces.txt');
        $originalContents = file_exists($textPath) ? file_get_contents($textPath) : null;

        file_put_contents($textPath, $expectedText);

        try {
            $response = $this->get('http://itami.localhost/business_form');

            $response->assertStatus(200);
            $response->assertSee($expectedText, false);
        } finally {
            if ($originalContents === null) {
                if (file_exists($textPath)) {
                    unlink($textPath);
                }
            } else {
                file_put_contents($textPath, $originalContents);
            }
        }
    }

    /**
     * 必須書類アップロードの文言にサブドメイン名が表示されること
     */
    public function test_business_application_form_displays_subdomain_name_in_required_document_labels(): void
    {
        $this->subdomain->update(['name' => 'テスト市']);

        $response = $this->get('http://itami.localhost/business_form');

        $response->assertStatus(200);
        $response->assertSee('テスト市税に係る徴収金（本税及び延滞金・督促手数料）を滞納していないことの証明', false);
        $response->assertDontSee('伊丹市税に係る徴収金（本税及び延滞金・督促手数料）を滞納していないことの証明', false);
    }

    /**
     * 代表者名のプレースホルダーにサブドメイン名・カナが表示されること
     */
    public function test_business_application_form_displays_subdomain_name_in_representative_placeholders(): void
    {
        $this->subdomain->update([
            'name' => 'テスト市',
            'name_kana' => 'テストシ',
        ]);

        $response = $this->get('http://itami.localhost/business_form');

        $response->assertStatus(200);
        $response->assertSee('placeholder="姓（例） テスト市"', false);
        $response->assertSee('placeholder="セイ（例） テストシ"', false);
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

    /**
     * 習い事の種別が現在のサブドメインに紐づくカテゴリのみ表示されること
     */
    public function test_business_application_form_filters_categories_by_subdomain(): void
    {
        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other',
            'name' => 'その他市',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $parentThis = CourseParentCategory::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '当サブドメイン親カテゴリ',
            'is_active' => true,
            'sort_order' => 1,
            'created_user_id' => $user->id,
            'updated_user_id' => $user->id,
        ]);
        CourseCategory::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parentThis->id,
            'name' => '当サブドメイン子カテゴリ',
            'is_active' => true,
            'sort_order' => 1,
            'created_user_id' => $user->id,
            'updated_user_id' => $user->id,
        ]);

        $parentOther = CourseParentCategory::factory()->create([
            'subdomain_id' => $otherSubdomain->id,
            'name' => '他サブドメイン親カテゴリ',
            'is_active' => true,
            'sort_order' => 1,
            'created_user_id' => $user->id,
            'updated_user_id' => $user->id,
        ]);
        CourseCategory::factory()->create([
            'subdomain_id' => $otherSubdomain->id,
            'parent_category_id' => $parentOther->id,
            'name' => '他サブドメイン子カテゴリ',
            'is_active' => true,
            'sort_order' => 1,
            'created_user_id' => $user->id,
            'updated_user_id' => $user->id,
        ]);

        $response = $this->get('http://itami.localhost/business_form');

        $response->assertStatus(200);
        $response->assertSee('当サブドメイン子カテゴリ', false);
        $response->assertDontSee('他サブドメイン子カテゴリ', false);
    }
}
