<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class VoucherIssueService
{
    /**
     * サブドメイン設定に基づき受給者へクーポンを1件発行する。
     *
     * @throws InvalidArgumentException クーポン金額・有効期限が未設定の場合
     */
    public function issueForBeneficiary(Beneficiary $beneficiary, Subdomain $subdomain, ?Carbon $issueDate = null): Voucher
    {
        if ($subdomain->voucher_amount === null || $subdomain->voucher_expiry === null) {
            throw new InvalidArgumentException('サブドメインのクーポン設定が完了していません。');
        }

        $today = $issueDate?->copy()->startOfDay() ?? Carbon::today();
        $expiryDate = $this->calculateExpiryDate($subdomain, $today);

        return Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => Str::uuid()->toString(),
            'issue_date' => $today,
            'expiry_date' => $expiryDate,
            'amount' => $subdomain->voucher_amount,
            'status' => 'unused',
        ]);
    }

    /**
     * サブドメイン設定から有効期限を算出する。
     * voucher_expiry が 0 の場合は会計年度末（3月31日）、それ以外は発行日から N ヶ月後。
     */
    public function calculateExpiryDate(Subdomain $subdomain, Carbon $issueDate): Carbon
    {
        if ($subdomain->voucher_expiry === 0) {
            if ($issueDate->month >= 4) {
                return Carbon::create($issueDate->year + 1, 3, 31);
            }

            return Carbon::create($issueDate->year, 3, 31);
        }

        return $issueDate->copy()->addMonths($subdomain->voucher_expiry);
    }
}
