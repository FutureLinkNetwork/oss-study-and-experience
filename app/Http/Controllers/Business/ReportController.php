<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessInfo;
use App\Models\VoucherUsage;
use App\Traits\HandlesAuth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use HandlesAuth;

    /**
     * 会計年度の開始日を取得（指定年の4月1日 00:00:00）
     */
    private function fiscalYearStart(int $year): Carbon
    {
        return Carbon::create($year, 4, 1)->startOfDay();
    }

    /**
     * 会計年度の終了日を取得（指定年の翌年3月31日 23:59:59）
     */
    private function fiscalYearEnd(int $year): Carbon
    {
        return Carbon::create($year + 1, 3, 31)->endOfDay();
    }

    /**
     * 選択可能な会計年度の開始年一覧（当該事業者に申込データがある年度のみ・新しい順）
     */
    private function availableFiscalYears(BusinessInfo $businessInfo, int $subdomainId): array
    {
        $fiscalYearExpr = 'YEAR(used_at) + CASE WHEN MONTH(used_at) >= 4 THEN 0 ELSE -1 END';

        return VoucherUsage::query()
            ->where('business_info_id', $businessInfo->id)
            ->where('subdomain_id', $subdomainId)
            ->where('is_cancelled', false)
            ->selectRaw("({$fiscalYearExpr}) as fiscal_year")
            ->groupByRaw($fiscalYearExpr)
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year')
            ->values()
            ->all();
    }

    /**
     * 現在の会計年度の開始年を返す（4月〜翌3月）
     */
    private function currentFiscalYear(): int
    {
        $today = Carbon::today();

        return $today->month >= 4 ? $today->year : $today->year - 1;
    }

    /**
     * レポート画面を表示
     */
    public function index(Request $request)
    {
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return redirect()->route('business.dashboard')
                ->with('error', '事業者情報が見つかりません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);
        $availableYears = $this->availableFiscalYears($businessInfo, $subdomain->id);

        $selectedYear = $request->input('year');
        if ($selectedYear !== null && $selectedYear !== '') {
            $selectedYear = (int) $selectedYear;
            if (! in_array($selectedYear, $availableYears, true)) {
                $selectedYear = $availableYears[0] ?? null;
            }
        } else {
            // デフォルトは今年度（今年度にデータがなければ直近でデータがある年度）
            $currentFiscal = $this->currentFiscalYear();
            $selectedYear = in_array($currentFiscal, $availableYears, true)
                ? $currentFiscal
                : ($availableYears[0] ?? null);
        }

        $monthlyData = [];
        $classroomData = [];
        $chartMonthLabels = [];
        $chartClassrooms = [];
        $classrooms = $businessInfo->classrooms()->orderBy('classroom_name')->get();

        if ($selectedYear !== null) {
            $start = $this->fiscalYearStart($selectedYear);
            $end = $this->fiscalYearEnd($selectedYear);

            $baseQuery = VoucherUsage::query()
                ->where('business_info_id', $businessInfo->id)
                ->where('subdomain_id', $subdomain->id)
                ->where('is_cancelled', false)
                ->whereBetween('used_at', [$start, $end]);

            // 月別集計（4月〜翌3月の順で12件）- DB非依存のためPHPで集計
            $usagesInRange = (clone $baseQuery)->get(['used_at', 'amount', 'classroom_info_id']);
            $monthlyAggregates = $usagesInRange->groupBy(fn ($row) => $row->used_at->format('Y-n'))
                ->map(fn ($group) => [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount'),
                ]);

            // 教室別・月別集計（グラフ用）
            $byClassroomMonth = $usagesInRange->groupBy(fn ($row) => $row->classroom_info_id.'-'.$row->used_at->format('Y-n'))
                ->map(fn ($group) => [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ]);

            $monthLabels = [
                4 => '4月', 5 => '5月', 6 => '6月', 7 => '7月', 8 => '8月', 9 => '9月',
                10 => '10月', 11 => '11月', 12 => '12月', 1 => '1月', 2 => '2月', 3 => '3月',
            ];
            $monthOrder = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
            $chartMonthLabels = array_map(fn ($m) => $monthLabels[$m], $monthOrder);

            foreach ($monthOrder as $m) {
                $y = $m >= 4 ? $selectedYear : $selectedYear + 1;
                $key = "{$y}-{$m}";
                $agg = $monthlyAggregates->get($key);
                $monthlyData[] = [
                    'label' => $monthLabels[$m],
                    'count' => $agg ? $agg['count'] : 0,
                    'amount' => $agg ? (int) $agg['total_amount'] : 0,
                ];
            }

            foreach ($classrooms as $classroom) {
                $counts = [];
                $amounts = [];
                foreach ($monthOrder as $m) {
                    $y = $m >= 4 ? $selectedYear : $selectedYear + 1;
                    $key = $classroom->id.'-'.$y.'-'.$m;
                    $agg = $byClassroomMonth->get($key);
                    $counts[] = $agg ? $agg['count'] : 0;
                    $amounts[] = $agg ? (int) $agg['amount'] : 0;
                }
                $chartClassrooms[] = [
                    'name' => $classroom->classroom_name,
                    'counts' => $counts,
                    'amounts' => $amounts,
                ];
            }

            // 教室別集計（表用）
            $classroomAggregates = (clone $baseQuery)
                ->select(
                    'classroom_info_id',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('COALESCE(SUM(amount), 0) as total_amount')
                )
                ->groupBy('classroom_info_id')
                ->get()
                ->keyBy('classroom_info_id');

            foreach ($classrooms as $classroom) {
                $agg = $classroomAggregates->get($classroom->id);
                $classroomData[] = [
                    'id' => $classroom->id,
                    'name' => $classroom->classroom_name,
                    'count' => $agg ? (int) $agg->count : 0,
                    'amount' => $agg ? (int) $agg->total_amount : 0,
                ];
            }
        }

        return view('business.reports.index', compact(
            'subdomain',
            'businessInfo',
            'availableYears',
            'selectedYear',
            'monthlyData',
            'classroomData',
            'classrooms',
            'chartMonthLabels',
            'chartClassrooms'
        ));
    }

    private function getUserBusinessInfo(): ?BusinessInfo
    {
        return BusinessInfo::where('user_id', Auth::id())
            // ->where('is_active', true)
            ->first();
    }
}
