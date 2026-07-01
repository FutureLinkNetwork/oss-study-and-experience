<?php

namespace App\Services;

use App\Models\Subdomain;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherCsvExportService
{
    private const EMPTY_VALUE = '-';

    /** ステータス表示ラベル（DB値 => 日本語） */
    private const STATUS_LABELS = [
        'unused' => '未使用',
        'used' => '使用中',
        'expired' => '期限切れ',
    ];

    /**
     * CSVヘッダー（出力順）
     *
     * @return array<int, string>
     */
    public function getHeaders(): array
    {
        return [
            'No.',
            '年度',
            'クーポン番号',
            '発行日',
            '有効期限',
            '金額',
            '対象児童名',
            'ステータス',
            '抽出日',
        ];
    }

    /**
     * 発行日（issue_date）から年度（4月〜翌3月）を算出
     */
    public function fiscalYearFromIssueDate(?\Carbon\Carbon $issueDate): string
    {
        if (! $issueDate) {
            return '';
        }

        $year = (int) $issueDate->format('Y');
        $month = (int) $issueDate->format('n');

        return (string) ($month >= 4 ? $year : $year - 1);
    }

    /**
     * 一覧と同一の絞り込み条件でクエリを組み立てる
     *
     * @return Builder<Voucher>
     */
    public function buildQuery(Request $request, Subdomain $subdomain): Builder
    {
        $query = Voucher::query()
            ->where('subdomain_id', $subdomain->id)
            ->with('beneficiary');

        if ($request->filled('voucher_number')) {
            $query->where('voucher_number', 'like', '%'.$request->voucher_number.'%');
        }

        if ($request->filled('child_name')) {
            $query->whereHas('beneficiary', function ($q) use ($request) {
                $q->where('child_name', 'like', '%'.$request->child_name.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('issue_date_from')) {
            $query->where('issue_date', '>=', $request->issue_date_from);
        }

        if ($request->filled('issue_date_to')) {
            $query->where('issue_date', '<=', $request->issue_date_to);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 指定ストリームにCSVを出力する（ヘッダー＋データ行）。Shift_JISでエンコード。
     *
     * @param  Builder<Voucher>  $query
     */
    public function streamCsvTo($stream, Builder $query, Carbon $extractedAt): void
    {
        $headers = $this->getHeaders();
        $sjisHeaders = array_map(fn (string $h) => mb_convert_encoding($h, 'SJIS-win', 'UTF-8'), $headers);
        fputcsv($stream, $sjisHeaders);

        $no = 1;
        foreach ($query->cursor() as $voucher) {
            assert($voucher instanceof Voucher);
            $row = $this->buildRow($voucher, $no++, $extractedAt);
            $sjisRow = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8'), $row);
            fputcsv($stream, $sjisRow);
        }
    }

    /**
     * ダウンロード用ストリーミングレスポンスを返す。subdomain が null の場合は RedirectResponse。
     */
    public function downloadResponse(Request $request, ?Subdomain $subdomain): StreamedResponse|RedirectResponse
    {
        if (! $subdomain) {
            return redirect()->route('admin.dashboard')->with('error', 'サブドメインが設定されていません。');
        }

        $query = $this->buildQuery($request, $subdomain);
        $filename = 'vouchers_'.Carbon::now()->format('Ymd_His').'.csv';

        return response()->streamDownload(
            function () use ($query) {
                $extractedAt = Carbon::now();
                $stream = fopen('php://output', 'w');
                if ($stream !== false) {
                    $this->streamCsvTo($stream, $query, $extractedAt);
                    fclose($stream);
                }
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=Shift_JIS',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    /**
     * 1行分の配列を組み立て
     *
     * @return array<int, string|int>
     */
    private function buildRow(Voucher $voucher, int $no, Carbon $extractedAt): array
    {
        $statusLabel = self::STATUS_LABELS[$voucher->status] ?? $voucher->status;

        return [
            $no,
            $this->fiscalYearFromIssueDate($voucher->issue_date ?? null),
            $voucher->voucher_number,
            $voucher->issue_date ? $voucher->issue_date->format('Y-m-d') : self::EMPTY_VALUE,
            $voucher->expiry_date ? $voucher->expiry_date->format('Y-m-d') : self::EMPTY_VALUE,
            $voucher->amount,
            $voucher->beneficiary?->child_name ?? self::EMPTY_VALUE,
            $statusLabel,
            $extractedAt->format('Y-m-d H:i:s'),
        ];
    }
}
