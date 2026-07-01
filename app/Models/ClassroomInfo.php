<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassroomInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_info_id',
        'classroom_name',
        'classroom_name_kana',
        'classroom_representative_name',
        'classroom_representative_name_kana',
        'classroom_postal_code',
        'classroom_prefecture',
        'classroom_city',
        'classroom_address1',
        'classroom_building_name',
        'classroom_latitude',
        'classroom_longitude',
        'use_map',
        'classroom_phone',
        'classroom_fax',
        'classroom_email',
        'business_hours',
        'holiday',
        'classroom_introduction',
        'service_type',
        'lesson_category',
        'lesson_category_other',
        'apply',
        'is_active',
        'disallow_amount_specified_usage',
        'qr_only',
        'created_user',
        'updated_user',
        // 教室画像管理フィールド
        'classroom_image_original_filename',
        'classroom_image_s3_key',
        'classroom_image_file_size',
        'classroom_image_mime_type',
        'classroom_image_thumbnail_s3_key',
        'classroom_image_medium_s3_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_map' => 'boolean',
        'disallow_amount_specified_usage' => 'boolean',
        'qr_only' => 'boolean',
        'lesson_category' => 'integer',
    ];

    /**
     * アクティブな教室のみを取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * 利用者マイページで金額指定利用が可能か
     */
    public function allowsAmountSpecifiedUsage(): bool
    {
        return ! $this->disallow_amount_specified_usage;
    }

    /**
     * 教室が属する事業者
     */
    public function businessInfo(): BelongsTo
    {
        return $this->belongsTo(BusinessInfo::class);
    }

    /**
     * 教室に属するコース情報
     */
    public function courses(): HasMany
    {
        return $this->hasMany(CourseInfo::class);
    }

    /**
     * 習い事の種別（子分類）
     * lesson_categoryが-1の場合はnullを返す（その他の場合）
     */
    public function lessonCategoryInfo(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'lesson_category');
    }

    /**
     * 教室を無効化し、関連するコースも無効化
     */
    public function deactivate(): void
    {
        // 自身を無効化
        $this->update(['is_active' => 0]);

        // 関連するコースを無効化
        $this->courses()->update(['is_active' => 0]);
    }

    /**
     * 教室画像が存在するかチェック
     */
    public function hasClassroomImage(): bool
    {
        return ! empty($this->classroom_image_s3_key);
    }

    /**
     * 教室画像のダウンロードURL生成（管理者用）
     */
    public function getClassroomImageDownloadUrl(string $size = 'original'): ?string
    {
        if (! $this->hasClassroomImage()) {
            return null;
        }

        return route('admin.business.classroom-image.download', [
            'business' => $this->business_info_id,
            'classroom' => $this->id,
            'size' => $size,
        ]);
    }

    /**
     * 教室画像のダウンロードURL生成（事業者用）
     */
    public function getBusinessClassroomImageDownloadUrl(string $size = 'original'): ?string
    {
        if (! $this->hasClassroomImage()) {
            return null;
        }

        return route('business.classrooms.image.download', [
            'classroom' => $this->id,
            'size' => $size,
        ]);
    }

    /**
     * 指定されたサイズの画像S3キーを取得
     */
    public function getImageS3Key(string $size = 'original'): ?string
    {
        return match ($size) {
            'original' => $this->classroom_image_s3_key,
            'medium' => $this->classroom_image_medium_s3_key ?? $this->classroom_image_s3_key,
            'thumbnail' => $this->classroom_image_thumbnail_s3_key ?? $this->classroom_image_s3_key,
            default => null,
        };
    }

    /**
     * 教室画像のダウンロードURL生成（一般公開用）
     */
    public function getPublicClassroomImageDownloadUrl(string $size = 'original'): ?string
    {
        if (! $this->hasClassroomImage()) {
            return null;
        }

        return route('course.classroom.image.download', [
            'classroom' => $this->id,
            'size' => $size,
        ]);
    }
}
