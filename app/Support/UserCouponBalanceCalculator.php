<?php

namespace App\Support;

use App\Models\Beneficiary;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;

/**
 * 利用者のクーポン利用可能金額（当会計年度の発行分・当会計年度の利用実績に基づく）
 */
final class UserCouponBalanceCalculator
{
    /**
     * @return array{balance: int, expiry_date: Carbon|null}
     */
    public static function calculate(User $user, ?Carbon $today = null): array
    {
        $beneficiary = Beneficiary::query()->where('user_id', $user->id)->first();
        if (! $beneficiary) {
            return ['balance' => 0, 'expiry_date' => null];
        }

        return self::calculateForBeneficiary($beneficiary, $today);
    }

    /**
     * 指定の受益者に紐づくクーポン・紐づく利用者ユーザーの申込で残高を算出する（管理画面の利用者詳細など）
     *
     * @return array{balance: int, expiry_date: Carbon|null}
     */
    public static function calculateForBeneficiary(Beneficiary $beneficiary, ?Carbon $today = null): array
    {
        $today = $today ?? Carbon::today();

        $fiscalStartYear = FiscalYear::currentStartYear($today);
        $fiscalStart = FiscalYear::startDateFor($fiscalStartYear);
        $fiscalEnd = FiscalYear::endDateFor($fiscalStartYear);

        $validVouchersQuery = Voucher::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->where('expiry_date', '>=', $today)
            ->where('status', '!=', 'expired')
            ->whereBetween('issue_date', [$fiscalStart->toDateString(), $fiscalEnd->toDateString()]);

        $totalVoucherAmount = (clone $validVouchersQuery)->sum('amount');
        $expiryDate = (clone $validVouchersQuery)->orderBy('expiry_date', 'asc')->value('expiry_date');

        $usedAmount = 0;
        if ($beneficiary->user_id) {
            $usedAmount = (int) VoucherUsage::query()
                ->where('user_id', $beneficiary->user_id)
                ->where('is_cancelled', false)
                ->whereBetween('used_at', [$fiscalStart, $fiscalEnd])
                ->sum('amount');
        }

        return [
            'balance' => max(0, $totalVoucherAmount - $usedAmount),
            'expiry_date' => $expiryDate ? Carbon::parse($expiryDate) : null,
        ];
    }
}
