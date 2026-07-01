<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_info_id',
        'classroom_info_id',
        'course_name',
        'course_description',
        'grades',
        'price',
        'tax_type',
        'open_date',
        'end_date',
        'is_active',
        'created_user',
        'updated_user',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
        'open_date' => 'date',
        'end_date' => 'date',
        'grades' => 'array',
    ];

    /**
     * アクティブなコースのみを取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * コースが属する事業者
     */
    public function businessInfo(): BelongsTo
    {
        return $this->belongsTo(BusinessInfo::class);
    }

    /**
     * コースが属する教室
     */
    public function classroomInfo(): BelongsTo
    {
        return $this->belongsTo(ClassroomInfo::class);
    }

    /**
     * コースを作成したユーザー
     */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    /**
     * コースを更新したユーザー
     */
    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user');
    }

    /**
     * コースを無効化
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => 0]);
    }
}