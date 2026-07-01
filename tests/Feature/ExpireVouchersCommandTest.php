<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpireVouchersCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 有効期限が過ぎたunusedクーポンがexpiredに更新される
     */
    public function test_expires_unused_voucher_with_past_expiry_date(): void
    {
        $today = Carbon::today();
        $subdomain = Subdomain::factory()->create();
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        $voucher = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-001',
            'issue_date' => $today->copy()->subMonth(),
            'expiry_date' => $today->copy()->subDay(),
            'amount' => 10000,
            'status' => 'unused',
        ]);

        $this->artisan('app:expire-vouchers')
            ->assertSuccessful();

        $voucher->refresh();
        $this->assertEquals('expired', $voucher->status);
    }

    /**
     * 有効期限が過ぎたusedクーポンがexpiredに更新される
     */
    public function test_expires_used_voucher_with_past_expiry_date(): void
    {
        $today = Carbon::today();
        $subdomain = Subdomain::factory()->create();
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        $voucher = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-002',
            'issue_date' => $today->copy()->subMonth(),
            'expiry_date' => $today->copy()->subDay(),
            'amount' => 10000,
            'status' => 'used',
        ]);

        $this->artisan('app:expire-vouchers')
            ->assertSuccessful();

        $voucher->refresh();
        $this->assertEquals('expired', $voucher->status);
    }

    /**
     * 有効期限が今日のクーポンは更新されない
     */
    public function test_does_not_expire_voucher_with_today_expiry_date(): void
    {
        $today = Carbon::today();
        $subdomain = Subdomain::factory()->create();
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        $voucher = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-003',
            'issue_date' => $today->copy()->subMonth(),
            'expiry_date' => $today,
            'amount' => 10000,
            'status' => 'unused',
        ]);

        $this->artisan('app:expire-vouchers')
            ->assertSuccessful();

        $voucher->refresh();
        $this->assertEquals('unused', $voucher->status);
    }

    /**
     * 既にexpiredのクーポンは更新されない
     */
    public function test_does_not_update_already_expired_voucher(): void
    {
        $today = Carbon::today();
        $subdomain = Subdomain::factory()->create();
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        $voucher = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-004',
            'issue_date' => $today->copy()->subMonth(),
            'expiry_date' => $today->copy()->subDay(),
            'amount' => 10000,
            'status' => 'expired',
        ]);

        $this->artisan('app:expire-vouchers')
            ->assertSuccessful();

        $voucher->refresh();
        $this->assertEquals('expired', $voucher->status);
    }

    /**
     * 有効期限が未来のクーポンは更新されない
     */
    public function test_does_not_expire_voucher_with_future_expiry_date(): void
    {
        $today = Carbon::today();
        $subdomain = Subdomain::factory()->create();
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        $voucher = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-005',
            'issue_date' => $today,
            'expiry_date' => $today->copy()->addDay(),
            'amount' => 10000,
            'status' => 'unused',
        ]);

        $this->artisan('app:expire-vouchers')
            ->assertSuccessful();

        $voucher->refresh();
        $this->assertEquals('unused', $voucher->status);
    }

    /**
     * 対象がない場合のメッセージ確認
     */
    public function test_shows_message_when_no_vouchers_to_expire(): void
    {
        $this->artisan('app:expire-vouchers')
            ->expectsOutput('更新対象のレコードはありません。')
            ->assertSuccessful();
    }

    /**
     * 複数のクーポンが正しく処理される
     */
    public function test_expires_multiple_vouchers_correctly(): void
    {
        $today = Carbon::today();
        $subdomain = Subdomain::factory()->create();
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        // 有効期限が過ぎたunusedクーポン
        $voucher1 = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-006',
            'issue_date' => $today->copy()->subMonth(),
            'expiry_date' => $today->copy()->subDay(),
            'amount' => 10000,
            'status' => 'unused',
        ]);

        // 有効期限が過ぎたusedクーポン
        $voucher2 = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-007',
            'issue_date' => $today->copy()->subMonth(),
            'expiry_date' => $today->copy()->subDays(2),
            'amount' => 5000,
            'status' => 'used',
        ]);

        // 有効期限が未来のクーポン（更新されない）
        $voucher3 = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-008',
            'issue_date' => $today,
            'expiry_date' => $today->copy()->addDay(),
            'amount' => 8000,
            'status' => 'unused',
        ]);

        // 既にexpiredのクーポン（更新されない）
        $voucher4 = Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'TEST-009',
            'issue_date' => $today->copy()->subMonth(),
            'expiry_date' => $today->copy()->subDay(),
            'amount' => 3000,
            'status' => 'expired',
        ]);

        $this->artisan('app:expire-vouchers')
            ->assertSuccessful();

        $voucher1->refresh();
        $voucher2->refresh();
        $voucher3->refresh();
        $voucher4->refresh();

        $this->assertEquals('expired', $voucher1->status);
        $this->assertEquals('expired', $voucher2->status);
        $this->assertEquals('unused', $voucher3->status);
        $this->assertEquals('expired', $voucher4->status);
    }
}
