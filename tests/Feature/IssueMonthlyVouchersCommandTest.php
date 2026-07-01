<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IssueMonthlyVouchersCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系：条件に一致する利用者にクーポンが発行される
     */
    public function test_issues_vouchers_to_qualified_beneficiaries(): void
    {
        $today = Carbon::today();
        $dayOfMonth = (int) $today->format('d');

        // サブドメインを作成（voucher_publish_dateが今日の日付と一致）
        $subdomain = Subdomain::factory()->create([
            'voucher_amount' => 10000,
            'voucher_expiry' => 1,
            'voucher_publish_date' => $dayOfMonth,
        ]);

        // 資格保持の利用者を作成
        $beneficiary1 = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'is_disqualified' => false,
        ]);

        $beneficiary2 = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'is_disqualified' => false,
        ]);

        // バッチコマンドを実行
        $this->artisan('app:issue-monthly-vouchers')
            ->assertSuccessful();

        // クーポンが2件発行されていることを確認
        $this->assertDatabaseCount('vouchers', 2);

        // 各利用者にクーポンが発行されていることを確認
        $voucher1 = Voucher::where('beneficiary_id', $beneficiary1->id)->first();
        $voucher2 = Voucher::where('beneficiary_id', $beneficiary2->id)->first();

        $this->assertNotNull($voucher1);
        $this->assertNotNull($voucher2);
        $this->assertEquals($subdomain->id, $voucher1->subdomain_id);
        $this->assertEquals($subdomain->id, $voucher2->subdomain_id);
        $this->assertEquals($subdomain->voucher_amount, $voucher1->amount);
        $this->assertEquals($subdomain->voucher_amount, $voucher2->amount);
        $this->assertEquals($today->format('Y-m-d'), $voucher1->issue_date->format('Y-m-d'));
        $this->assertEquals($today->format('Y-m-d'), $voucher2->issue_date->format('Y-m-d'));
        $this->assertEquals($today->copy()->addMonths($subdomain->voucher_expiry)->format('Y-m-d'), $voucher1->expiry_date->format('Y-m-d'));
        $this->assertEquals('unused', $voucher1->status);
        $this->assertNotEmpty($voucher1->voucher_number);
    }

    /**
     * スキップ：voucher_publish_dateが一致しない場合は発行されない
     */
    public function test_skips_subdomain_when_publish_date_does_not_match(): void
    {
        $today = Carbon::today();
        $dayOfMonth = (int) $today->format('d');
        $differentDay = $dayOfMonth === 1 ? 2 : 1; // 異なる日付

        // サブドメインを作成（voucher_publish_dateが今日の日付と一致しない）
        $subdomain = Subdomain::factory()->create([
            'voucher_amount' => 10000,
            'voucher_expiry' => 1,
            'voucher_publish_date' => $differentDay,
        ]);

        // 資格保持の利用者を作成
        Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'is_disqualified' => false,
        ]);

        // バッチコマンドを実行
        $this->artisan('app:issue-monthly-vouchers')
            ->assertSuccessful();

        // クーポンが発行されていないことを確認
        $this->assertDatabaseCount('vouchers', 0);
    }

    /**
     * スキップ：設定がNULLのサブドメインはスキップされる
     */
    public function test_skips_subdomain_when_settings_are_null(): void
    {
        $today = Carbon::today();
        $dayOfMonth = (int) $today->format('d');

        // 設定がNULLのサブドメインを作成
        $subdomain1 = Subdomain::factory()->create([
            'voucher_amount' => null,
            'voucher_expiry' => 1,
            'voucher_publish_date' => $dayOfMonth,
        ]);

        $subdomain2 = Subdomain::factory()->create([
            'voucher_amount' => 10000,
            'voucher_expiry' => null,
            'voucher_publish_date' => $dayOfMonth,
        ]);

        // 資格保持の利用者を作成
        Beneficiary::factory()->create([
            'subdomain_id' => $subdomain1->id,
            'is_disqualified' => false,
        ]);

        Beneficiary::factory()->create([
            'subdomain_id' => $subdomain2->id,
            'is_disqualified' => false,
        ]);

        // バッチコマンドを実行
        $this->artisan('app:issue-monthly-vouchers')
            ->assertSuccessful();

        // クーポンが発行されていないことを確認
        $this->assertDatabaseCount('vouchers', 0);
    }

    /**
     * スキップ：is_disqualified=1の利用者はスキップされる
     */
    public function test_skips_disqualified_beneficiaries(): void
    {
        $today = Carbon::today();
        $dayOfMonth = (int) $today->format('d');

        // サブドメインを作成
        $subdomain = Subdomain::factory()->create([
            'voucher_amount' => 10000,
            'voucher_expiry' => 1,
            'voucher_publish_date' => $dayOfMonth,
        ]);

        // 資格保持の利用者
        $qualifiedBeneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'is_disqualified' => false,
        ]);

        // 資格喪失の利用者
        $disqualifiedBeneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'is_disqualified' => true,
        ]);

        // バッチコマンドを実行
        $this->artisan('app:issue-monthly-vouchers')
            ->assertSuccessful();

        // 資格保持の利用者にのみクーポンが発行されていることを確認
        $this->assertDatabaseCount('vouchers', 1);
        $voucher = Voucher::where('beneficiary_id', $qualifiedBeneficiary->id)->first();
        $this->assertNotNull($voucher);
        $this->assertNull(Voucher::where('beneficiary_id', $disqualifiedBeneficiary->id)->first());
    }

    /**
     * 既存クーポン：既存のクーポンがあっても新規発行される
     */
    public function test_issues_new_voucher_even_when_existing_voucher_exists(): void
    {
        $today = Carbon::today();
        $dayOfMonth = (int) $today->format('d');

        // サブドメインを作成
        $subdomain = Subdomain::factory()->create([
            'voucher_amount' => 10000,
            'voucher_expiry' => 1,
            'voucher_publish_date' => $dayOfMonth,
        ]);

        // 資格保持の利用者を作成
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'is_disqualified' => false,
        ]);

        // 既存のクーポンを作成
        Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'existing-voucher-001',
            'issue_date' => Carbon::today()->subMonth(),
            'expiry_date' => Carbon::today()->addMonth(),
            'amount' => 5000,
            'status' => 'unused',
        ]);

        // バッチコマンドを実行
        $this->artisan('app:issue-monthly-vouchers')
            ->assertSuccessful();

        // 既存のクーポンに加えて、新しいクーポンが発行されていることを確認
        $this->assertDatabaseCount('vouchers', 2);
        $vouchers = Voucher::where('beneficiary_id', $beneficiary->id)->get();
        $this->assertCount(2, $vouchers);
    }
}
