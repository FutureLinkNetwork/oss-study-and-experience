<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subdomain extends Model
{
    use HasFactory;

    /** 振込日ルール: 翌月末 */
    public const TRANSFER_DATE_RULE_NEXT_MONTH_END = 'next_month_end';

    /** 振込日ルール: 翌々月末 */
    public const TRANSFER_DATE_RULE_MONTH_AFTER_NEXT_END = 'month_after_next_end';

    protected $fillable = [
        'subdomain',
        'name',
        'system_name',
        'description',
        'voucher_amount',
        'voucher_expiry',
        'voucher_publish_date',
        'tax_rate',
        'is_active',
        'settings',
        'latitude',
        'longitude',
        'postal_code',
        'address',
        'phone',
        'fax',
        'transfer_date_rule',
        'zengin_requester_code',
        'zengin_requester_name',
        'zengin_bank_code',
        'zengin_bank_name',
        'zengin_branch_code',
        'zengin_branch_name',
        'zengin_account_type',
        'zengin_account_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'tax_rate' => 'decimal:2',
    ];

    /**
     * このサブドメインに属するユーザー
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * アクティブなサブドメインのスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * サブドメイン名でサブドメインを検索
     */
    public function scopeBySubdomain($query, string $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    /**
     * 設定値を取得
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * 設定値を設定
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * 学年リストを取得
     *
     * @return array<string>
     */
    public function getGrades(): array
    {
        return $this->getSetting('grades', []);
    }

    /**
     * 全銀フォーマット用の依頼人情報がすべて設定されているか
     */
    public function hasZenginHeaderConfigured(): bool
    {
        $fields = [
            'zengin_requester_code',
            'zengin_requester_name',
            'zengin_bank_code',
            'zengin_bank_name',
            'zengin_branch_code',
            'zengin_branch_name',
            'zengin_account_type',
            'zengin_account_number',
        ];

        foreach ($fields as $field) {
            if (trim((string) $this->{$field}) === '') {
                return false;
            }
        }

        return true;
    }
}
