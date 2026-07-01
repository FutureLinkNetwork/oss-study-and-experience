<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subdomain_id',
        'role_id',
        'login_id',
        'name',
        'display_name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'remember_token',
        'password_reset_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_reset_token_expires_at' => 'datetime',
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
     * ユーザーロール
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * ユーザーに紐づく利用者（Beneficiary）情報（subdomain_user の場合）
     */
    public function beneficiary(): HasOne
    {
        return $this->hasOne(Beneficiary::class);
    }

    /**
     * ユーザーの事業者情報（逆参照）
     */
    public function businessInfos()
    {
        return $this->hasMany(BusinessInfo::class);
    }

    /**
     * アクティブなユーザーのスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * サブドメインでフィルタリング
     */
    public function scopeInSubdomain($query, $subdomainId)
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    /**
     * ロールでフィルタリング
     */
    public function scopeWithRole($query, $roleName)
    {
        return $query->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * ログインIDでユーザーを検索（サブドメイン内）
     */
    public function scopeByLoginId($query, $loginId, $subdomainId)
    {
        return $query->where('login_id', $loginId)
            ->where('subdomain_id', $subdomainId);
    }

    /**
     * 権限チェック
     */
    public function hasPermission(string $resource, string $action): bool
    {
        return $this->role?->hasPermission($resource, $action) ?? false;
    }

    /**
     * グローバル権限を持つかチェック
     */
    public function isGlobalAdmin(): bool
    {
        return $this->role?->is_global ?? false;
    }

    /**
     * 指定したレベル以上の権限を持つかチェック
     */
    public function hasLevelOrAbove(int $level): bool
    {
        return $this->role?->hasLevelOrAbove($level) ?? false;
    }

    /**
     * 最終ログイン情報を更新
     */
    public function updateLastLogin(string $ip): void
    {
        Log::info('updateLastLogin called', [
            'user_id' => $this->id,
            'login_id' => $this->login_id,
            'ip' => $ip,
            'current_last_login_at' => $this->last_login_at,
            'current_last_login_ip' => $this->last_login_ip,
        ]);

        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        Log::info('updateLastLogin completed', [
            'user_id' => $this->id,
            'new_last_login_at' => $this->fresh()->last_login_at,
            'new_last_login_ip' => $this->fresh()->last_login_ip,
        ]);
    }
}
