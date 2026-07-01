<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '未処理',
            self::InProgress => '対応中',
            self::Completed => '処理済み',
        };
    }

    /**
     * 全ケースを配列で取得
     *
     * @return array<self>
     */
    public static function all(): array
    {
        return [
            self::Pending,
            self::InProgress,
            self::Completed,
        ];
    }
}
