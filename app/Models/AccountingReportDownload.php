<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Subdomain $subdomain
 * @property-read User|null $csvDownloadedByUser
 * @property-read User|null $pdfDownloadedByUser
 * @property-read User|null $csvNonTargetDownloadedByUser
 * @property-read User|null $pdfNonTargetDownloadedByUser
 */
class AccountingReportDownload extends Model
{
    public const CATEGORY_TARGET = 'target';

    public const CATEGORY_NON_TARGET = 'non_target';

    protected $fillable = [
        'subdomain_id',
        'target_month',
        'csv_s3_key',
        'pdf_s3_key',
        'csv_downloaded_at',
        'csv_downloaded_by_user_id',
        'pdf_downloaded_at',
        'pdf_downloaded_by_user_id',
        'csv_s3_key_non_target',
        'pdf_s3_key_non_target',
        'csv_non_target_downloaded_at',
        'csv_non_target_downloaded_by_user_id',
        'pdf_non_target_downloaded_at',
        'pdf_non_target_downloaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'target_month' => 'date',
            'csv_downloaded_at' => 'datetime',
            'pdf_downloaded_at' => 'datetime',
            'csv_non_target_downloaded_at' => 'datetime',
            'pdf_non_target_downloaded_at' => 'datetime',
        ];
    }

    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    public function csvDownloadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'csv_downloaded_by_user_id');
    }

    public function pdfDownloadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pdf_downloaded_by_user_id');
    }

    public function csvNonTargetDownloadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'csv_non_target_downloaded_by_user_id');
    }

    public function pdfNonTargetDownloadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pdf_non_target_downloaded_by_user_id');
    }

    /**
     * @return array{s3_key: string, downloaded_at: string, downloaded_by_user_id: string}
     */
    public static function columnMapForCategory(string $category, string $fileType): array
    {
        $isTarget = self::normalizeCategory($category) === self::CATEGORY_TARGET;

        if ($fileType === 'csv') {
            return $isTarget
                ? ['s3_key' => 'csv_s3_key', 'downloaded_at' => 'csv_downloaded_at', 'downloaded_by_user_id' => 'csv_downloaded_by_user_id']
                : ['s3_key' => 'csv_s3_key_non_target', 'downloaded_at' => 'csv_non_target_downloaded_at', 'downloaded_by_user_id' => 'csv_non_target_downloaded_by_user_id'];
        }

        return $isTarget
            ? ['s3_key' => 'pdf_s3_key', 'downloaded_at' => 'pdf_downloaded_at', 'downloaded_by_user_id' => 'pdf_downloaded_by_user_id']
            : ['s3_key' => 'pdf_s3_key_non_target', 'downloaded_at' => 'pdf_non_target_downloaded_at', 'downloaded_by_user_id' => 'pdf_non_target_downloaded_by_user_id'];
    }

    public static function normalizeCategory(?string $category): string
    {
        return $category === self::CATEGORY_NON_TARGET ? self::CATEGORY_NON_TARGET : self::CATEGORY_TARGET;
    }

    /**
     * @param  Builder<AccountingReportDownload>  $query
     * @return Builder<AccountingReportDownload>
     */
    public function scopeForSubdomain(Builder $query, int $subdomainId): Builder
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    /**
     * CSV または PDF（対象・対象外）のいずれかが未ダウンロードのレコードに限定
     *
     * @param  Builder<AccountingReportDownload>  $query
     * @return Builder<AccountingReportDownload>
     */
    public function scopeUndownloaded(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where(function (Builder $q2) {
                $q2->whereNotNull('csv_s3_key')->whereNull('csv_downloaded_at');
            })->orWhere(function (Builder $q2) {
                $q2->whereNotNull('pdf_s3_key')->whereNull('pdf_downloaded_at');
            })->orWhere(function (Builder $q2) {
                $q2->whereNotNull('csv_s3_key_non_target')->whereNull('csv_non_target_downloaded_at');
            })->orWhere(function (Builder $q2) {
                $q2->whereNotNull('pdf_s3_key_non_target')->whereNull('pdf_non_target_downloaded_at');
            });
        });
    }
}
