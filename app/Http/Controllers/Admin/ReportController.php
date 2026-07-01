<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseInfo;
use App\Models\UserApplication;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * 管理画面レポート（過去12暦月の月次指標）を表示
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user->hasLevelOrAbove(40)) {
            abort(403, 'この機能にアクセスする権限がありません。');
        }

        $subdomainId = $user->subdomain_id;
        if (! $subdomainId) {
            abort(404, 'サブドメインが見つかりません。');
        }

        $subdomain = $user->subdomain;

        // 直近12暦月（例: 今日が2026/3/10 → 2025/4 〜 2026/3）
        $months = $this->buildLast12CalendarMonths();
        $monthLabels = array_column($months, 'label');
        $monthStarts = array_column($months, 'start');
        $monthEnds = array_column($months, 'end');

        $firstMonthStart = $monthStarts[0];
        $lastMonthEnd = $monthEnds[11];

        // クーポン発行: 月別（発行利用者数・発行金額）・累計（残高用）
        $vouchersInRange = Voucher::query()
            ->where('subdomain_id', $subdomainId)
            ->whereBetween('issue_date', [$firstMonthStart->format('Y-m-d'), $lastMonthEnd->format('Y-m-d')])
            ->get(['id', 'beneficiary_id', 'issue_date', 'amount']);

        $issuedUserCounts = $this->uniqueCountByMonth($vouchersInRange, 'issue_date', 'beneficiary_id');
        $issuedAmounts = $this->sumByMonth($vouchersInRange, 'issue_date', 'amount');

        // 累計発行額（各月末時点）
        $cumulativeIssued = $this->cumulativeAmountByMonthEnd(
            $subdomainId,
            'vouchers',
            'issue_date',
            'amount',
            $monthEnds
        );

        // クーポン利用: 月別（利用者数・利用金額）・累計（残高用）
        $usagesInRange = VoucherUsage::query()
            ->where('subdomain_id', $subdomainId)
            ->where('is_cancelled', false)
            ->whereBetween('used_at', [$firstMonthStart->startOfDay(), $lastMonthEnd->endOfDay()])
            ->get(['id', 'user_id', 'used_at', 'amount']);

        $usedUserCounts = $this->uniqueCountByMonth($usagesInRange, 'used_at', 'user_id');
        $usedAmounts = $this->sumByMonth($usagesInRange, 'used_at', 'amount');

        $cumulativeUsed = $this->cumulativeUsedAmountByMonthEnd($subdomainId, $monthEnds);

        // 月次クーポン利用者申請割合（案B）: その月発行ユニーク利用者数のうち、その月に利用したユニーク利用者数の割合（%）
        $applicationRates = [];
        foreach ($monthLabels as $i => $_) {
            $issued = $issuedUserCounts[$i] ?? 0;
            $used = $usedUserCounts[$i] ?? 0;
            $applicationRates[] = $issued > 0 ? round($used / $issued * 100, 1) : 0;
        }

        // 月次クーポン残高（その月までに発行 − その月までに利用）
        $balances = [];
        foreach ($cumulativeIssued as $i => $issued) {
            $used = $cumulativeUsed[$i] ?? 0;
            $balances[] = $issued - $used;
        }

        // 月次クーポン1人あたり平均残高（残高 / その月に発行されたユニーク利用者数）
        $avgBalancePerUser = [];
        foreach ($monthLabels as $i => $_) {
            $denom = $issuedUserCounts[$i] ?? 0;
            $bal = $balances[$i] ?? 0;
            $avgBalancePerUser[] = $denom > 0 ? (int) floor($bal / $denom) : 0;
        }

        // 月末時点の事業者数・教室数・コース数（案A）
        $businessCounts = $this->countAtMonthEnd(BusinessInfo::query()->where('subdomain_id', $subdomainId), $monthEnds);
        $classroomCounts = $this->countClassroomsAtMonthEnd($subdomainId, $monthEnds);
        $courseCounts = $this->countCoursesAtMonthEnd($subdomainId, $monthEnds);

        // 月次人気教室トップ20バンプチャート用データ（案A: 過去12ヶ月合計利用者数上位20教室の月別順位）
        $bumpChart = $this->buildBumpChartData($subdomainId, $monthStarts, $monthEnds, $monthLabels);

        // 登録事業者の種別分布（月末時点の有効事業者を種別ごとに集計、12ヶ月分の積み上げ横棒用）
        $applicantTypeChart = $this->buildApplicantTypeChartData($subdomainId, $monthEnds, $monthLabels);

        // クーポン利用の習い事の種別分布（子カテゴリ上位20+その他、利用件数・12ヶ月積み上げ）
        $usageByLessonCategoryChart = $this->buildUsageByLessonCategoryChartData($subdomainId, $monthStarts, $monthEnds, $monthLabels);

        // 申請・審査の推移（利用申請数・利用者審査通過数・事業者申請数・事業者審査通過数）
        $applicationApprovalChart = $this->buildApplicationAndApprovalChartData($subdomainId, $monthStarts, $monthEnds, $monthLabels);

        $chartData = [
            'monthLabels' => $monthLabels,
            'issuedUserCounts' => $issuedUserCounts,
            'usedUserCounts' => $usedUserCounts,
            'applicationRates' => $applicationRates,
            'issuedAmounts' => $issuedAmounts,
            'usedAmounts' => $usedAmounts,
            'balances' => $balances,
            'avgBalancePerUser' => $avgBalancePerUser,
            'businessCounts' => $businessCounts,
            'classroomCounts' => $classroomCounts,
            'courseCounts' => $courseCounts,
            'bumpChart' => $bumpChart,
            'applicantTypeChart' => $applicantTypeChart,
            'usageByLessonCategoryChart' => $usageByLessonCategoryChart,
            'applicationApprovalChart' => $applicationApprovalChart,
        ];

        $couponDescriptions = $this->getCouponReportDescriptions();
        $entityDescriptions = $this->getEntityReportDescriptions();
        $couponColors = $this->getCouponChartColors();
        $entityColors = $this->getEntityChartColors();
        $bumpChartDescription = $this->getBumpChartDescription();
        $applicantTypeChartDescription = $this->getApplicantTypeChartDescription();
        $usageByLessonCategoryChartDescription = $this->getUsageByLessonCategoryChartDescription();
        $applicationApprovalChartDescription = $this->getApplicationApprovalChartDescription();

        return view('admin.reports.index', [
            'subdomain' => $subdomain,
            'chartData' => $chartData,
            'couponDescriptions' => $couponDescriptions,
            'entityDescriptions' => $entityDescriptions,
            'couponColors' => $couponColors,
            'entityColors' => $entityColors,
            'bumpChartDescription' => $bumpChartDescription,
            'applicantTypeChartDescription' => $applicantTypeChartDescription,
            'usageByLessonCategoryChartDescription' => $usageByLessonCategoryChartDescription,
            'applicationApprovalChartDescription' => $applicationApprovalChartDescription,
        ]);
    }

    /**
     * 直近12暦月の開始・終了・ラベルを返す
     *
     * @return array<int, array{label: string, start: Carbon, end: Carbon}>
     */
    private function buildLast12CalendarMonths(): array
    {
        $result = [];
        $start = Carbon::today()->subMonths(11)->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $result[] = [
                'label' => $month->format('Y/n'),
                'start' => $month->copy()->startOfMonth(),
                'end' => $month->copy()->endOfMonth()->endOfDay(),
            ];
        }

        return $result;
    }

    /**
     * コレクションを月別にグループ化し、指定キーでユニークカウントした配列（12要素）を返す
     *
     * @param  \Illuminate\Support\Collection  $collection  dateAttribute と countKey を持つモデルコレクション
     * @return array<int, int>
     */
    private function uniqueCountByMonth($collection, string $dateAttribute, string $countKey): array
    {
        $months = $this->buildLast12CalendarMonths();
        $out = [];
        foreach ($months as $i => $m) {
            $start = $m['start']->format('Y-m-d');
            $end = $m['end']->format('Y-m-d');
            $ids = $collection
                ->filter(function ($row) use ($dateAttribute, $start, $end) {
                    $d = $row->{$dateAttribute};
                    if ($d instanceof \DateTimeInterface) {
                        $d = $d->format('Y-m-d');
                    }

                    return $d >= $start && $d <= $end;
                })
                ->pluck($countKey)
                ->unique()
                ->values();
            $out[] = $ids->count();
        }

        return $out;
    }

    /**
     * コレクションを月別に合計した配列（12要素）を返す
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @return array<int, int>
     */
    private function sumByMonth($collection, string $dateAttribute, string $sumKey): array
    {
        $months = $this->buildLast12CalendarMonths();
        $out = [];
        foreach ($months as $m) {
            $start = $m['start']->format('Y-m-d');
            $end = $m['end']->format('Y-m-d');
            $sum = $collection
                ->filter(function ($row) use ($dateAttribute, $start, $end) {
                    $d = $row->{$dateAttribute};
                    if ($d instanceof \DateTimeInterface) {
                        $d = $d->format('Y-m-d');
                    }

                    return $d >= $start && $d <= $end;
                })
                ->sum($sumKey);
            $out[] = (int) $sum;
        }

        return $out;
    }

    /**
     * コレクションを月別に件数カウントした配列（12要素）を返す
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @return array<int, int>
     */
    private function countByMonth($collection, string $dateAttribute): array
    {
        $months = $this->buildLast12CalendarMonths();
        $out = [];
        foreach ($months as $m) {
            $start = $m['start']->format('Y-m-d');
            $end = $m['end']->format('Y-m-d');
            $count = $collection
                ->filter(function ($row) use ($dateAttribute, $start, $end) {
                    $d = $row->{$dateAttribute};
                    if ($d instanceof \DateTimeInterface) {
                        $d = $d->format('Y-m-d');
                    }

                    return $d >= $start && $d <= $end;
                })
                ->count();
            $out[] = $count;
        }

        return $out;
    }

    /**
     * 日付属性で月別にグループ化（内部用）
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function groupByMonth($collection, string $dateAttribute): array
    {
        $groups = [];
        foreach ($collection as $row) {
            $d = $row->{$dateAttribute};
            if ($d instanceof \DateTimeInterface) {
                $key = $d->format('Y-n');
            } else {
                $key = Carbon::parse($d)->format('Y-n');
            }
            if (! isset($groups[$key])) {
                $groups[$key] = collect();
            }
            $groups[$key]->push($row);
        }

        return $groups;
    }

    /**
     * 各月末時点での累計発行額（vouchers の issue_date <= 月末、amount 合計）
     *
     * @param  array<int, Carbon>  $monthEnds
     * @return array<int, int>
     */
    private function cumulativeAmountByMonthEnd(int $subdomainId, string $table, string $dateCol, string $amountCol, array $monthEnds): array
    {
        $out = [];
        foreach ($monthEnds as $end) {
            $sum = DB::table($table)
                ->where('subdomain_id', $subdomainId)
                ->where($dateCol, '<=', $end->format('Y-m-d'))
                ->sum($amountCol);
            $out[] = (int) $sum;
        }

        return $out;
    }

    /**
     * 各月末時点での累計利用額（voucher_usages、used_at <= 月末、is_cancelled = false）
     *
     * @param  array<int, Carbon>  $monthEnds
     * @return array<int, int>
     */
    private function cumulativeUsedAmountByMonthEnd(int $subdomainId, array $monthEnds): array
    {
        $out = [];
        foreach ($monthEnds as $end) {
            $sum = VoucherUsage::query()
                ->where('subdomain_id', $subdomainId)
                ->where('is_cancelled', false)
                ->where('used_at', '<=', $end->endOfDay())
                ->sum('amount');
            $out[] = (int) $sum;
        }

        return $out;
    }

    /**
     * 各月末時点で created_at <= 月末 かつ is_active = 1 の件数（クエリビルダを渡す）
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<int, Carbon>  $monthEnds
     * @return array<int, int>
     */
    private function countAtMonthEnd($query, array $monthEnds): array
    {
        $out = [];
        foreach ($monthEnds as $end) {
            $out[] = (clone $query)->where('is_active', true)->where('created_at', '<=', $end)->count();
        }

        return $out;
    }

    /**
     * 各月末時点で有効な教室数（サブドメインに紐づく事業者配下、created_at <= 月末、is_active = 1）
     *
     * @param  array<int, Carbon>  $monthEnds
     * @return array<int, int>
     */
    private function countClassroomsAtMonthEnd(int $subdomainId, array $monthEnds): array
    {
        $out = [];
        foreach ($monthEnds as $end) {
            $out[] = ClassroomInfo::query()
                ->whereHas('businessInfo', fn ($q) => $q->where('subdomain_id', $subdomainId)->where('is_active', true))
                ->where('is_active', true)
                ->where('created_at', '<=', $end)
                ->count();
        }

        return $out;
    }

    /**
     * 各月末時点で有効なコース数（サブドメインに紐づく事業者配下、created_at <= 月末、is_active = 1）
     *
     * @param  array<int, Carbon>  $monthEnds
     * @return array<int, int>
     */
    private function countCoursesAtMonthEnd(int $subdomainId, array $monthEnds): array
    {
        $out = [];
        foreach ($monthEnds as $end) {
            $out[] = CourseInfo::query()
                ->whereHas('businessInfo', fn ($q) => $q->where('subdomain_id', $subdomainId)->where('is_active', true))
                ->where('is_active', true)
                ->where('created_at', '<=', $end)
                ->count();
        }

        return $out;
    }

    /**
     * 月次人気教室トップ20バンプチャート用データを構築（案A: 過去12ヶ月合計利用者数上位20教室の月別順位）
     *
     * @param  array<int, Carbon>  $monthStarts
     * @param  array<int, Carbon>  $monthEnds
     * @param  array<int, string>  $monthLabels
     * @return array{monthLabels: array<int, string>, classrooms: array<int, array{id: int, name: string, ranks: array<int, int|null>}>}
     */
    private function buildBumpChartData(int $subdomainId, array $monthStarts, array $monthEnds, array $monthLabels): array
    {
        $firstStart = $monthStarts[0]->copy()->startOfDay();
        $lastEnd = $monthEnds[11]->copy()->endOfDay();

        $usages = VoucherUsage::query()
            ->where('subdomain_id', $subdomainId)
            ->where('is_cancelled', false)
            ->whereBetween('used_at', [$firstStart, $lastEnd])
            ->get(['classroom_info_id', 'user_id', 'used_at']);

        $perMonthPerClassroom = [];
        foreach ($usages as $row) {
            $usedAt = $row->used_at;
            $monthIndex = null;
            for ($i = 0; $i < 12; $i++) {
                $start = $monthStarts[$i]->copy()->startOfDay();
                $end = $monthEnds[$i]->copy()->endOfDay();
                if ($usedAt->between($start, $end)) {
                    $monthIndex = $i;
                    break;
                }
            }
            if ($monthIndex === null) {
                continue;
            }
            $cid = $row->classroom_info_id;
            if (! isset($perMonthPerClassroom[$cid])) {
                $perMonthPerClassroom[$cid] = array_fill(0, 12, []);
            }
            $perMonthPerClassroom[$cid][$monthIndex][$row->user_id] = true;
        }

        $monthlyUserCounts = [];
        foreach ($perMonthPerClassroom as $cid => $perMonth) {
            $monthlyUserCounts[$cid] = array_map('count', $perMonth);
        }
        $totalByClassroom = [];
        foreach ($monthlyUserCounts as $cid => $counts) {
            $totalByClassroom[$cid] = array_sum($counts);
        }
        arsort($totalByClassroom, SORT_NUMERIC);
        $top20Ids = array_values(array_slice(array_keys($totalByClassroom), 0, 20, true));

        if ($top20Ids === []) {
            return [
                'monthLabels' => $monthLabels,
                'classrooms' => [],
            ];
        }

        $classroomModels = ClassroomInfo::query()
            ->whereIn('id', $top20Ids)
            ->get(['id', 'classroom_name'])
            ->keyBy('id');

        $classroomsOrdered = [];
        for ($m = 0; $m < 12; $m++) {
            $ranksInMonth = $this->rankClassroomsInMonth($top20Ids, $monthlyUserCounts, $m);
            foreach ($top20Ids as $idx => $cid) {
                if (! isset($classroomsOrdered[$idx])) {
                    $classroomsOrdered[$idx] = [
                        'id' => $cid,
                        'name' => $classroomModels->get($cid)->classroom_name ?? "教室#{$cid}",
                        'ranks' => array_fill(0, 12, null),
                    ];
                }
                $classroomsOrdered[$idx]['ranks'][$m] = $ranksInMonth[$cid];
            }
        }
        $classroomsOrdered = array_values($classroomsOrdered);

        return [
            'monthLabels' => $monthLabels,
            'classrooms' => $classroomsOrdered,
        ];
    }

    /**
     * バンプチャート用: 指定月のトップ20教室の利用者数から順位を付ける（同率は同じ順位、次を飛ばす）。0の教室は null。
     *
     * @param  array<int, int>  $classroomIds
     * @param  array<int, array<int, int>>  $monthlyUserCounts
     * @return array<int, int|null>
     */
    private function rankClassroomsInMonth(array $classroomIds, array $monthlyUserCounts, int $monthIndex): array
    {
        $counts = [];
        foreach ($classroomIds as $cid) {
            $counts[$cid] = $monthlyUserCounts[$cid][$monthIndex] ?? 0;
        }
        arsort($counts, SORT_NUMERIC);
        $ranks = [];
        $rank = 1;
        $prevCount = null;
        $prevRank = 0;
        foreach ($counts as $cid => $c) {
            if ($c === 0) {
                $ranks[$cid] = null;

                continue;
            }
            if ($prevCount !== null && $c < $prevCount) {
                $rank = $prevRank + 1;
            }
            $ranks[$cid] = $rank;
            $prevRank = $rank;
            $prevCount = $c;
        }

        return $ranks;
    }

    /**
     * バンプチャートの集計基準説明
     */
    private function getBumpChartDescription(): string
    {
        return '過去12暦月の合計利用者数が上位20の教室を対象に、各月の利用者数で1〜20位の順位をつけ、月ごとの変動を表示しています。利用者数はその月・その教室でクーポンを利用したユニークな利用者数です。同率の場合は同じ順位とし、次の順位は飛ばします。ある月に利用がなかった場合は線を途切れさせています。';
    }

    /**
     * 登録事業者の種別分布グラフ用データ（月末時点の有効事業者を申請者種別ごとに集計）
     *
     * @param  array<int, Carbon>  $monthEnds
     * @param  array<int, string>  $monthLabels
     * @return array{monthLabels: array<int, string>, corporation: array<int, int>, voluntary_group: array<int, int>, individual: array<int, int>, government_agency: array<int, int>}
     */
    private function buildApplicantTypeChartData(int $subdomainId, array $monthEnds, array $monthLabels): array
    {
        $businesses = BusinessInfo::query()
            ->where('subdomain_id', $subdomainId)
            ->where('is_active', true)
            ->get(['created_at', 'applicant_type']);

        $corporation = array_fill(0, 12, 0);
        $voluntaryGroup = array_fill(0, 12, 0);
        $individual = array_fill(0, 12, 0);
        $governmentAgency = array_fill(0, 12, 0);

        foreach ($businesses as $b) {
            $createdAt = $b->created_at;
            for ($i = 0; $i < 12; $i++) {
                if ($createdAt <= $monthEnds[$i]) {
                    match ($b->applicant_type) {
                        'corporation' => $corporation[$i]++,
                        'voluntary_group' => $voluntaryGroup[$i]++,
                        'individual' => $individual[$i]++,
                        'government_agency' => $governmentAgency[$i]++,
                        default => null,
                    };
                }
            }
        }

        return [
            'monthLabels' => $monthLabels,
            'corporation' => $corporation,
            'voluntary_group' => $voluntaryGroup,
            'individual' => $individual,
            'government_agency' => $governmentAgency,
        ];
    }

    /**
     * 登録事業者種別分布グラフの集計基準説明
     */
    private function getApplicantTypeChartDescription(): string
    {
        // return '各月の末日時点で有効（is_active=1）な登録事業者を、申請者種別（法人・任意団体・個人事業主）ごとに件数で集計しています。直近12暦月の推移を積み上げ横棒で表示しています。';
        return '各月の末日時点で有効な登録事業者を、申請者種別（法人・任意団体・個人事業主・行政機関）ごとに件数で集計しています。直近12暦月の推移を積み上げ横棒で表示しています。';
    }

    /**
     * クーポン利用の習い事の種別分布グラフ用データ（子カテゴリ上位20+その他、利用件数・12ヶ月積み上げ）
     *
     * @param  array<int, Carbon>  $monthStarts
     * @param  array<int, Carbon>  $monthEnds
     * @param  array<int, string>  $monthLabels
     * @return array{monthLabels: array<int, string>, labels: array<int, string>, series: array<int, array<int, int>>}
     */
    private function buildUsageByLessonCategoryChartData(int $subdomainId, array $monthStarts, array $monthEnds, array $monthLabels): array
    {
        $firstStart = $monthStarts[0]->copy()->startOfDay();
        $lastEnd = $monthEnds[11]->copy()->endOfDay();

        $usages = VoucherUsage::query()
            ->where('subdomain_id', $subdomainId)
            ->where('is_cancelled', false)
            ->whereBetween('used_at', [$firstStart, $lastEnd])
            ->get(['classroom_info_id', 'used_at']);

        if ($usages->isEmpty()) {
            return [
                'monthLabels' => $monthLabels,
                'labels' => [],
                'series' => [],
            ];
        }

        $classroomIds = $usages->pluck('classroom_info_id')->unique()->values()->all();
        $classrooms = ClassroomInfo::query()
            ->whereIn('id', $classroomIds)
            ->get(['id', 'lesson_category'])
            ->keyBy('id');

        $monthlyCount = array_fill(0, 12, []);
        foreach (range(0, 11) as $i) {
            $monthlyCount[$i] = [];
        }

        foreach ($usages as $usage) {
            $classroom = $classrooms->get($usage->classroom_info_id);
            $lessonCategory = $classroom?->lesson_category;
            $categoryKey = ($lessonCategory === null || $lessonCategory === -1) ? '__other__' : (string) $lessonCategory;

            $usedAt = $usage->used_at;
            $monthIndex = null;
            for ($i = 0; $i < 12; $i++) {
                $start = $monthStarts[$i]->copy()->startOfDay();
                $end = $monthEnds[$i]->copy()->endOfDay();
                if ($usedAt->between($start, $end)) {
                    $monthIndex = $i;
                    break;
                }
            }
            if ($monthIndex === null) {
                continue;
            }

            if (! isset($monthlyCount[$monthIndex][$categoryKey])) {
                $monthlyCount[$monthIndex][$categoryKey] = 0;
            }
            $monthlyCount[$monthIndex][$categoryKey]++;
        }

        $totalPerCategory = [];
        foreach (range(0, 11) as $i) {
            foreach ($monthlyCount[$i] as $key => $count) {
                $totalPerCategory[$key] = ($totalPerCategory[$key] ?? 0) + $count;
            }
        }

        $otherKey = '__other__';
        $categoryIdsOnly = array_values(array_filter(array_keys($totalPerCategory), fn ($k) => $k !== $otherKey));
        usort($categoryIdsOnly, fn ($a, $b) => ($totalPerCategory[$b] ?? 0) <=> ($totalPerCategory[$a] ?? 0));
        $top20CategoryIds = array_slice($categoryIdsOnly, 0, 20);

        $orderedLabels = [];
        $series = [];

        if ($top20CategoryIds !== []) {
            $categories = CourseCategory::query()
                ->whereIn('id', $top20CategoryIds)
                ->get(['id', 'name'])
                ->keyBy('id');

            foreach ($top20CategoryIds as $id) {
                $orderedLabels[] = $categories->get((int) $id)?->name ?? "ID:{$id}";
                $row = [];
                for ($m = 0; $m < 12; $m++) {
                    $row[] = $monthlyCount[$m][$id] ?? 0;
                }
                $series[] = $row;
            }
        }

        $otherRow = [];
        for ($m = 0; $m < 12; $m++) {
            $sum = $monthlyCount[$m][$otherKey] ?? 0;
            foreach (array_keys($monthlyCount[$m]) as $key) {
                if ($key !== $otherKey && ! in_array($key, $top20CategoryIds, true)) {
                    $sum += $monthlyCount[$m][$key];
                }
            }
            $otherRow[] = $sum;
        }
        if (array_sum($otherRow) > 0 || $orderedLabels === []) {
            $orderedLabels[] = 'その他';
            $series[] = $otherRow;
        }

        return [
            'monthLabels' => $monthLabels,
            'labels' => $orderedLabels,
            'series' => $series,
        ];
    }

    /**
     * クーポン利用の習い事の種別分布グラフの集計基準説明
     */
    private function getUsageByLessonCategoryChartDescription(): string
    {
        return '直近12暦月のクーポン利用（キャンセル除く）を、利用先教室の習い事種別（子カテゴリ）ごとに件数で集計しています。利用件数が上位20の種別と、それ以外を「その他」にまとめて積み上げ横棒で表示しています。';
    }

    /**
     * 申請・審査の推移グラフ用データ（利用申請数・利用者審査通過数・事業者申請数・事業者審査通過数、12ヶ月）
     *
     * @param  array<int, Carbon>  $monthStarts
     * @param  array<int, Carbon>  $monthEnds
     * @param  array<int, string>  $monthLabels
     * @return array{monthLabels: array<int, string>, userApplicationCounts: array<int, int>, beneficiaryApprovalCounts: array<int, int>, businessApplicationCounts: array<int, int>, businessApprovalCounts: array<int, int>}
     */
    private function buildApplicationAndApprovalChartData(int $subdomainId, array $monthStarts, array $monthEnds, array $monthLabels): array
    {
        $firstStart = $monthStarts[0]->copy()->startOfDay();
        $lastEnd = $monthEnds[11]->copy()->endOfDay();

        $userApplications = UserApplication::query()
            ->where('subdomain_id', $subdomainId)
            ->whereBetween('created_at', [$firstStart, $lastEnd])
            ->get(['created_at']);

        $beneficiaries = Beneficiary::query()
            ->where('subdomain_id', $subdomainId)
            ->whereBetween('created_at', [$firstStart, $lastEnd])
            ->get(['created_at']);

        $businessApplications = BusinessInfo::query()
            ->where('subdomain_id', $subdomainId)
            ->whereBetween('created_at', [$firstStart, $lastEnd])
            ->get(['created_at']);

        $businessApprovals = BusinessInfo::query()
            ->where('subdomain_id', $subdomainId)
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$firstStart->format('Y-m-d'), $lastEnd->format('Y-m-d')])
            ->get(['approved_at']);

        return [
            'monthLabels' => $monthLabels,
            'userApplicationCounts' => $this->countByMonth($userApplications, 'created_at'),
            'beneficiaryApprovalCounts' => $this->countByMonth($beneficiaries, 'created_at'),
            'businessApplicationCounts' => $this->countByMonth($businessApplications, 'created_at'),
            'businessApprovalCounts' => $this->countByMonth($businessApprovals, 'approved_at'),
        ];
    }

    /**
     * 申請・審査の推移グラフの集計基準説明
     */
    private function getApplicationApprovalChartDescription(): string
    {
        // return '直近12暦月の利用申請数（user_applications の created_at）、利用者審査通過数（beneficiaries の created_at＝受給者登録月）、事業者申請数（business_infos の created_at）、事業者審査通過数（business_infos の approved_at）を件数で集計しています。';
        return '直近12暦月の利用申請数（利用者申請の作成日）、利用者審査通過数（利用者の受給者登録月）、事業者申請数（事業者の作成日）、事業者審査通過数（事業者の審査通過日）を件数で集計しています。';
    }

    /**
     * クーポン関連グラフの集計基準説明
     *
     * @return array<string, string>
     */
    private function getCouponReportDescriptions(): array
    {
        return [
            // 'issued_user_count' => 'その月にクーポンを発行された利用者（beneficiary）のユニーク人数。発行日（issue_date）で集計。',
            'issued_user_count' => 'その月にクーポンを発行された利用者のユニーク人数。発行日で集計。',
            // 'used_user_count' => 'その月にクーポンを1回以上利用した利用者（user_id）のユニーク人数。利用日時（used_at）で集計。キャンセル済みは除く。',
            'used_user_count' => 'その月にクーポンを1回以上利用した利用者のユニーク人数。利用日時で集計。キャンセル済みは除く。',
            'application_rate' => 'その月に発行された利用者数のうち、その月に1回以上利用した利用者数の割合（%）。分母＝発行ユニーク利用者数、分子＝利用ユニーク利用者数。',
            'issued_amount' => 'その月に発行したクーポンの金額合計（円）。発行日で集計。',
            'used_amount' => 'その月に利用された金額の合計（円）。利用日時で集計。キャンセル済みは除く。',
            'balance' => 'その月の末日時点の残高。その月までに発行した金額合計から、その月までに利用した金額合計を引いた値（円）。',
            'avg_balance_per_user' => '上記「月次クーポン残高」を、その月にクーポンが発行されたユニーク利用者数で割った値（円）。',
        ];
    }

    /**
     * 事業者・教室・コース数グラフの集計基準説明
     *
     * @return array<string, string>
     */
    private function getEntityReportDescriptions(): array
    {
        return [
            // 'business_count' => 'その月の末日時点で有効（is_active=1）な事業者情報の件数。当月末日までに登録されたものを対象。',
            'business_count' => 'その月の末日時点で有効な事業者情報の件数。当月末日までに登録されたものを対象。',
            // 'classroom_count' => 'その月の末日時点で有効（is_active=1）な教室情報の件数。',
            'classroom_count' => 'その月の末日時点で有効な教室情報の件数。',
            // 'course_count' => 'その月の末日時点で有効（is_active=1）なコース情報の件数。',
            'course_count' => 'その月の末日時点で有効なコース情報の件数。',
        ];
    }

    /**
     * クーポン関連グラフの凡例色（admin-report-chart.js の COLORS と一致）
     *
     * @return array<string, string>
     */
    private function getCouponChartColors(): array
    {
        return [
            'issued_user_count' => 'rgb(59, 130, 246)',
            'used_user_count' => 'rgb(234, 88, 12)',
            'application_rate' => 'rgb(139, 92, 246)',
            'issued_amount' => 'rgb(34, 197, 94)',
            'used_amount' => 'rgb(20, 184, 166)',
            'balance' => 'rgb(239, 68, 68)',
            'avg_balance_per_user' => 'rgb(236, 72, 153)',
        ];
    }

    /**
     * 事業者・教室・コース数グラフの凡例色（admin-report-chart.js の COLORS と一致）
     *
     * @return array<string, string>
     */
    private function getEntityChartColors(): array
    {
        return [
            'business_count' => 'rgb(59, 130, 246)',
            'classroom_count' => 'rgb(234, 88, 12)',
            'course_count' => 'rgb(34, 197, 94)',
        ];
    }
}
