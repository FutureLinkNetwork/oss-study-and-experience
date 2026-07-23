<?php

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use App\Models\Voucher;
use App\Services\VoucherIssueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class VoucherIssueServiceTest extends TestCase
{
    use RefreshDatabase;

    private VoucherIssueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VoucherIssueService;
    }

    public function test_calculate_expiry_date_uses_fiscal_year_end_when_expiry_is_zero(): void
    {
        $subdomain = Subdomain::factory()->make([
            'voucher_expiry' => 0,
        ]);

        $aprilIssue = Carbon::create(2026, 4, 15);
        $this->assertSame(
            '2027-03-31',
            $this->service->calculateExpiryDate($subdomain, $aprilIssue)->toDateString()
        );

        $januaryIssue = Carbon::create(2027, 1, 10);
        $this->assertSame(
            '2027-03-31',
            $this->service->calculateExpiryDate($subdomain, $januaryIssue)->toDateString()
        );
    }

    public function test_calculate_expiry_date_adds_months_when_expiry_is_positive(): void
    {
        $subdomain = Subdomain::factory()->make([
            'voucher_expiry' => 3,
        ]);

        $issueDate = Carbon::create(2026, 7, 22);
        $this->assertSame(
            '2026-10-22',
            $this->service->calculateExpiryDate($subdomain, $issueDate)->toDateString()
        );
    }

    public function test_issue_for_beneficiary_creates_unused_voucher(): void
    {
        $subdomain = Subdomain::factory()->create([
            'voucher_amount' => 10000,
            'voucher_expiry' => 6,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        $voucher = $this->service->issueForBeneficiary(
            $beneficiary,
            $subdomain,
            Carbon::create(2026, 7, 22)
        );

        $this->assertInstanceOf(Voucher::class, $voucher);
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'amount' => 10000,
            'status' => 'unused',
            'issue_date' => '2026-07-22',
            'expiry_date' => '2027-01-22',
        ]);
    }

    public function test_issue_for_beneficiary_throws_when_settings_missing(): void
    {
        $subdomain = Subdomain::factory()->create([
            'voucher_amount' => null,
            'voucher_expiry' => null,
        ]);
        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->issueForBeneficiary($beneficiary, $subdomain);
    }
}
