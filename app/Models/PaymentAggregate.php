<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Subdomain $subdomain
 * @property-read BusinessInfo $businessInfo
 * @property-read ClassroomInfo $classroomInfo
 */
class PaymentAggregate extends Model
{
    protected $fillable = [
        'target_month',
        'subdomain_id',
        'business_info_id',
        'classroom_info_id',
        'application_count',
        'total_amount',
        'is_public_funds_transfer_target',
    ];

    protected function casts(): array
    {
        return [
            'target_month' => 'date',
            'application_count' => 'integer',
            'total_amount' => 'integer',
            'is_public_funds_transfer_target' => 'boolean',
        ];
    }

    /**
     * @param  Builder<PaymentAggregate>  $query
     * @return Builder<PaymentAggregate>
     */
    public function scopeForPublicFundsTransferTarget(Builder $query, bool $isTarget): Builder
    {
        return $query->where('is_public_funds_transfer_target', $isTarget);
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
     * @return BelongsTo<ClassroomInfo, $this>
     */
    public function classroomInfo(): BelongsTo
    {
        return $this->belongsTo(ClassroomInfo::class);
    }

    /**
     * 指定した申込月でスコープ
     *
     * @param  Builder<PaymentAggregate>  $query
     * @param  string  $yearMonth  YYYY-MM 形式
     * @return Builder<PaymentAggregate>
     */
    public function scopeForTargetMonth(Builder $query, string $yearMonth): Builder
    {
        $start = $yearMonth.'-01';

        return $query->whereDate('target_month', $start);
    }

    /**
     * @param  Builder<PaymentAggregate>  $query
     * @return Builder<PaymentAggregate>
     */
    public function scopeForSubdomain(Builder $query, int $subdomainId): Builder
    {
        return $query->where('subdomain_id', $subdomainId);
    }

    /**
     * @param  Builder<PaymentAggregate>  $query
     * @return Builder<PaymentAggregate>
     */
    public function scopeForBusiness(Builder $query, int $businessInfoId): Builder
    {
        return $query->where('business_info_id', $businessInfoId);
    }
}
