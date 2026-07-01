<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_global',
        'level',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
        'permissions' => 'array',
    ];

    /**
     * このロールを持つユーザー
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * グローバル権限のスコープ
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * サブドメイン権限のスコープ
     */
    public function scopeSubdomain($query)
    {
        return $query->where('is_global', false);
    }

    /**
     * 有効なロールのスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * レベルでソート
     */
    public function scopeOrderByLevel($query, string $direction = 'desc')
    {
        return $query->orderBy('level', $direction);
    }

    /**
     * 権限チェック
     */
    public function hasPermission(string $resource, string $action): bool
    {
        $permissions = $this->permissions ?? [];

        if (! isset($permissions[$resource])) {
            return false;
        }

        return in_array($action, $permissions[$resource]);
    }

    /**
     * 指定したレベル以上かチェック
     */
    public function hasLevelOrAbove(int $level): bool
    {
        return $this->level >= $level;
    }
}
