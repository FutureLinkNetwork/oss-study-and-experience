<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Beneficiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'subdomain_id',
        'user_id',
        'child_id',
        'certification_number',
        'guardian_name',
        'guardian_name_kana',
        'guardian_birth_date',
        'guardian_address',
        'guardian_phone',
        'guardian_email',
        'child_name',
        'child_name_kana',
        'child_birth_date',
        'elementary_school_name',
        'grade',
        'child_address',
        'child_address_same_as_guardian',
        'child_registered_in_municipality_and_receiving_scholarship',
        'survey_consent',
        'classroom_name_1',
        'classroom_location_1',
        'classroom_phone_1',
        'classroom_contact_person_1',
        'classroom_name_2',
        'classroom_location_2',
        'classroom_phone_2',
        'classroom_contact_person_2',
        'classroom_name_3',
        'classroom_location_3',
        'classroom_phone_3',
        'classroom_contact_person_3',
        'application_date',
        'certification_date',
        'status',
        'system_message',
        'disqualification_date',
        'labels',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'guardian_birth_date' => 'date',
            'child_birth_date' => 'date',
            'application_date' => 'date',
            'certification_date' => 'date',
            'disqualification_date' => 'date',
            'child_address_same_as_guardian' => 'boolean',
            'child_registered_in_municipality_and_receiving_scholarship' => 'boolean',
            'survey_consent' => 'boolean',
        ];
    }

    /**
     * 所属サブドメイン
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    /**
     * 登録ユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 紐づくクーポン
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * 申込履歴（VoucherUsage）
     */
    public function voucherUsages(): HasManyThrough
    {
        return $this->hasManyThrough(VoucherUsage::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }

    /**
     * ラベルを配列として取得
     */
    public function getLabelsArrayAttribute(): array
    {
        if (empty($this->labels)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->labels)));
    }

    /**
     * ラベルが含まれているかチェック
     */
    public function hasLabel(string $label): bool
    {
        $labels = $this->labels_array;

        return in_array($label, $labels, true);
    }

    /**
     * PDF発行済みラベルがあるかチェック
     */
    public function hasPdfIssuedLabel(): bool
    {
        return $this->hasLabel('PDF発行済み');
    }

    /**
     * 資格喪失かどうかをチェック
     */
    public function isDisqualified(): bool
    {
        return $this->status === '資格喪失';
    }

    /**
     * 利用可能なステータス一覧を取得
     */
    public static function getAvailableStatuses(): array
    {
        return [
            '決定通知書未送信',
            '決定通知書送信待ち',
            '決定通知書送信失敗',
            '決定通知書送信済',
            'ログイン認証済み',
            '資格喪失予定',
            '資格喪失',
        ];
    }
}
