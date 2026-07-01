<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subdomain_id',
        'business_info_id',
        'classroom_info_id',
        'course_info_id',
        'amount',
        'used_at',
        'memo',
        'business_memo',
        'admin_correction_memo',
        'admin_corrected_at',
        'qr_flag',
        'is_cancelled',
        'cancelled_by_user_id',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'used_at' => 'datetime',
            'qr_flag' => 'boolean',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
            'admin_corrected_at' => 'datetime',
        ];
    }

    /**
     * 利用ユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 利用コースの事業者
     */
    public function businessInfo(): BelongsTo
    {
        return $this->belongsTo(BusinessInfo::class);
    }

    /**
     * 利用コースの教室
     */
    public function classroomInfo(): BelongsTo
    {
        return $this->belongsTo(ClassroomInfo::class);
    }

    /**
     * 利用コース
     */
    public function courseInfo(): BelongsTo
    {
        return $this->belongsTo(CourseInfo::class);
    }

    /**
     * キャンセルしたユーザー
     */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }
}
