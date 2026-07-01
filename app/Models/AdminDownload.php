<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Subdomain $subdomain
 */
class AdminDownload extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'admin_downloads';

    protected $fillable = [
        'subdomain_id',
        'exported_at',
        'summary',
        's3_key',
        'download_type',
    ];

    protected function casts(): array
    {
        return [
            'exported_at' => 'datetime',
        ];
    }

    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    /**
     * @param  Builder<AdminDownload>  $query
     * @return Builder<AdminDownload>
     */
    public function scopeForSubdomain(Builder $query, int $subdomainId): Builder
    {
        return $query->where('subdomain_id', $subdomainId);
    }
}
