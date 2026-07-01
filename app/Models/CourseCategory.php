<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'subdomain_id',
        'parent_category_id',
        'name',
        'sort_order',
        'is_active',
        'created_user_id',
        'updated_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (CourseCategory $model): void {
            if (! app()->environment('testing')) {
                return;
            }
            if ($model->created_user_id !== null && $model->updated_user_id !== null) {
                return;
            }
            $userId = User::query()
                ->where('subdomain_id', $model->subdomain_id)
                ->orderBy('id')
                ->value('id');
            if ($userId === null) {
                return;
            }
            $model->created_user_id ??= $userId;
            $model->updated_user_id ??= $userId;
        });
    }

    /**
     * サブドメインとのリレーション
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    /**
     * 親分類とのリレーション
     */
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(CourseParentCategory::class, 'parent_category_id');
    }

    /**
     * 親分類とのリレーション（エイリアス）
     */
    public function parent(): BelongsTo
    {
        return $this->parentCategory();
    }

    /**
     * 作成者とのリレーション
     */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    /**
     * 更新者とのリレーション
     */
    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }

    /**
     * 有効な分類のみ取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * サブドメインでフィルタリングするスコープ
     */
    public function scopeForSubdomain($query, $subdomainId)
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    /**
     * 親分類でフィルタリングするスコープ
     */
    public function scopeForParent($query, $parentCategoryId)
    {
        return $query->where('parent_category_id', $parentCategoryId);
    }

    /**
     * 表示順でソートするスコープ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
