<?php

namespace App\Services;

use App\Models\BusinessInfo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ZipArchive;

class BusinessCsvExportService
{
    private const APPLICANT_TYPE_LABELS = [
        'individual' => '個人事業主',
        'corporation' => '法人',
        'voluntary_group' => '任意団体',
        'government_agency' => '行政機関',
    ];

    public function __construct(
        private readonly BankService $bankService
    ) {}

    /**
     * 事業者コレクションからZIPファイルを生成し、一時ファイルのパスを返す。
     * 呼び出し元でダウンロードレスポンス送信後にファイル削除すること。
     */
    public function createZipPath(Collection $businesses): string
    {
        $extractedAt = Carbon::now()->format('Y-m-d H:i');

        $businessCsv = $this->buildBusinessCsvContent($businesses, $extractedAt);
        $classroomCsv = $this->buildClassroomCsvContent($businesses, $extractedAt);
        $courseCsv = $this->buildCourseCsvContent($businesses, $extractedAt);

        $zipFileName = 'business_export_'.Carbon::now()->format('Ymd_His').'.zip';
        $zipPath = sys_get_temp_dir().'/'.$zipFileName;

        $dir = dirname($zipPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('ZIPファイルを開けません: '.$zipPath);
        }

        $zip->addFromString('事業者情報.csv', $businessCsv);
        $zip->addFromString('教室情報.csv', $classroomCsv);
        $zip->addFromString('コース情報.csv', $courseCsv);
        $zip->close();

        return $zipPath;
    }

    /**
     * 文字列をShift-JISに変換してCSV行を返す
     *
     * @param  array<int, mixed>  $row
     */
    private function toSjisCsvRow(array $row): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Failed to open temp stream');
        }
        $sjisRow = array_map(function ($cell) {
            return mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8');
        }, $row);
        fputcsv($stream, $sjisRow);
        rewind($stream);
        $line = stream_get_contents($stream);
        fclose($stream);

        return $line;
    }

    private function buildBusinessCsvContent(Collection $businesses, string $extractedAt): string
    {
        $headers = [
            'No.',
            '申請者種別',
            '反社チェック',
            'プライバシーポリシー同意',
            '事業者ID',
            '事業者名',
            '事業者名（カナ）',
            '代表者役職名',
            '代表者名',
            '代表者名（カナ）',
            '郵便番号',
            '都道府県',
            '市区町村',
            '町名・番地',
            '建物名・部屋番号',
            '電話番号',
            'FAX番号',
            'メールアドレス',
            '担当者名',
            '担当者電話番号',
            '文書等送付先：宛名',
            '文書等送付先：住所',
            '営業時間',
            '定休日',
            '銀行名',
            '支店名',
            '口座種別',
            '口座番号',
            '口座名義（カナ）',
            'ステータス',
            'QR決裁のみ',
            '公金振替',
            '抽出日',
        ];

        $lines = [$this->toSjisCsvRow($headers)];

        $no = 1;
        foreach ($businesses as $business) {
            assert($business instanceof BusinessInfo);
            $bankName = $business->bank_code
                ? ($this->bankService->getBankName($business->bank_code) ?? $business->bank_code)
                : '';
            $branchName = $business->bank_code && $business->branch_code
                ? ($this->bankService->getBranchName($business->bank_code, $business->branch_code) ?? $business->branch_code)
                : '';

            $row = [
                $no++,
                self::APPLICANT_TYPE_LABELS[$business->applicant_type] ?? $business->applicant_type,
                $business->antisocial_forces_pledged ? 'する' : 'しない',
                $business->privacy_policy_agreed ? 'する' : 'しない',
                $business->id,
                $business->business_name ?? '',
                $business->business_name_kana ?? '',
                $business->representative_title ?? '',
                $business->representative_name_full ?? ($business->representative_name ?? ''),
                $business->representative_name_kana_full ?? ($business->representative_name_kana ?? ''),
                $business->postal_code ?? '',
                $business->prefecture ?? '',
                $business->city ?? '',
                $business->address1 ?? '',
                $business->building_name ?? '',
                $business->phone ?? '',
                $business->fax ?? '',
                $business->email ?? '',
                $business->contact_person ?? '',
                $business->contact_phone ?? '',
                $business->document_person ?? '',
                $business->document_address ?? '',
                $business->business_hours ?? '',
                $business->holiday ?? '',
                $bankName,
                $branchName,
                $business->account_type ?? '',
                $business->account_number ?? '',
                $business->account_holder_name ?? '',
                $business->status ?? '',
                $business->qr_only ? 'する' : 'しない',
				$business->is_public_funds_transfer_target ? '対象' : '対象外',
                $extractedAt,
            ];
            $lines[] = $this->toSjisCsvRow($row);
        }

        return implode('', $lines);
    }

    private function buildClassroomCsvContent(Collection $businesses, string $extractedAt): string
    {
        $headers = [
            '教室ID',
            '事業者ID',
            '教室名',
            '教室名（カナ）',
            '代表者名',
            '代表者名（カナ）',
            '郵便番号',
            '都道府県',
            '市区町村',
            '町名・番地',
            '建物名・部屋番号',
            '地図利用',
            '電話番号',
            'FAX',
            'メールアドレス',
            '営業時間',
            '定休日',
            '教室紹介',
            'サービス提供の種類',
            '親分類',
            '親分類の並び順',
            '習い事の種別',
            '並び順',
            '状態',
            '抽出日',
        ];

        $lines = [$this->toSjisCsvRow($headers)];

        foreach ($businesses as $business) {
            assert($business instanceof BusinessInfo);
            foreach ($business->classrooms as $classroom) {
                $lessonCategory = $classroom->relationLoaded('lessonCategoryInfo') ? $classroom->lessonCategoryInfo : null;
                $lessonCategoryName = $lessonCategory?->name ?? '';
                $parentCategory = $lessonCategory && $lessonCategory->relationLoaded('parentCategory') ? $lessonCategory->parentCategory : null;
                $parentCategoryName = $parentCategory?->name ?? '';
                $parentCategorySortOrder = $parentCategory !== null && isset($parentCategory->sort_order) ? (string) $parentCategory->sort_order : '';
                $sortOrder = $lessonCategory !== null && isset($lessonCategory->sort_order) ? (string) $lessonCategory->sort_order : '';

                $row = [
                    $classroom->id,
                    $business->id,
                    $classroom->classroom_name ?? '',
                    $classroom->classroom_name_kana ?? '',
                    $classroom->classroom_representative_name ?? '',
                    $classroom->classroom_representative_name_kana ?? '',
                    $classroom->classroom_postal_code ?? '',
                    $classroom->classroom_prefecture ?? '',
                    $classroom->classroom_city ?? '',
                    $classroom->classroom_address1 ?? '',
                    $classroom->classroom_building_name ?? '',
                    $classroom->use_map ? 'する' : 'しない',
                    $classroom->classroom_phone ?? '',
                    $classroom->classroom_fax ?? '',
                    $classroom->classroom_email ?? '',
                    $classroom->business_hours ?? '',
                    $classroom->holiday ?? '',
                    $classroom->classroom_introduction ?? '',
                    $classroom->service_type ?? '',
                    $parentCategoryName,
                    $parentCategorySortOrder,
                    $lessonCategoryName,
                    $sortOrder,
                    $classroom->is_active ? '有効' : '無効',
                    $extractedAt,
                ];
                $lines[] = $this->toSjisCsvRow($row);
            }
        }

        return implode('', $lines);
    }

    private function buildCourseCsvContent(Collection $businesses, string $extractedAt): string
    {
        $headers = [
            'コースID',
            '事業者ID',
            '教室ID',
            'コース名',
            '料金',
            '税区分',
            '詳細',
            '対象学年',
            '期間設定',
            '状態',
            '抽出日',
        ];

        $lines = [$this->toSjisCsvRow($headers)];

        foreach ($businesses as $business) {
            assert($business instanceof BusinessInfo);
            foreach ($business->classrooms as $classroom) {
                foreach ($classroom->courses as $course) {
                    $grades = is_array($course->grades) ? $course->grades : [];
                    $gradesStr = implode(' ', array_map('strval', $grades));

                    $openDate = $course->open_date ? $course->open_date->format('Y/m/d') : '';
                    $endDate = $course->end_date ? $course->end_date->format('Y/m/d') : '';
                    if ($openDate && $endDate) {
                        $periodStr = $openDate.'～'.$endDate;
                    } elseif ($openDate) {
                        $periodStr = $openDate;
                    } elseif ($endDate) {
                        $periodStr = $endDate;
                    } else {
                        $periodStr = '';
                    }

                    $row = [
                        $course->id,
                        $business->id,
                        $classroom->id,
                        $course->course_name ?? '',
                        $course->price ?? '',
                        $course->tax_type ?? '',
                        $course->course_description ?? '',
                        $gradesStr,
                        $periodStr,
                        $course->is_active ? '有効' : '無効',
                        $extractedAt,
                    ];
                    $lines[] = $this->toSjisCsvRow($row);
                }
            }
        }

        return implode('', $lines);
    }
}
