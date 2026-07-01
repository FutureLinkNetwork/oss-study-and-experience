<?php

namespace App\Services;

use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CouponUsageCsvExportService
{
    private const EMPTY_VALUE = '-';

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
            'こどもID',
            '保護者名',
            '保護者名カナ',
            '申請日',
            '認定日(交付決定日）',
            '住所',
            '対象児童名',
            '対象児童名カナ',
            '学校名',
            '学年',
            '調査同意',
            '利用者ステータス',
            '資格喪失日',
            '利用者ラベル',
            '交付総額',
            '発行日始期',
            '申込日時',
            '事業者ID',
            '事業者名',
            '教室ID',
            '教室名',
            'コースID',
            'コース名',
            '利用金額',
            'クーポンステータス',
            '抽出日',
        ];
    }

    /**
     * 利用日（used_at）から年度（4月〜翌3月）を算出
     */
    public function fiscalYearFromUsedAt(?\Carbon\Carbon $usedAt): string
    {
        if (! $usedAt) {
            return '';
        }

        $year = (int) $usedAt->format('Y');
        $month = (int) $usedAt->format('n');

        return (string) ($month >= 4 ? $year : $year - 1);
    }

    /**
     * 利用日から当該年度の開始日・終了日を返す [start, end]
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function fiscalYearRangeFromUsedAt(Carbon $usedAt): array
    {
        $year = (int) $usedAt->format('Y');
        $month = (int) $usedAt->format('n');

        if ($month >= 4) {
            $start = Carbon::create($year, 4, 1)->startOfDay();
            $end = Carbon::create($year + 1, 3, 31)->endOfDay();
        } else {
            $start = Carbon::create($year - 1, 4, 1)->startOfDay();
            $end = Carbon::create($year, 3, 31)->endOfDay();
        }

        return [$start, $end];
    }

    /**
     * 一覧と同一の絞り込み条件でクエリを組み立てる
     */
    public function buildQuery(Request $request, int $subdomainId): Builder
    {
        $query = VoucherUsage::query()
            ->where('subdomain_id', $subdomainId)
            ->with(['user.beneficiary.vouchers', 'businessInfo', 'classroomInfo', 'courseInfo']);

        if ($request->filled('used_at_from')) {
            $from = Carbon::parse($request->used_at_from)->startOfDay();
            $query->where('used_at', '>=', $from);
        }
        if ($request->filled('used_at_to')) {
            $to = Carbon::parse($request->used_at_to)->endOfDay();
            $query->where('used_at', '<=', $to);
        }
        if ($request->filled('child_name')) {
            $keyword = $request->child_name;
            $query->whereHas('user.beneficiary', function ($q) use ($keyword) {
                $q->where('child_name', 'LIKE', '%'.$keyword.'%');
            });
        }
        if ($request->filled('classroom_name')) {
            $keyword = $request->classroom_name;
            $query->whereHas('classroomInfo', function ($q) use ($keyword) {
                $q->where('classroom_name', 'LIKE', '%'.$keyword.'%');
            });
        }

        return $query->orderByDesc('used_at');
    }

    /**
     * 指定ストリームにCSVを出力する（ヘッダー＋データ行）。Shift_JISでエンコード。
     */
    public function streamCsvTo($stream, Builder $query, Carbon $extractedAt): void
    {
        $headers = $this->getHeaders();
        $sjisHeaders = array_map(fn (string $h) => mb_convert_encoding($h, 'SJIS-win', 'UTF-8'), $headers);
        fputcsv($stream, $sjisHeaders);

        $no = 1;
        foreach ($query->cursor() as $usage) {
            assert($usage instanceof VoucherUsage);
            $row = $this->buildRow($usage, $no++, $extractedAt);
            $sjisRow = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8'), $row);
            fputcsv($stream, $sjisRow);
        }
    }

    /**
     * ダウンロード用ストリーミングレスポンスを返す。subdomainId が null の場合は RedirectResponse。
     */
    public function downloadResponse(Request $request, ?int $subdomainId): StreamedResponse|RedirectResponse
    {
        if (! $subdomainId) {
            return redirect()->route('admin.dashboard')->with('error', 'サブドメインが設定されていません。');
        }

        $query = $this->buildQuery($request, $subdomainId);
        $filename = 'coupon_usages_'.Carbon::now()->format('Ymd_His').'.csv';

        return response()->streamDownload(
            function () use ($query) {
                $extractedAt = Carbon::now();
                $this->streamCsvTo(fopen('php://output', 'w'), $query, $extractedAt);
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
    private function buildRow(VoucherUsage $usage, int $no, Carbon $extractedAt): array
    {
        $beneficiary = $usage->user?->beneficiary;
        $user = $usage->user;

        $guardianName = $beneficiary?->guardian_name ?? $user?->name ?? self::EMPTY_VALUE;
        $guardianNameKana = $beneficiary?->guardian_name_kana ?? self::EMPTY_VALUE;
        $applicationDate = $beneficiary?->application_date
            ? $beneficiary->application_date->format('Y-m-d')
            : self::EMPTY_VALUE;
        $certificationDate = $beneficiary?->certification_date
            ? $beneficiary->certification_date->format('Y-m-d')
            : self::EMPTY_VALUE;
        $address = $beneficiary?->guardian_address ?? self::EMPTY_VALUE;
        $childName = $beneficiary?->child_name ?? self::EMPTY_VALUE;
        $childNameKana = $beneficiary?->child_name_kana ?? self::EMPTY_VALUE;
        $schoolName = $beneficiary?->elementary_school_name ?? self::EMPTY_VALUE;
        $grade = $beneficiary?->grade ?? self::EMPTY_VALUE;
        $surveyConsent = $beneficiary !== null
            ? ($beneficiary->survey_consent ? 'はい' : 'いいえ')
            : self::EMPTY_VALUE;
        $beneficiaryStatus = $beneficiary?->status ?? self::EMPTY_VALUE;
        $disqualificationDate = $beneficiary?->disqualification_date
            ? $beneficiary->disqualification_date->format('Y-m-d')
            : self::EMPTY_VALUE;
        $labels = $beneficiary?->labels ?? self::EMPTY_VALUE;

        $childId = $beneficiary?->child_id ?? self::EMPTY_VALUE;

        $deliveryTotal = 0;
        $issueDateStart = '';
        if ($beneficiary && $usage->used_at) {
            [$fiscalStart, $fiscalEnd] = $this->fiscalYearRangeFromUsedAt($usage->used_at);
            $vouchersInYear = $beneficiary->vouchers->filter(function ($v) use ($fiscalStart, $fiscalEnd) {
                if (! $v->issue_date) {
                    return false;
                }
                $d = Carbon::parse($v->issue_date);

                return $d->between($fiscalStart, $fiscalEnd);
            });
            $deliveryTotal = $vouchersInYear->sum('amount');
            $minDate = $vouchersInYear->min('issue_date');
            $issueDateStart = $minDate ? Carbon::parse($minDate)->format('Y-m-d') : '';
        }
        if ($beneficiary === null) {
            $issueDateStart = self::EMPTY_VALUE;
        }

        $usedAtStr = $usage->used_at ? $usage->used_at->format('Y-m-d H:i:s') : '';

        $usageStatus = $usage->is_cancelled ? 'キャンセル' : '利用済';

        return [
            $no,
            $this->fiscalYearFromUsedAt($usage->used_at),
            $childId,
            $guardianName,
            $guardianNameKana,
            $applicationDate,
            $certificationDate,
            $address,
            $childName,
            $childNameKana,
            $schoolName,
            $grade,
            $surveyConsent,
            $beneficiaryStatus,
            $disqualificationDate,
            $labels,
            $deliveryTotal,
            $issueDateStart,
            $usedAtStr,
            (string) ($usage->business_info_id ?? ''),
            $usage->businessInfo?->business_name ?? '',
            (string) ($usage->classroom_info_id ?? ''),
            $usage->classroomInfo?->classroom_name ?? '',
            (string) ($usage->course_info_id ?? ''),
            $usage->courseInfo?->course_name ?? '',
            $usage->amount,
            $usageStatus,
            $extractedAt->format('Y-m-d H:i:s'),
        ];
    }
}
