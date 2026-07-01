<?php

namespace App\Services;

use App\Models\UserApplication;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class UserApplicationCsvExportService
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
            '認定番号',
            '保護者名',
            '保護者名カナ',
            '保護者生年月日',
            '住所',
            '電話番号',
            'メール',
            '対象児童名',
            '対象児童名カナ',
            '対象児童生年月日',
            '小学校名',
            '学年',
            '対象児童住所',
            '申請者と同一住所',
            '自治体住所登録・就学援助受給',
            '調査同意',
            'プライバシーポリシー同意',
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
            '出力済みフラグ',
            'ダウンロード対象外フラグ',
            '備考（運営用）',
            '申請日',
        ];
    }

    /**
     * 指定ストリームにCSVを出力する（ヘッダー＋データ行）。Shift_JISでエンコード。
     */
    public function streamCsvTo($stream, Builder $userApplicationQuery, Carbon $extractedAt): void
    {
        $headers = $this->getHeaders();
        $sjisHeaders = array_map(fn (string $h) => mb_convert_encoding($h, 'SJIS-win', 'UTF-8'), $headers);
        fputcsv($stream, $sjisHeaders);

        $query = (clone $userApplicationQuery)
            ->orderBy('created_at', 'asc');

        $no = 1;
        foreach ($query->cursor() as $userApplication) {
            assert($userApplication instanceof UserApplication);
            $row = $this->buildRow($userApplication, $no++);
            $sjisRow = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8'), $row);
            fputcsv($stream, $sjisRow);
        }
    }

    /**
     * 1行分の配列を組み立て
     *
     * @return array<int, string|int>
     */
    private function buildRow(UserApplication $userApplication, int $no): array
    {
        return [
            $no,
            $userApplication->certification_number ?? '',
            $userApplication->guardian_name ?? '',
            $userApplication->guardian_name_kana ?? '',
            $userApplication->guardian_birth_date ? $userApplication->guardian_birth_date->format('Y-m-d') : '',
            $userApplication->guardian_address ?? '',
            $userApplication->guardian_phone ?? '',
            $userApplication->guardian_email ?? '',
            $userApplication->child_name ?? '',
            $userApplication->child_name_kana ?? '',
            $userApplication->child_birth_date ? $userApplication->child_birth_date->format('Y-m-d') : '',
            $userApplication->elementary_school_name ?? '',
            $userApplication->grade ?? '',
            $userApplication->child_address ?? '',
            $userApplication->child_address_same_as_guardian ? 'はい' : 'いいえ',
            $userApplication->child_registered_in_municipality_and_receiving_scholarship ? 'はい' : 'いいえ',
            $userApplication->survey_consent ? 'はい' : 'いいえ',
            $userApplication->privacy_policy_agreed ? 'はい' : 'いいえ',
            $userApplication->classroom_name_1 ?? '',
            $userApplication->classroom_location_1 ?? '',
            $userApplication->classroom_phone_1 ?? '',
            $userApplication->classroom_contact_person_1 ?? '',
            $userApplication->classroom_name_2 ?? '',
            $userApplication->classroom_location_2 ?? '',
            $userApplication->classroom_phone_2 ?? '',
            $userApplication->classroom_contact_person_2 ?? '',
            $userApplication->classroom_name_3 ?? '',
            $userApplication->classroom_location_3 ?? '',
            $userApplication->classroom_phone_3 ?? '',
            $userApplication->classroom_contact_person_3 ?? '',
            $userApplication->is_exported ? 'はい' : 'いいえ',
            $userApplication->is_excluded_from_download ? 'はい' : 'いいえ',
            $userApplication->admin_remarks ?? '',
            $userApplication->created_at ? $userApplication->created_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
