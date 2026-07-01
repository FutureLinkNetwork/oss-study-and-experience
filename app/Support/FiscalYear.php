<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * 会計年度（4月1日〜翌年3月31日）の境界を扱う
 */
final class FiscalYear
{
    /**
     * 指定日が属する会計年度の「開始年」（4月始まりの年）を返す
     */
    public static function currentStartYear(Carbon $today): int
    {
        return $today->month >= 4 ? $today->year : $today->year - 1;
    }

    /**
     * 会計年度開始日（開始年の4月1日 00:00:00）
     */
    public static function startDateFor(int $fiscalYearStartYear): Carbon
    {
        return Carbon::create($fiscalYearStartYear, 4, 1)->startOfDay();
    }

    /**
     * 会計年度終了日（開始年の翌年3月31日 23:59:59）
     */
    public static function endDateFor(int $fiscalYearStartYear): Carbon
    {
        return Carbon::create($fiscalYearStartYear + 1, 3, 31)->endOfDay();
    }
}
