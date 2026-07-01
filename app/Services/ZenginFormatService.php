<?php

namespace App\Services;

use App\Models\BusinessInfo;
use App\Models\Subdomain;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ZenginFormatService
{
    private const RECORD_LENGTH = 120;

    public function __construct(
        protected BankService $bankService
    ) {}

    /**
     * 全銀フォーマットのテキストを生成する（UTF-8 文字列）。
     * レスポンス時には Shift-JIS に変換して返す想定。
     *
     * @param  Collection<int, array{business_info_id: int, total_amount: int}>  $rows  事業者ごとの合算済み（business_info_id => total_amount）
     * @param  array<int, BusinessInfo>  $businessesById  business_info_id をキーにした BusinessInfo
     */
    public function build(
        Subdomain $subdomain,
        string $targetYearMonth,
        Collection $rows,
        array $businessesById
    ): string {
        $transferDate = $this->resolveTransferDateMmDd($subdomain, $targetYearMonth);
        $lines = [];
        $lines[] = $this->buildHeaderRecord($subdomain, $transferDate);
        $totalAmount = 0;
        foreach ($rows as $businessInfoId => $amount) {
            $business = $businessesById[$businessInfoId] ?? null;
            if (! $business) {
                continue;
            }
            $lines[] = $this->buildDataRecord($business, (int) $amount);
            $totalAmount += (int) $amount;
        }
        $lines[] = $this->buildTrailerRecord($rows->count(), $totalAmount);
        $lines[] = $this->buildEndRecord();

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * 振込指定日（その月の末日）を MMDD で返す
     */
    public function resolveTransferDateMmDd(Subdomain $subdomain, string $targetYearMonth): string
    {
        $rule = $subdomain->transfer_date_rule ?? Subdomain::TRANSFER_DATE_RULE_NEXT_MONTH_END;
        $target = Carbon::parse($targetYearMonth.'-01');
        $transferMonth = match ($rule) {
            Subdomain::TRANSFER_DATE_RULE_MONTH_AFTER_NEXT_END => $target->copy()->addMonths(2),
            default => $target->copy()->addMonth(),
        };
        $lastDay = $transferMonth->endOfMonth();

        return $lastDay->format('md');
    }

    /**
     * 1レコード: ヘッダー（依頼人情報）
     */
    private function buildHeaderRecord(Subdomain $subdomain, string $transferDateMmDd): string
    {
        $dataKind = '1';
        $typeCode = '21';
        $codeType = '0';
        $requesterCode = $this->padRight($this->toHalfWidth($subdomain->zengin_requester_code ?? ''), 10, '0');
        $requesterName = $this->padLeft($this->toHalfWidth($subdomain->zengin_requester_name ?? '', 40), 40);
		$bankCode = $this->padRight($this->toHalfWidth($subdomain->zengin_bank_code ?? ''), 4, '0');
        $bankName = $this->padLeft($this->toHalfWidth($subdomain->zengin_bank_name ?? '', 15), 15);
        $branchCode = $this->padRight($this->toHalfWidth($subdomain->zengin_branch_code ?? ''), 3, '0');
        $branchName = $this->padLeft($this->toHalfWidth($subdomain->zengin_branch_name ?? '', 15), 15);
        $accountType = $this->padRight($subdomain->zengin_account_type ?? '', 1);
        $accountNumber = $this->padRight($this->toHalfWidth($subdomain->zengin_account_number ?? ''), 7, '0');
        $dummy = str_repeat(' ', 17);

        return $this->fixLength($dataKind.$typeCode.$codeType.$requesterCode.$requesterName
            .$transferDateMmDd.$bankCode.$bankName.$branchCode.$branchName
            .$accountType.$accountNumber.$dummy);
    }

    /**
     * 2レコード: データ（振込先・金額）
     */
    private function buildDataRecord(BusinessInfo $business, int $amount): string
    {
        $dataKind = '2';
        $bankCode = $this->padRight($this->toHalfWidth($business->bank_code ?? ''), 4, '0');
        $bankName = $this->bankNameKanaForZengin($business->bank_code ?? '');
        $branchCode = $this->padRight($this->toHalfWidth($business->branch_code ?? ''), 3, '0');
        $branchName = $this->branchNameKanaForZengin($business->bank_code ?? '', $business->branch_code ?? '');
        $clearingCode = str_repeat('0', 4);
        $accountType = $this->mapAccountTypeToZengin($business->account_type);
        $accountNumber = $this->padRight($business->account_number ?? '', 7, '0');
        $recipientName = $this->recipientNameForZengin($business->account_holder_name ?? '');
        $amountStr = $this->padRight((string) $amount, 10, '0');
        $newCode = ' ';
        $customerCode1 = str_repeat(' ', 10);
        $customerCode2 = str_repeat(' ', 10);
        $transferSpec = ' ';
        $dummy = str_repeat(' ', 8);

        return $this->fixLength($dataKind.$bankCode.$bankName.$branchCode.$branchName
            .$clearingCode.$accountType.$accountNumber.$recipientName.$amountStr
            .$newCode.$customerCode1.$customerCode2.$transferSpec.$dummy);
    }

    /**
     * 8レコード: トレーラ（件数・合計金額）
     */
    private function buildTrailerRecord(int $count, int $totalAmount): string
    {
        $dataKind = '8';
        $countStr = $this->padRight((string) $count, 6, '0');
        $totalStr = $this->padRight((string) $totalAmount, 12, '0');
        $dummy = str_repeat(' ', 100);

        return $this->fixLength($dataKind.$countStr.$totalStr.$dummy);
    }

    /**
     * 9レコード: エンド
     */
    private function buildEndRecord(): string
    {
        $dataKind = '9';
        $dummy = str_repeat(' ', 118);

        return $this->fixLength($dataKind.$dummy);
    }

    private function padLeft(string $s, int $len): string
    {
        $s = mb_substr($s, 0, $len);

        return mb_strlen($s) >= $len ? $s : $s.str_repeat(' ', $len - mb_strlen($s));
    }

    private function padRight(string $s, int $len, string $pad = ' '): string
    {
        $s = mb_substr($s, 0, $len);

        return mb_strlen($s) >= $len ? $s : str_repeat($pad, $len - mb_strlen($s)).$s;
    }

    private function fixLength(string $s): string
    {
        $len = mb_strlen($s);
        if ($len > self::RECORD_LENGTH) {
            return mb_substr($s, 0, self::RECORD_LENGTH);
        }
        if ($len < self::RECORD_LENGTH) {
            return $s.str_repeat(' ', self::RECORD_LENGTH - $len);
        }

        return $s;
    }

    /**
     * 事業者の口座種別（普通/当座）を全銀の1桁にマッピング（1:普通 2:当座 4:貯蓄）
     */
    private function mapAccountTypeToZengin(?string $accountType): string
    {
        return match (trim((string) $accountType)) {
            '当座' => '2',
            '貯蓄' => '4',
            default => '1',
        };
    }

    /**
     * データレコード用：銀行名カナ（半角カナ）。15文字超の場合は15スペースを返す。変換は行わない。
     */
    private function bankNameKanaForZengin(string $bankCode): string
    {
        $kana = $this->bankService->getBankNameKana($bankCode) ?? '';

        return $this->zenginKanaField15($kana);
    }

    /**
     * データレコード用：支店名カナ（半角カナ）。15文字超の場合は15スペースを返す。変換は行わない。
     */
    private function branchNameKanaForZengin(string $bankCode, string $branchCode): string
    {
        if ($bankCode === '' || $branchCode === '') {
            return $this->padLeft('', 15);
        }
        $kana = $this->bankService->getBranchNameKana($bankCode, $branchCode) ?? '';

        return $this->zenginKanaField15($kana);
    }

    /**
     * 全銀の15桁カナ項目：半角カナのため変換不要。15文字超なら15スペース、それ以外は左詰15桁。
     */
    private function zenginKanaField15(string $s): string
    {
        if (mb_strlen($s) > 15) {
            return str_repeat(' ', 15);
        }

        return $this->padLeft($s, 15);
    }

    /**
     * 受取人名を全銀用に変換（半角カナ変換のみ、30桁左詰スペース埋め）
     */
    private function recipientNameForZengin(string $name): string
    {
        $s = mb_convert_kana($name, 'k', 'UTF-8');

        return $this->padLeft(mb_substr($s, 0, 30), 30);
    }

    /**
     * 全角を半角に変換（全銀は半角のみ可）。最大長を超える場合は切り詰める。
     */
    private function toHalfWidth(string $s, int $maxLen = 0): string
    {
        $s = mb_convert_kana($s, 'ak', 'UTF-8');
        if ($maxLen > 0) {
            $s = mb_substr($s, 0, $maxLen);
        }

        return $s;
    }
}
