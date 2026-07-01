<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingReportDownload;
use App\Models\AdminDownload;
use App\Models\BusinessInfo;
use App\Models\PaymentAggregate;
use App\Services\PaymentNoticePdfService;
use App\Services\ZenginFormatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected ZenginFormatService $zenginFormatService,
        protected PaymentNoticePdfService $pdfService
    ) {}

    /**
     * 支払集計一覧（事業者・教室別）
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $subdomainId = $user->subdomain_id;
        if (! $subdomainId) {
            return redirect()->route('admin.dashboard')->with('error', 'サブドメインが設定されていません。');
        }

        $availableMonths = PaymentAggregate::query()
            ->where('subdomain_id', $subdomainId)
            ->select('target_month')
            ->distinct()
            ->orderByDesc('target_month')
            ->pluck('target_month')
            ->map(fn ($d) => [
                'value' => Carbon::parse($d)->format('Y-m'),
                'label' => Carbon::parse($d)->format('Y年n月'),
            ])
            ->values();

        $lastMonth = Carbon::today()->subMonth()->format('Y-m');
        $selected = $request->get('month', $lastMonth);
        if ($availableMonths->isEmpty() || ! $availableMonths->contains('value', $selected)) {
            $selected = $availableMonths->isNotEmpty() ? $availableMonths->first()['value'] : null;
        }

        $aggregates = collect();
        $accountingReport = null;
        if ($selected) {
            $aggregates = PaymentAggregate::query()
                ->forTargetMonth($selected)
                ->forSubdomain($subdomainId)
                ->with(['businessInfo', 'classroomInfo'])
                ->orderBy('business_info_id')
                ->orderBy('classroom_info_id')
                ->get();

            $accountingReport = AccountingReportDownload::query()
                ->forSubdomain($subdomainId)
                ->whereDate('target_month', $selected.'-01')
                ->first();
        }

        $subdomain = $user->subdomain;

        $latestAdminDownload = AdminDownload::query()
            ->forSubdomain($subdomainId)
            ->orderByDesc('created_at')
            ->first();
        $adminDownloadLastCreatedAt = $latestAdminDownload?->created_at;

        return view('admin.payments.index', [
            'subdomain' => $subdomain,
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selected,
            'selectedMonthLabel' => $selected ? Carbon::parse($selected.'-01')->format('Y年n月') : null,
            'aggregates' => $aggregates,
            'accountingReport' => $accountingReport,
            'adminDownloadLastCreatedAt' => $adminDownloadLastCreatedAt,
        ]);
    }

    /**
     * 選択月の集計データを全銀フォーマットでダウンロード
     *
     * @return \Illuminate\Http\RedirectResponse|StreamedResponse
     */
    public function downloadZengin(Request $request)
    {
        $user = Auth::user();
        $subdomainId = $user->subdomain_id;
        if (! $subdomainId) {
            return redirect()->route('admin.dashboard')->with('error', 'サブドメインが設定されていません。');
        }

        $month = $request->get('month');
        if (! $month || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return redirect()->route('admin.payments.index')->with('error', '申込月を指定してください。');
        }

        $subdomain = $user->subdomain;
        if (! $subdomain->hasZenginHeaderConfigured()) {
            return redirect()
                ->route('admin.payments.index', ['month' => $month])
                ->with('error', '全銀用の依頼人情報が未設定です。システム管理で設定してください。');
        }

        $category = $request->get('category');
        if (! in_array($category, ['target', 'non_target'], true)) {
            return redirect()
                ->route('admin.payments.index', ['month' => $month])
                ->with('error', '公金振替区分（target または non_target）を指定してください。');
        }

        $isTarget = $category === 'target';

        $aggregates = PaymentAggregate::query()
            ->forTargetMonth($month)
            ->forSubdomain($subdomainId)
            ->forPublicFundsTransferTarget($isTarget)
            ->get();

        $rowsByBusiness = $aggregates->groupBy('business_info_id')->map(fn ($group) => $group->sum('total_amount'));

        if ($rowsByBusiness->isEmpty()) {
            return redirect()
                ->route('admin.payments.index', ['month' => $month])
                ->with('error', 'この月は振込データがありません。');
        }

        $businessIds = $rowsByBusiness->keys()->unique()->values()->all();
        $businesses = BusinessInfo::query()->whereIn('id', $businessIds)->get()->keyBy('id');
        $businessesById = $businesses->all();

        $utf8 = $this->zenginFormatService->build($subdomain, $month, $rowsByBusiness, $businessesById);
        $shiftJis = mb_convert_encoding($utf8, 'SJIS', 'UTF-8');
        $categorySuffix = $isTarget ? 'target' : 'non_target';
        $filename = 'zengin_'.str_replace('-', '', $month).'_'.$categorySuffix.'.txt';

        return response()->streamDownload(
            function () use ($shiftJis) {
                echo $shiftJis;
            },
            $filename,
            [
                'Content-Type' => 'text/plain; charset=Shift_JIS',
            ]
        );
    }

    /**
     * 指定月・指定事業者の支払通知PDFをダウンロード（事業者ページと同じフォーマット）
     * BusinessPaymentDownload.downloaded_at は更新しない
     */
    public function downloadPdf(Request $request): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $subdomainId = $user->subdomain_id;
        if (! $subdomainId) {
            return redirect()->route('admin.dashboard')->with('error', 'サブドメインが設定されていません。');
        }

        $month = $request->get('month');
        if (! $month || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return redirect()->route('admin.payments.index')->with('error', '申込月を指定してください。');
        }

        $businessId = $request->get('business_id');
        if (! $businessId || ! ctype_digit((string) $businessId)) {
            return redirect()->route('admin.payments.index', ['month' => $month])->with('error', '事業者を指定してください。');
        }

        $businessInfo = BusinessInfo::query()
            ->where('id', $businessId)
            ->where('subdomain_id', $subdomainId)
            ->first();

        if (! $businessInfo) {
            return redirect()->route('admin.payments.index', ['month' => $month])->with('error', '指定の事業者が見つかりません。');
        }

        $aggregates = PaymentAggregate::query()
            ->forTargetMonth($month)
            ->forSubdomain($subdomainId)
            ->forBusiness((int) $businessId)
            ->with(['classroomInfo'])
            ->orderBy('classroom_info_id')
            ->get();

        if ($aggregates->isEmpty()) {
            return redirect()->route('admin.payments.index', ['month' => $month])->with('error', '該当する支払データがありません。');
        }

        try {
            $subdomain = $user->subdomain;
            $path = $this->pdfService->generate($businessInfo, $subdomain, $month, $aggregates->all());
            $safeName = preg_replace('/[\x00-\x1f\\\\\/:*?"<>|]/u', '_', $businessInfo->business_name ?? '');
            $safeName = trim($safeName) !== '' ? $safeName : '事業者'.$businessId;
            $filename = '支払通知_'.Carbon::parse($month.'-01')->format('Y年n月').'_'.$safeName.'.pdf';

            return response()->streamDownload(
                function () use ($path) {
                    echo file_get_contents($path);
                    @unlink($path);
                },
                $filename,
                ['Content-Type' => 'application/pdf'],
                'inline'
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.payments.index', ['month' => $month])->with('error', 'PDFの生成に失敗しました。');
        }
    }
}
