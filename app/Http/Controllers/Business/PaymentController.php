<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessInfo;
use App\Models\BusinessPaymentDownload;
use App\Models\ClassroomInfo;
use App\Models\PaymentAggregate;
use App\Services\PaymentNoticePdfService;
use App\Traits\HandlesAuth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    use HandlesAuth;

    public function __construct(
        protected PaymentNoticePdfService $pdfService
    ) {}

    /**
     * 支払一覧（月別サマリ・自事業者のみ）
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $businessInfo = $this->getUserBusinessInfo();
        if (! $businessInfo) {
            return redirect()->route('business.dashboard')->with('error', '事業者情報が見つかりません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        $months = PaymentAggregate::query()
            ->forBusiness($businessInfo->id)
            ->where('subdomain_id', $subdomain->id)
            ->selectRaw('target_month, SUM(application_count) as total_count, SUM(total_amount) as total_amount')
            ->groupBy('target_month')
            ->orderByDesc('target_month')
            ->get()
            ->map(function ($row) {
                $targetMonth = $row->target_month;

                return [
                    'year_month' => Carbon::parse($targetMonth)->format('Y-m'),
                    'label' => Carbon::parse($targetMonth)->format('Y年n月'),
                    'total_count' => (int) $row->getAttribute('total_count'),
                    'total_amount' => (int) $row->getAttribute('total_amount'),
                ];
            });

        $undownloadedMonths = BusinessPaymentDownload::query()
            ->undownloaded()
            ->forBusiness($businessInfo->id)
            ->forSubdomain($subdomain->id)
            ->orderByDesc('target_month')
            ->get();

        return view('business.payments.index', [
            'subdomain' => $subdomain,
            'months' => $months,
            'undownloadedMonths' => $undownloadedMonths,
        ]);
    }

    /**
     * 支払通知PDFダウンロード（指定月・自事業者のみ）
     */
    public function downloadPdf(Request $request, string $yearMonth): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $this->isValidYearMonth($yearMonth)) {
            return redirect()->route('business.payments.index')->with('error', '指定が不正です。');
        }

        $businessInfo = $this->getUserBusinessInfo();
        if (! $businessInfo) {
            return redirect()->route('business.dashboard')->with('error', '事業者情報が見つかりません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        $aggregates = PaymentAggregate::query()
            ->forBusiness($businessInfo->id)
            ->forSubdomain($subdomain->id)
            ->forTargetMonth($yearMonth)
            ->with(['classroomInfo'])
            ->orderBy('classroom_info_id')
            ->get();

        if ($aggregates->isEmpty()) {
            return redirect()->route('business.payments.index')->with('error', '該当する支払データがありません。');
        }

        try {
            $path = $this->pdfService->generate($businessInfo, $subdomain, $yearMonth, $aggregates->all());
            $filename = '支払通知_'.Carbon::parse($yearMonth.'-01')->format('Y年n月').'.pdf';

            $targetMonthDate = $yearMonth.'-01';
            $downloadRecord = BusinessPaymentDownload::query()
                ->forSubdomain($subdomain->id)
                ->forBusiness($businessInfo->id)
                ->whereDate('target_month', $targetMonthDate)
                ->first();
            if ($downloadRecord && $downloadRecord->downloaded_at === null) {
                $downloadRecord->update(['downloaded_at' => now()]);
            }

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

            return redirect()->route('business.payments.index')->with('error', 'PDFの生成に失敗しました。');
        }
    }

    /**
     * 支払明細CSVダウンロード（指定月・自事業者のみ・教室名・申込件数・金額）
     */
    public function downloadCsv(Request $request, string $yearMonth): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $this->isValidYearMonth($yearMonth)) {
            return redirect()->route('business.payments.index')->with('error', '指定が不正です。');
        }

        $businessInfo = $this->getUserBusinessInfo();
        if (! $businessInfo) {
            return redirect()->route('business.dashboard')->with('error', '事業者情報が見つかりません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        $aggregates = PaymentAggregate::query()
            ->forBusiness($businessInfo->id)
            ->forSubdomain($subdomain->id)
            ->forTargetMonth($yearMonth)
            ->with(['classroomInfo'])
            ->orderBy('classroom_info_id')
            ->get();

        if ($aggregates->isEmpty()) {
            return redirect()->route('business.payments.index')->with('error', '該当する支払データがありません。');
        }

        $filename = '支払明細_'.Carbon::parse($yearMonth.'-01')->format('Y年n月').'.csv';

        $targetMonthDate = $yearMonth.'-01';
        $downloadRecord = BusinessPaymentDownload::query()
            ->forSubdomain($subdomain->id)
            ->forBusiness($businessInfo->id)
            ->whereDate('target_month', $targetMonthDate)
            ->first();
        if ($downloadRecord && $downloadRecord->downloaded_at === null) {
            $downloadRecord->update(['downloaded_at' => now()]);
        }

        return response()->streamDownload(function () use ($aggregates) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }
            $headers = ['教室名', '申込件数', '金額'];
            $sjisHeaders = array_map(fn ($h) => mb_convert_encoding($h, 'SJIS-win', 'UTF-8'), $headers);
            fputcsv($output, $sjisHeaders);
            foreach ($aggregates as $agg) {
                $classroom = $agg->classroomInfo;
                $classroomName = $classroom instanceof ClassroomInfo ? $classroom->classroom_name : '-';
                $row = [$classroomName, (string) $agg->application_count, (string) $agg->total_amount];
                $sjisRow = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8'), $row);
                fputcsv($output, $sjisRow);
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function getUserBusinessInfo(): ?BusinessInfo
    {
        return BusinessInfo::where('user_id', Auth::id())->first();
    }

    private function isValidYearMonth(string $yearMonth): bool
    {
        if (! preg_match('/^\d{4}-(\d{2})$/', $yearMonth, $m)) {
            return false;
        }
        $month = (int) $m[1];

        return $month >= 1 && $month <= 12;
    }
}
