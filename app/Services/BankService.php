<?php

namespace App\Services;

use App\Models\BankBranch;

class BankService
{
    /**
     * 半角カナを全角カナに変換
     */
    private function convertKanaToZenkaku(string $kana): string
    {
        return mb_convert_kana($kana, 'HV', 'UTF-8');
    }

    /**
     * 銀行一覧を取得（表示用にフォーマット）
     */
    public function getBanksForSelect(): array
    {
        $banks = BankBranch::getBanks();

        return $banks->map(function ($bank) {
            return [
                'value' => $bank->bank_code,
                'text' => $bank->bank_name.'（'.$this->convertKanaToZenkaku($bank->bank_name_kana).'）',
                'bank_name' => $bank->bank_name,
                'bank_name_kana' => $bank->bank_name_kana,
            ];
        })->toArray();
    }

    /**
     * 指定された銀行の支店一覧を取得（表示用にフォーマット）
     */
    public function getBranchesForSelect(string $bankCode): array
    {
        $branches = BankBranch::getBranches($bankCode);

        return $branches->map(function ($branch) {
            return [
                'value' => $branch->branch_code,
                'text' => $branch->branch_name.'（'.$this->convertKanaToZenkaku($branch->branch_name_kana).'）',
                'branch_name' => $branch->branch_name,
                'branch_name_kana' => $branch->branch_name_kana,
            ];
        })->toArray();
    }

    /**
     * 銀行コードから銀行名を取得
     */
    public function getBankName(string $bankCode): ?string
    {
        $bank = BankBranch::where('bank_code', $bankCode)->first();

        return $bank ? $bank->bank_name : null;
    }

    /**
     * 銀行コードから銀行名カナを取得（全銀データレコード用・半角カナ）
     */
    public function getBankNameKana(string $bankCode): ?string
    {
        $bank = BankBranch::where('bank_code', $bankCode)->first();

        return $bank ? $bank->bank_name_kana : null;
    }

    /**
     * 銀行コードと支店コードから支店名を取得
     */
    public function getBranchName(string $bankCode, string $branchCode): ?string
    {
        $branch = BankBranch::where('bank_code', $bankCode)
            ->where('branch_code', $branchCode)
            ->first();

        return $branch ? $branch->branch_name : null;
    }

    /**
     * 銀行コードと支店コードから支店名カナを取得（全銀データレコード用・半角カナ）
     */
    public function getBranchNameKana(string $bankCode, string $branchCode): ?string
    {
        $branch = BankBranch::where('bank_code', $bankCode)
            ->where('branch_code', $branchCode)
            ->first();

        return $branch ? $branch->branch_name_kana : null;
    }
}
