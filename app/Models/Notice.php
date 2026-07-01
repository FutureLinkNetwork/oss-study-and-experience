<?php

namespace App\Models;

use App\Enums\NoticeTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    protected $fillable = [
        'subdomain_id',
        'title',
        'content',
        'notice_date',
        'publish_start_at',
        'publish_end_at',
        'address',
        'latitude',
        'longitude',
        'link_url',
        'attachment_s3_key',
        'attachment_original_filename',
        'attachment_file_size',
        'attachment_mime_type',
        'show_on_public',
        'show_on_user_dashboard',
        'show_on_business_dashboard',
        'is_deleted',
        'created_user',
        'updated_user',
    ];

    protected $casts = [
        'notice_date' => 'date',
        'publish_start_at' => 'datetime',
        'publish_end_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'show_on_public' => 'boolean',
        'show_on_user_dashboard' => 'boolean',
        'show_on_business_dashboard' => 'boolean',
        'is_deleted' => 'boolean',
        'attachment_file_size' => 'integer',
    ];

    /**
     * 添付ファイルがあるか
     */
    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_s3_key);
    }

    /**
     * 添付ファイルの MIME タイプを取得（ダウンロード用）
     */
    public function getAttachmentMimeType(): ?string
    {
        return $this->attachment_mime_type ?? 'application/octet-stream';
    }

    /**
     * リレーション: サブドメイン
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    /**
     * リレーション: 作成者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    /**
     * リレーション: 更新者
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_user');
    }

    /**
     * スコープ: 削除されていないレコード
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }

    /**
     * スコープ: 公開されているお知らせ
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('publish_start_at')
                ->orWhere('publish_start_at', '<=', now());
        })
            ->where(function ($q) {
                $q->whereNull('publish_end_at')
                    ->orWhere('publish_end_at', '>=', now());
            });
    }

    /**
     * スコープ: 特定のサブドメインのお知らせ
     */
    public function scopeForSubdomain(Builder $query, int $subdomainId): Builder
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    /**
     * スコープ: パブリック表示用
     */
    public function scopePublicDisplay(Builder $query): Builder
    {
        return $query->where('show_on_public', true);
    }

    /**
     * スコープ: ユーザーダッシュボード表示用
     */
    public function scopeUserDashboard(Builder $query): Builder
    {
        return $query->where('show_on_user_dashboard', true);
    }

    /**
     * スコープ: 事業者ダッシュボード表示用
     */
    public function scopeBusinessDashboard(Builder $query): Builder
    {
        return $query->where('show_on_business_dashboard', true);
    }

    /**
     * 位置情報を持っているかチェック
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * 地図用の座標配列を取得
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (! $this->hasLocation()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * 現在公開中かチェック
     */
    public function isCurrentlyPublished(): bool
    {
        $now = now();

        if ($this->publish_start_at && $this->publish_start_at > $now) {
            return false;
        }

        if ($this->publish_end_at && $this->publish_end_at < $now) {
            return false;
        }

        return true;
    }

    /**
     * 公開期間の表示文字列を取得
     */
    public function getPublishPeriodDisplayAttribute(): string
    {
        if (! $this->publish_start_at && ! $this->publish_end_at) {
            return '無期限';
        }

        if ($this->publish_start_at && ! $this->publish_end_at) {
            return $this->publish_start_at->format('Y年m月d日').'から無期限';
        }

        if (! $this->publish_start_at && $this->publish_end_at) {
            return $this->publish_end_at->format('Y年m月d日').'まで';
        }

        return $this->publish_start_at->format('Y年m月d日').'〜'.$this->publish_end_at->format('Y年m月d日');
    }

    /**
     * お知らせ日付の表示文字列を取得（Y.m.d形式）
     */
    public function getNoticeDateDisplayAttribute(): string
    {
        return $this->notice_date->format('Y.m.d');
    }

    /**
     * 表示対象のラベル配列を取得
     *
     * @return array<int, array{target: NoticeTarget, label: string, class: string}>
     */
    public function getTargetLabelsAttribute(): array
    {
        $labels = [];

        if ($this->show_on_user_dashboard) {
            $labels[] = [
                'target' => NoticeTarget::User,
                'label' => NoticeTarget::User->label(),
                'class' => NoticeTarget::User->cssClass(),
            ];
        }

        if ($this->show_on_business_dashboard) {
            $labels[] = [
                'target' => NoticeTarget::Business,
                'label' => NoticeTarget::Business->label(),
                'class' => NoticeTarget::Business->cssClass(),
            ];
        }

        return $labels;
    }

    /**
     * パブリック表示用の公開お知らせを取得
     *
     * @param  int  $subdomainId  サブドメインID
     * @param  int  $limit  取得件数（デフォルト10件）
     * @param  int  $offset  オフセット（デフォルト0件）
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPublicNotices(int $subdomainId, int $limit = 10, int $offset = 0)
    {
        $query = static::query()
            ->notDeleted()
            ->published()
            ->publicDisplay()
            ->forSubdomain($subdomainId)
            ->orderBy('id', 'desc');

        return $query->offset($offset)->limit($limit)->get();
    }

    /**
     * パブリック表示用の公開お知らせの総件数を取得
     *
     * @param  int  $subdomainId  サブドメインID
     */
    public static function getPublicNoticesCount(int $subdomainId): int
    {
        return static::query()
            ->notDeleted()
            ->published()
            ->publicDisplay()
            ->forSubdomain($subdomainId)
            ->count();
    }

    /**
     * 事業者向けお知らせデータを取得
     *
     * @param  int  $subdomainId  サブドメインID
     * @param  int  $limit  取得件数（デフォルト10件）
     * @param  int  $offset  オフセット（デフォルト0件）
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBusinessNotices(int $subdomainId, int $limit = 10, int $offset = 0)
    {
        $query = static::query()
            ->notDeleted()
            ->published()
            ->businessDashboard()
            ->forSubdomain($subdomainId)
            ->orderBy('notice_date', 'desc');

        return $query->offset($offset)->limit($limit)->get();
    }

    /**
     * 事業者向けお知らせデータの総件数を取得
     *
     * @param  int  $subdomainId  サブドメインID
     */
    public static function getBusinessNoticesCount(int $subdomainId): int
    {
        return static::query()
            ->notDeleted()
            ->published()
            ->businessDashboard()
            ->forSubdomain($subdomainId)
            ->count();
    }
}
