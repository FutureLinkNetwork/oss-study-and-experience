<?php

namespace App\Services;

use App\Enums\InquiryType;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Builder;

class InquiryCsvExportService
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
            '問い合わせ日時',
            '種別',
            '利用者／事業者名',
            'メールアドレス',
            '電話番号',
            'お問い合わせ内容',
            'ステータス',
            '備考',
        ];
    }

    /**
     * 指定ストリームにCSVを出力する（ヘッダー＋データ行）。Shift_JISでエンコード。
     * 0件の場合はヘッダー＋「0件」旨の1行を出力する。
     *
     * @param  resource  $stream
     */
    public function streamCsvTo($stream, Builder $inquiryQuery): void
    {
        $headers = $this->getHeaders();
        $sjisHeaders = array_map(fn (string $h) => mb_convert_encoding($h, 'SJIS-win', 'UTF-8'), $headers);
        fputcsv($stream, $sjisHeaders);

        $query = (clone $inquiryQuery)
            ->with(['user.beneficiary', 'user.businessInfos'])
            ->orderBy('created_at', 'asc');
        $count = 0;

        foreach ($query->cursor() as $inquiry) {
            assert($inquiry instanceof Inquiry);
            $count++;
            $row = $this->buildRow($inquiry, $count);
            $sjisRow = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8'), $row);
            fputcsv($stream, $sjisRow);
        }

        if ($count === 0) {
            $zeroRow = ['0件', '', '', '', '', '', '', '', ''];
            $sjisZeroRow = array_map(fn (string $cell) => mb_convert_encoding($cell, 'SJIS-win', 'UTF-8'), $zeroRow);
            fputcsv($stream, $sjisZeroRow);
        }
    }

    /**
     * 1行分の配列を組み立て（user_id 経由で名前・メール・電話を取得）
     *
     * @return array<int, string|int>
     */
    private function buildRow(Inquiry $inquiry, int $no): array
    {
        $name = $inquiry->sender_name;
        $email = '';
        $phone = '';

        $user = $inquiry->user;
        if ($user) {
            if ($inquiry->inquiry_type === InquiryType::User) {
                $email = $user->email ?? '';
                $beneficiary = $user->beneficiary;
                $phone = $beneficiary?->guardian_phone ?? '';
            } else {
                $businessInfo = $user->businessInfos->first();
                $email = $businessInfo?->email ?? $user->email ?? '';
                $phone = $businessInfo ? ($businessInfo->phone ?? $businessInfo->contact_phone ?? '') : '';
            }
        }

        return [
            $no,
            $inquiry->created_at ? $inquiry->created_at->format('Y-m-d H:i') : '',
            $inquiry->inquiry_type->label(),
            $name,
            $email,
            $phone,
            $inquiry->content ?? '',
            $inquiry->status->label(),
            $inquiry->remarks ?? '',
        ];
    }
}
