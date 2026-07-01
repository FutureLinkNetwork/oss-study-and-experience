<?php

namespace App\Enums;

enum CouponNotificationFrequency: string
{
    case Immediate = 'immediate';
    case Daily = 'daily';
    case None = 'none';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Immediate => '都度',
            self::Daily => '一日1回（9時頃）',
            self::None => '通知しない',
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
            self::Immediate,
            self::Daily,
            self::None,
        ];
    }
}
