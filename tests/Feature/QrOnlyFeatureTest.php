<?php

namespace Tests\Feature;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrOnlyFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Subdomain $subdomain;

    protected User $adminUser;

    protected User $businessUser;

    protected BusinessInfo $business;

    protected ClassroomInfo $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        // サブドメインを作成
        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'is_active' => true,
        ]);

        // ロールを作成
        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 40,
        ]);

        // 管理者ユーザーを作成
        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        // 事業者ユーザーを作成
        $this->businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $businessRole->id,
            'is_active' => true,
        ]);

        // 習い事カテゴリを作成
        $parentCategory = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => 'テストカテゴリ親',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $category = CourseCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parentCategory->id,
            'name' => 'テストカテゴリ',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // 事業者を作成
        $this->business = BusinessInfo::create([
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
            'email' => 'test@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        // 教室を作成
        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => 'テスト教室',
            'classroom_name_kana' => 'テストキョウシツ',
            'classroom_representative_name' => 'テスト責任者',
            'classroom_representative_name_kana' => 'テストセキニンシャ',
            'classroom_postal_code' => '664-0001',
            'classroom_prefecture' => '兵庫県',
            'classroom_city' => '伊丹市',
            'classroom_address1' => '荻野1-1-1',
            'classroom_email' => 'classroom@example.com',
            'business_hours' => '9:00-18:00',
            'holiday' => '日曜日',
            'classroom_introduction' => 'テスト教室の紹介文',
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'apply' => 1,
            'is_active' => 1,
            'qr_only' => false,
        ]);
    }

    /**
     * qr_onlyがfalseの場合、利用者ページでボタンが有効であることをテスト
     */
    public function test_buttons_are_enabled_when_qr_only_is_false(): void
    {
        $response = $this->get('/user/course/'.$this->classroom->id);

        $response->assertStatus(200);
        $response->assertDontSee('QR決済のみ');
        $response->assertDontSee('btn-disable');
    }

    /**
     * 教室のqr_onlyがtrueでqrパラメータがない場合、ボタンが無効であることをテスト
     */
    public function test_buttons_are_disabled_when_qr_only_is_true_without_qr_param(): void
    {
        // qr_onlyを有効にする
        $this->classroom->update(['qr_only' => true]);

        $response = $this->get('/user/course/'.$this->classroom->id);

        $response->assertStatus(200);
        $response->assertSee('QR決済のみ');
        $response->assertSee('btn-disable');
        $response->assertSee('disabled');
    }

    /**
     * 教室のqr_onlyがtrueでqrパラメータがある場合、ボタンが有効であることをテスト
     */
    public function test_buttons_are_enabled_when_qr_only_is_true_with_qr_param(): void
    {
        // qr_onlyを有効にする
        $this->classroom->update(['qr_only' => true]);

        $response = $this->get('/user/course/'.$this->classroom->id.'?qr=1');

        $response->assertStatus(200);
        $response->assertSee('QR決済のみ');
        // qr=1パラメータがある場合はボタンが有効
        $response->assertDontSee('btn-disable');
    }

    /**
     * 事業者が教室詳細画面でqr_onlyを更新できることをテスト
     */
    public function test_business_can_update_qr_only_setting_for_classroom(): void
    {
        $this->actingAs($this->businessUser);

        $response = $this->put(route('business.classrooms.update', $this->classroom), [
            'classroom_introduction' => '更新後の紹介文',
            'is_active' => 1,
            'disallow_amount_specified_usage' => 0,
            'qr_only' => 1,
        ]);

        $response->assertRedirect(route('business.classrooms.show', $this->classroom));

        // データベースで教室のqr_onlyが更新されていることを確認
        $this->classroom->refresh();
        $this->assertTrue($this->classroom->qr_only);
    }

    /**
     * 管理画面の教室編集でqr_onlyを更新できることをテスト
     */
    public function test_admin_can_update_qr_only_setting_for_classroom(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->put(route('admin.business.update-classroom', [$this->business, $this->classroom]), [
            'classroom_name' => $this->classroom->classroom_name,
            'classroom_name_kana' => $this->classroom->classroom_name_kana,
            'classroom_representative_name' => $this->classroom->classroom_representative_name,
            'classroom_representative_name_kana' => $this->classroom->classroom_representative_name_kana,
            'classroom_postal_code' => $this->classroom->classroom_postal_code,
            'classroom_prefecture' => $this->classroom->classroom_prefecture,
            'classroom_city' => $this->classroom->classroom_city,
            'classroom_address1' => $this->classroom->classroom_address1,
            'classroom_phone' => '072-123-4567',
            'business_hours' => $this->classroom->business_hours,
            'holiday' => $this->classroom->holiday,
            'service_type' => '教室型',
            'lesson_category' => $this->classroom->lesson_category,
            'is_active' => 1,
            'apply' => 1,
            'disallow_amount_specified_usage' => 0,
            'qr_only' => 1,
        ]);

        $response->assertRedirect(route('admin.business.edit-classroom', [$this->business, $this->classroom]));

        $this->classroom->refresh();
        $this->assertTrue($this->classroom->qr_only);
    }

    /**
     * 事業者の教室詳細画面でqr_onlyが有効な場合、QRコードURLにパラメータが付与されることをテスト
     */
    public function test_qr_code_url_has_param_when_qr_only_is_true(): void
    {
        // qr_onlyを有効にする
        $this->classroom->update(['qr_only' => true]);

        $this->actingAs($this->businessUser);

        $response = $this->get(route('business.classrooms.show', $this->classroom));

        $response->assertStatus(200);
        // QRコードのURLに?qr=1が含まれていることを確認
        $response->assertSee('?qr=1');
    }

    /**
     * 事業者の教室詳細画面でqr_onlyが無効な場合、QRコードURLにパラメータが付与されないことをテスト
     */
    public function test_qr_code_url_has_no_param_when_qr_only_is_false(): void
    {
        $this->actingAs($this->businessUser);

        $response = $this->get(route('business.classrooms.show', $this->classroom));

        $response->assertStatus(200);
        // QRコードのURLに?qr=1が含まれていないことを確認
        $response->assertDontSee('?qr=1');
    }
}
