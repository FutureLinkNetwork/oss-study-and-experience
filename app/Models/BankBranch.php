<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankBranch extends Model
{
    use HasFactory;

    protected $table = 'bank_branches';

    protected $fillable = [
        'management_code',
        'bank_code',
        'bank_name',
        'bank_name_kana',
        'branch_code',
        'branch_name',
        'branch_name_kana',
    ];

    /**
     * 銀行コードごとに銀行一覧を取得
     */
    public static function getBanks()
    {
        return self::select('bank_code', 'bank_name', 'bank_name_kana')
            ->groupBy('bank_code', 'bank_name', 'bank_name_kana')
            ->orderBy('bank_code')
            ->get();
    }

    /**
     * 指定された銀行コードの支店一覧を取得
     */
    public static function getBranches($bankCode)
    {
        return self::select('branch_code', 'branch_name', 'branch_name_kana')
            ->where('bank_code', $bankCode)
            ->orderBy('branch_code')
            ->get();
    }
}