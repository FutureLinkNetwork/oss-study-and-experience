<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    /** @use HasFactory<\Database\Factories\InquiryFactory> */
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'subdomain_id',
        'user_id',
        'inquiry_type',
        'content',
        'status',
        'remarks',
        'created_user_id',
        'updated_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inquiry_type' => InquiryType::class,
            'status' => InquiryStatus::class,
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
     * 送信者（利用者 or 事業者）
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 登録者（送信者と同値）
     */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    /**
     * 更新した管理者
     */
    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }

    /**
     * サブドメインでフィルタ
     */
    public function scopeForSubdomain(Builder $query, int $subdomainId): Builder
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    /**
     * 送信者表示名（利用者＝保護者名、事業者＝事業者名）
     */
    public function getSenderNameAttribute(): string
    {
        $user = $this->user;
        if (! $user) {
            return '—';
        }
        if ($this->inquiry_type === InquiryType::User) {
            $beneficiary = $user->beneficiary;

            return $beneficiary?->guardian_name ?? $user->display_name ?? $user->name ?? $user->login_id ?? '—';
        }
        $business = $user->businessInfos()->first();

        return $business?->business_name ?? $user->display_name ?? $user->name ?? $user->login_id ?? '—';
    }
}
