<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id',
        'subdomain_id',
        'voucher_number',
        'issue_date',
        'expiry_date',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'amount' => 'integer',
        ];
    }

    /**
     * 紐づく利用者
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    /**
     * 所属サブドメイン
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }
}
