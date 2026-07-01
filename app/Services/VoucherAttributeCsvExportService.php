<?php

namespace App\Services;

use App\Models\Subdomain;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherAttributeCsvExportService
{
    private const EMPTY_VALUE = '-';

    public function __construct(
        private VoucherCsvExportService $voucherCsvExportService
    ) {}

    /**
     * 属性別CSVヘッダー（出力順）
     *
     * @return array<int, string>
     */
    public function getHeaders(): array
    {
        return [
            'No.',
            '年度',
            '学校名',
            '学年',
            '利用者ラベル',
            '金額',
            '抽出日',
        ];
    }

    /**
     * 一覧と同一の絞り込み条件でクエリを取得（VoucherCsvExportService を流用）
     *
     * @return Builder<Voucher>
     */
    public function buildQuery(Request $request, Subdomain $subdomain): Builder
    {
        return $this->voucherCsvExportService->buildQuery($request, $subdomain);
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
     * ダウンロード用ストリーミングレスポンスを返す。
     */
    public function downloadResponse(Request $request, ?Subdomain $subdomain): StreamedResponse|RedirectResponse
    {
        if (! $subdomain) {
            return redirect()->route('admin.dashboard')->with('error', 'サブドメインが設定されていません。');
        }

        $query = $this->buildQuery($request, $subdomain);
        $filename = 'vouchers_attribute_'.Carbon::now()->format('Ymd_His').'.csv';

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
     * 1行分の配列を組み立て（beneficiary 不在時は学校名・学年・利用者ラベルを「-」）
     *
     * @return array<int, string|int>
     */
    private function buildRow(Voucher $voucher, int $no, Carbon $extractedAt): array
    {
        $beneficiary = $voucher->beneficiary;

        return [
            $no,
            $this->voucherCsvExportService->fiscalYearFromIssueDate($voucher->issue_date ?? null),
            $beneficiary?->elementary_school_name ?? self::EMPTY_VALUE,
            $beneficiary?->grade ?? self::EMPTY_VALUE,
            $beneficiary && $beneficiary->labels !== null && $beneficiary->labels !== '' ? $beneficiary->labels : self::EMPTY_VALUE,
            $voucher->amount,
            $extractedAt->format('Y-m-d H:i:s'),
        ];
    }
}
