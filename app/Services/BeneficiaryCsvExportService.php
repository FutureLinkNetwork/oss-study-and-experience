<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class BeneficiaryCsvExportService
{
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
            '就学援助認定番号',
            '保護者名',
            '保護者名カナ',
            '保護者生年月日',
            '電話番号',
            'メールアドレス',
            '申請日',
            '認定日(交付決定日)',
            '住所',
            '対象児童名',
            '対象児童名カナ',
            '対象児童生年月日',
            '学校名',
            '学年',
            '調査同意',
            '対象児童住所',
            '申請者と同一の住所',
            '教室名1',
            '所在地1',
            '電話番号1',
            '担当者1',
            '教室名2',
            '所在地2',
            '電話番号2',
            '担当者2',
            '教室名3',
            '所在地3',
            '電話番号3',
            '担当者3',
            'ステータス',
            '資格喪失日',
            '利用者ラベル',
            '備考',
            'クーポン：交付総額',
            'クーポン：発行日始期',
            'クーポン：発行日終期',
            'クーポン：申込総額',
            '抽出日',
        ];
    }

    /**
     * 申請日から年度（4月〜翌3月）を算出
     */
    public function fiscalYearFromApplicationDate(?\Carbon\Carbon $applicationDate): string
    {
        if (! $applicationDate) {
            return '';
        }

        $year = (int) $applicationDate->format('Y');
        $month = (int) $applicationDate->format('n');

        return (string) ($month >= 4 ? $year : $year - 1);
    }

    /**
     * 指定ストリームにCSVを出力する（ヘッダー＋データ行）。Shift_JISでエンコード。
     */
    public function streamCsvTo($stream, Builder $beneficiaryQuery, Carbon $extractedAt): void
    {
        $headers = $this->getHeaders();
        $sjisHeaders = array_map(fn (string $h) => mb_convert_encoding($h, 'SJIS-win', 'UTF-8'), $headers);
        fputcsv($stream, $sjisHeaders);

        $query = (clone $beneficiaryQuery)
            ->with(['vouchers'])
            ->orderBy('created_at', 'desc');

        $no = 1;
        foreach ($query->cursor() as $beneficiary) {
            assert($beneficiary instanceof Beneficiary);
            $row = $this->buildRow($beneficiary, $no++, $extractedAt);
            $sjisRow = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8'), $row);
            fputcsv($stream, $sjisRow);
        }
    }

    /**
     * 1行分の配列を組み立て
     *
     * @return array<int, string|int>
     */
    private function buildRow(Beneficiary $beneficiary, int $no, Carbon $extractedAt): array
    {
        $vouchers = $beneficiary->vouchers;
        $totalAmount = $vouchers->sum('amount');
        $issueDates = $vouchers->pluck('issue_date')->filter();
        $issueDateMin = $issueDates->min();
        $issueDateMax = $issueDates->max();

        $usageTotalAmount = $beneficiary->user_id
            ? (int) VoucherUsage::where('user_id', $beneficiary->user_id)
                ->where('is_cancelled', false)
                ->sum('amount')
            : 0;

        return [
            $no,
            $this->fiscalYearFromApplicationDate($beneficiary->application_date),
            $beneficiary->child_id ?? '',
            $beneficiary->certification_number ?? '',
            $beneficiary->guardian_name ?? '',
            $beneficiary->guardian_name_kana ?? '',
            $beneficiary->guardian_birth_date ? $beneficiary->guardian_birth_date->format('Y-m-d') : '',
            $beneficiary->guardian_phone ?? '',
            $beneficiary->guardian_email ?? '',
            $beneficiary->application_date ? $beneficiary->application_date->format('Y-m-d') : '',
            $beneficiary->certification_date ? $beneficiary->certification_date->format('Y-m-d') : '',
            $beneficiary->guardian_address ?? '',
            $beneficiary->child_name ?? '',
            $beneficiary->child_name_kana ?? '',
            $beneficiary->child_birth_date ? $beneficiary->child_birth_date->format('Y-m-d') : '',
            $beneficiary->elementary_school_name ?? '',
            $beneficiary->grade ?? '',
            $beneficiary->survey_consent ? 'はい' : 'いいえ',
            $beneficiary->child_address ?? '',
            $beneficiary->child_address_same_as_guardian ? 'はい' : 'いいえ',
            $beneficiary->classroom_name_1 ?? '',
            $beneficiary->classroom_location_1 ?? '',
            $beneficiary->classroom_phone_1 ?? '',
            $beneficiary->classroom_contact_person_1 ?? '',
            $beneficiary->classroom_name_2 ?? '',
            $beneficiary->classroom_location_2 ?? '',
            $beneficiary->classroom_phone_2 ?? '',
            $beneficiary->classroom_contact_person_2 ?? '',
            $beneficiary->classroom_name_3 ?? '',
            $beneficiary->classroom_location_3 ?? '',
            $beneficiary->classroom_phone_3 ?? '',
            $beneficiary->classroom_contact_person_3 ?? '',
            $beneficiary->status ?? '',
            $beneficiary->disqualification_date ? $beneficiary->disqualification_date->format('Y-m-d') : '',
            $beneficiary->labels ?? '',
            $beneficiary->remarks ?? '',
            $totalAmount,
            $issueDateMin ? \Carbon\Carbon::parse($issueDateMin)->format('Y-m-d') : '',
            $issueDateMax ? \Carbon\Carbon::parse($issueDateMax)->format('Y-m-d') : '',
            $usageTotalAmount,
            $extractedAt->format('Y-m-d H:i:s'),
        ];
    }
}
