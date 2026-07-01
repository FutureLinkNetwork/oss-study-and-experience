<?php

namespace App\Enums;

enum NoticeTarget: string
{
    case User = 'user';
    case Business = 'business';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::User => '対象者',
            self::Business => '事業者',
        };
    }

    /**
     * CSSクラス名を取得
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::User => 'li-label_user',
            self::Business => 'li-label_partner',
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
