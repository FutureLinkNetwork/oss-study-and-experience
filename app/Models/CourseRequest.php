<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRequest extends Model
{
    protected $fillable = [
        'classroom_name',
        'address',
        'phone',
        'requester_name',
        'requester_email',
        'requester_phone',
        'ip_address',
        'is_confirmed',
        'remarks',
        'updated_user_id',
        'subdomain_id',
    ];

    /**
     * リレーション: 更新者
     */
    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }

    /**
     * リレーション: サブドメイン
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    /**
     * スコープ: 確認済みのリクエスト
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('is_confirmed', true);
    }

    /**
     * スコープ: 未確認のリクエスト
     */
    public function scopeUnconfirmed(Builder $query): Builder
    {
        return $query->where('is_confirmed', false);
    }
}
