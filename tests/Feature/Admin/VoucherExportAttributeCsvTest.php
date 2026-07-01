<?php

namespace Tests\Feature\Admin;

use App\Models\Beneficiary;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherExportAttributeCsvTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 50,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'voucher_attr_export_admin',
            'is_active' => true,
        ]);
    }

    public function test_export_attribute_csv_requires_auth(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $response = $this->get('http://test.localhost/admin/vouchers/export-attribute-csv');

        $response->assertRedirect();
    }

    public function test_export_attribute_csv_returns_csv_with_headers_and_data(): void
    {
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->adminUser->id,
            'child_name' => '属性CSV児童',
            'elementary_school_name' => 'テスト小学校',
            'grade' => '3年生',
            'labels' => 'DV避難等',
        ]);

        Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $this->subdomain->id,
            'voucher_number' => 'VOUCH-ATTR-001',
            'issue_date' => Carbon::create(2025, 5, 15),
            'expiry_date' => Carbon::create(2026, 3, 31),
            'amount' => 8000,
            'status' => 'unused',
        ]);

        $response = $this->actingAs($this->adminUser)->get('http://test.localhost/admin/vouchers/export-attribute-csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type');
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('Shift_JIS', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('vouchers_attribute_', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(2, count($lines), 'CSV should have header and at least one data row');

        $headerRow = $lines[0];
        $decodedHeader = mb_convert_encoding($headerRow, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('No.', $decodedHeader);
        $this->assertStringContainsString('年度', $decodedHeader);
        $this->assertStringContainsString('学校名', $decodedHeader);
        $this->assertStringContainsString('学年', $decodedHeader);
        $this->assertStringContainsString('利用者ラベル', $decodedHeader);
        $this->assertStringContainsString('金額', $decodedHeader);
        $this->assertStringContainsString('抽出日', $decodedHeader);

        $decodedContent = mb_convert_encoding($content, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('テスト小学校', $decodedContent);
        $this->assertStringContainsString('3年生', $decodedContent);
        $this->assertStringContainsString('DV避難等', $decodedContent);
        $this->assertStringContainsString('2025', $decodedContent);
        $this->assertStringContainsString('8000', $decodedContent);
    }

    public function test_export_attribute_csv_respects_search_filters(): void
    {
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->adminUser->id,
            'child_name' => 'フィルタ児童',
        ]);

        Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $this->subdomain->id,
            'voucher_number' => 'VOUCH-FILTER',
            'issue_date' => Carbon::today(),
            'expiry_date' => Carbon::today()->addYear(),
            'amount' => 8000,
            'status' => 'unused',
        ]);

        $response = $this->actingAs($this->adminUser)->get(
            'http://test.localhost/admin/vouchers/export-attribute-csv?child_name='.urlencode('存在しない児童名')
        );

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertCount(1, $lines, 'Only header row when no match');
    }

    public function test_export_attribute_csv_shows_hyphen_when_labels_empty(): void
    {
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->adminUser->id,
            'child_name' => 'ラベルなし児童',
            'labels' => null,
        ]);

        Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $this->subdomain->id,
            'voucher_number' => 'VOUCH-NOLABEL',
            'issue_date' => Carbon::today(),
            'expiry_date' => Carbon::today()->addYear(),
            'amount' => 5000,
            'status' => 'unused',
        ]);

        $response = $this->actingAs($this->adminUser)->get('http://test.localhost/admin/vouchers/export-attribute-csv');

        $response->assertStatus(200);
        $decoded = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('-', $decoded);
        $this->assertStringContainsString('5000', $decoded);
    }

    public function test_export_attribute_csv_requires_level_40_or_above(): void
    {
        $lowLevelRole = Role::factory()->create([
            'name' => 'low_admin',
            'level' => 30,
            'is_active' => true,
        ]);
        $lowUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $lowLevelRole->id,
            'login_id' => 'low_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lowUser)->get('http://test.localhost/admin/vouchers/export-attribute-csv');

        $response->assertStatus(403);
    }
}
