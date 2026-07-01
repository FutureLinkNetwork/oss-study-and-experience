<?php

namespace Tests\Feature\Admin;

use App\Models\Beneficiary;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponUsageControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(Subdomain $subdomain): User
    {
        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);

        return User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_coupon_'.uniqid(),
            'email' => 'admin_coupon_'.uniqid().'@example.com',
        ]);
    }

    private function createUsageData(Subdomain $subdomain): array
    {
        $businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $userRole = Role::create([
            'name' => 'subdomain_user',
            'display_name' => '利用者',
            'is_global' => false,
            'level' => 10,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'biz_'.uniqid(),
            'email' => 'biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'usr_'.uniqid(),
            'email' => 'usr_'.uniqid().'@example.com',
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'user_id' => $consumerUser->id,
            'child_name' => 'テスト児童',
            'status' => 'ログイン認証済み',
        ]);

        Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'VOUCH-'.uniqid(),
            'issue_date' => Carbon::today()->subMonths(2),
            'expiry_date' => Carbon::today()->addMonth(),
            'amount' => 10000,
            'status' => 'unused',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Business',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'b@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'サンプル教室',
            'classroom_name_kana' => 'サンプル',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $course = CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'Test Course',
            'price' => 5000,
            'is_active' => 1,
        ]);

        $usedAt = Carbon::today()->startOfMonth()->subMonth()->day(15)->startOfDay();
        $usage = VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 3000,
            'used_at' => $usedAt,
            'is_cancelled' => false,
        ]);

        return [
            'usage' => $usage,
            'consumer_user' => $consumerUser,
            'business' => $business,
            'classroom' => $classroom,
        ];
    }

    public function test_index_requires_auth(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $response = $this->get('http://www.localhost/admin/coupon-usages');

        $response->assertRedirect();
    }

    public function test_index_displays_for_authenticated_admin(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);

        $response = $this->actingAs($admin)->get('http://www.localhost/admin/coupon-usages');

        $response->assertStatus(200);
        $response->assertSee('クーポンの利用状況管理');
        $response->assertSee('検索条件');
    }

    public function test_index_lists_usages_and_filter_by_child_name(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);
        $data = $this->createUsageData($subdomain);

        $response = $this->actingAs($admin)->get('http://www.localhost/admin/coupon-usages');

        $response->assertStatus(200);
        $response->assertSee('テスト児童');
        $response->assertSee('サンプル教室');
        $response->assertSee('3,000');

        $response = $this->actingAs($admin)->get(route('admin.coupon-usages.index', ['child_name' => 'テスト児童']));
        $response->assertStatus(200);
        $response->assertSee('テスト児童');

        $response = $this->actingAs($admin)->get(route('admin.coupon-usages.index', ['child_name' => '存在しない']));
        $response->assertStatus(200);
        $response->assertDontSee('テスト児童');
    }

    public function test_show_requires_auth(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $data = $this->createUsageData($subdomain);

        $response = $this->get('http://www.localhost/admin/coupon-usages/'.$data['usage']->id);

        $response->assertRedirect();
    }

    public function test_show_displays_and_editable_form_when_in_editable_period(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);
        $data = $this->createUsageData($subdomain);

        $response = $this->actingAs($admin)->get('http://www.localhost/admin/coupon-usages/'.$data['usage']->id);

        $response->assertStatus(200);
        $response->assertSee('利用詳細');
        $response->assertSee('テスト児童');
        $response->assertSee('修正メモ');
        $response->assertSee('保存');
    }

    public function test_show_returns_404_for_other_subdomain(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $otherSubdomain = Subdomain::factory()->create(['subdomain' => 'other']);
        $admin = $this->createAdminUser($subdomain);
        $data = $this->createUsageData($otherSubdomain);

        $response = $this->actingAs($admin)->get('http://www.localhost/admin/coupon-usages/'.$data['usage']->id);

        $response->assertStatus(404);
    }

    public function test_update_saves_and_sets_admin_correction_fields(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);
        $data = $this->createUsageData($subdomain);
        $usage = $data['usage'];
        $editableFrom = Carbon::today()->startOfMonth()->subMonth()->startOfDay();

        $response = $this->actingAs($admin)->put('http://www.localhost/admin/coupon-usages/'.$usage->id, [
            'used_at' => $editableFrom->format('Y-m-d'),
            'amount' => 2000,
            'is_cancelled' => '0',
            'admin_correction_memo' => '金額を修正しました。',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '保存しました。');

        $usage->refresh();
        $this->assertEquals(2000, $usage->amount);
        $this->assertEquals('金額を修正しました。', $usage->admin_correction_memo);
        $this->assertNotNull($usage->admin_corrected_at);
    }

    public function test_update_rejects_when_cancelled(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);
        $data = $this->createUsageData($subdomain);
        $usage = $data['usage'];
        $usage->update(['is_cancelled' => true, 'cancelled_at' => now(), 'cancelled_by_user_id' => $admin->id]);

        $response = $this->actingAs($admin)->put('http://www.localhost/admin/coupon-usages/'.$usage->id, [
            'used_at' => Carbon::today()->startOfMonth()->subMonth()->format('Y-m-d'),
            'amount' => 2000,
            'is_cancelled' => '0',
            'admin_correction_memo' => 'メモ',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'キャンセル済みの利用は編集できません。');
    }

    public function test_update_requires_admin_correction_memo(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);
        $data = $this->createUsageData($subdomain);
        $usage = $data['usage'];
        $editableFrom = Carbon::today()->startOfMonth()->subMonth()->startOfDay();

        $response = $this->actingAs($admin)->put('http://www.localhost/admin/coupon-usages/'.$usage->id, [
            'used_at' => $editableFrom->format('Y-m-d'),
            'amount' => 2000,
            'is_cancelled' => '0',
            'admin_correction_memo' => '',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('admin_correction_memo');
    }

    public function test_export_csv_requires_auth(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $response = $this->get(route('admin.coupon-usages.export-csv'));

        $response->assertRedirect();
    }

    public function test_export_csv_returns_csv_with_headers_and_data(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);
        $this->createUsageData($subdomain);

        $response = $this->actingAs($admin)->get(route('admin.coupon-usages.export-csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type');
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('Shift_JIS', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('coupon_usages_', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(2, count($lines), 'CSV should have header and at least one data row');

        $headerRow = $lines[0];
        $decodedHeader = mb_convert_encoding($headerRow, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('No.', $decodedHeader);
        $this->assertStringContainsString('年度', $decodedHeader);
        $this->assertStringContainsString('申込日時', $decodedHeader);
        $this->assertStringContainsString('ステータス', $decodedHeader);
    }

    public function test_export_csv_respects_search_filters(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);
        $this->createUsageData($subdomain);

        $response = $this->actingAs($admin)->get(route('admin.coupon-usages.export-csv', [
            'child_name' => '存在しない児童',
        ]));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertCount(1, $lines, 'Only header row when no match');
    }

    public function test_export_csv_cancelled_usage_shows_cancel_status(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain);
        $data = $this->createUsageData($subdomain);
        $data['usage']->update(['is_cancelled' => true, 'cancelled_at' => now(), 'cancelled_by_user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('admin.coupon-usages.export-csv'));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $decoded = mb_convert_encoding($content, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('キャンセル', $decoded);
    }
}
