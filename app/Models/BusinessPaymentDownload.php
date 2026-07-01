<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Subdomain $subdomain
 * @property-read BusinessInfo $businessInfo
 */
class BusinessPaymentDownload extends Model
{
    protected $fillable = [
        'subdomain_id',
        'business_info_id',
        'target_month',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'target_month' => 'date',
            'downloaded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Subdomain, $this>
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    /**
     * @return BelongsTo<BusinessInfo, $this>
     */
    public function businessInfo(): BelongsTo
    {
        return $this->belongsTo(BusinessInfo::class);
    }

    /**
     * @param  Builder<BusinessPaymentDownload>  $query
     * @return Builder<BusinessPaymentDownload>
     */
    public function scopeForSubdomain(Builder $query, int $subdomainId): Builder
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    /**
     * @param  Builder<BusinessPaymentDownload>  $query
     * @return Builder<BusinessPaymentDownload>
     */
    public function scopeForBusiness(Builder $query, int $businessInfoId): Builder
    {
        return $query->where('business_info_id', $businessInfoId);
    }

    /**
     * @param  Builder<BusinessPaymentDownload>  $query
     * @param  string  $yearMonth  YYYY-MM 形式
     * @return Builder<BusinessPaymentDownload>
     */
    public function scopeForTargetMonth(Builder $query, string $yearMonth): Builder
    {
        $start = $yearMonth.'-01';

        return $query->whereDate('target_month', $start);
    }

    /**
     * 未ダウンロード（downloaded_at が null）のレコードに限定
     *
     * @param  Builder<BusinessPaymentDownload>  $query
     * @return Builder<BusinessPaymentDownload>
     */
    public function scopeUndownloaded(Builder $query): Builder
    {
        return $query->whereNull('downloaded_at');
    }
}
