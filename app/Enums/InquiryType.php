<?php

namespace App\Enums;

enum InquiryType: string
{
    case User = 'user';
    case Business = 'business';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::User => '利用者',
            self::Business => '事業者',
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
            self::User,
            self::Business,
        ];
    }
}
