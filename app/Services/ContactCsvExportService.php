<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

class ContactCsvExportService
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
            '名前',
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
    public function streamCsvTo($stream, Builder $contactQuery): void
    {
        $headers = $this->getHeaders();
        $sjisHeaders = array_map(fn (string $h) => mb_convert_encoding($h, 'SJIS-win', 'UTF-8'), $headers);
        fputcsv($stream, $sjisHeaders);

        $query = (clone $contactQuery)->orderBy('created_at', 'asc');
        $count = 0;

        foreach ($query->cursor() as $contact) {
            assert($contact instanceof Contact);
            $count++;
            $row = $this->buildRow($contact, $count);
            $sjisRow = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8'), $row);
            fputcsv($stream, $sjisRow);
        }

        if ($count === 0) {
            $zeroRow = ['0件', '', '', '', '', '', '', ''];
            $sjisZeroRow = array_map(fn (string $cell) => mb_convert_encoding($cell, 'SJIS-win', 'UTF-8'), $zeroRow);
            fputcsv($stream, $sjisZeroRow);
        }
    }

    /**
     * is_confirmed をステータスラベルに変換
     */
    public static function statusLabel(int|bool|null $isConfirmed): string
    {
        $v = $isConfirmed === null ? 0 : (int) $isConfirmed;

        return match ($v) {
            1 => '確認中',
            2 => '対応済',
            default => '未確認',
        };
    }

    /**
     * 1行分の配列を組み立て
     *
     * @return array<int, string|int>
     */
    private function buildRow(Contact $contact, int $no): array
    {
        return [
            $no,
            $contact->created_at ? $contact->created_at->format('Y-m-d H:i') : '',
            $contact->name ?? '',
            $contact->email ?? '',
            $contact->phone ?? '',
            $contact->content ?? '',
            self::statusLabel($contact->is_confirmed),
            $contact->remarks ?? '',
        ];
    }
}
