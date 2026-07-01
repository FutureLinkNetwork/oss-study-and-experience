<?php

namespace App\Services;

use App\Models\BusinessInfo;
use App\Models\Subdomain;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use setasign\Fpdi\Tcpdf\Fpdi;

class AccountingInvoicePdfService
{
    private const ROWS_PER_PAGE = 38;

    public function __construct(
        protected BankService $bankService
    ) {}

    /**
     * 指定サブドメイン・対象月の請求書PDFを生成（事業者ごとに1ページ目請求書＋2ページ目以降内訳を連結）
     *
     * @return string 一時ファイルパス
     */
    public function generateForSubdomain(Subdomain $subdomain, string $targetYearMonth, ?bool $onlyPublicFundsTransferTarget = null): string
    {
        $monthStart = Carbon::parse($targetYearMonth.'-01')->startOfMonth()->startOfDay();
        $monthEnd = Carbon::parse($targetYearMonth.'-01')->endOfMonth()->endOfDay();

        $usagesQuery = VoucherUsage::query()
            ->where('subdomain_id', $subdomain->id)
            ->where('is_cancelled', false)
            ->whereBetween('used_at', [$monthStart, $monthEnd]);

        if ($onlyPublicFundsTransferTarget !== null) {
            $usagesQuery->whereHas('businessInfo', function ($query) use ($onlyPublicFundsTransferTarget) {
                $query->where('is_public_funds_transfer_target', $onlyPublicFundsTransferTarget);
            });
        }

        $usages = $usagesQuery
            ->with(['businessInfo', 'classroomInfo', 'courseInfo', 'user.beneficiary'])
            ->orderBy('business_info_id')
            ->orderBy('used_at')
            ->get();

        $byBusiness = $usages->groupBy('business_info_id');
        $businessIds = $byBusiness->keys()->filter()->values()->all();
        $businesses = BusinessInfo::query()
            ->whereIn('id', $businessIds)
            ->get()
            ->keyBy('id');

        $pdf = new Fpdi;
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetFont('kozgopromedium', '', 9);

        $targetMonth = Carbon::parse($targetYearMonth.'-01');
        $targetMonthLabel = $targetMonth->format('Y年n月');
        $reiwa = $targetMonth->format('Y') - 2018;

        foreach ($businessIds as $businessId) {
            $business = $businesses->get($businessId);
            if (! $business instanceof BusinessInfo) {
                continue;
            }
            $businessUsages = $byBusiness->get($businessId, collect());
            $totalAmount = $businessUsages->sum('amount');

            $businessPageNum = 0;
            $this->addInvoicePage($pdf, $business, $subdomain, $targetMonthLabel, $reiwa, $totalAmount, $businessPageNum);
            $this->addBreakdownPages($pdf, $business, $businessUsages, $targetMonthLabel, $businessPageNum);
        }

        if ($pdf->PageNo() === 0) {
            $pdf->AddPage();
            $pdf->SetXY(20, 30);
            $pdf->Write(0, '該当する利用データがありません。');
        }

        $tempPath = sys_get_temp_dir().'/accounting_invoice_'.uniqid().'.pdf';
        $tempDir = dirname($tempPath);
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $pdf->Output($tempPath, 'F');

        return $tempPath;
    }

    private function addInvoicePage(Fpdi $pdf, BusinessInfo $business, Subdomain $subdomain, string $targetMonthLabel, int $reiwa, int $totalAmount, int &$businessPageNum): void
    {
        $pdf->AddPage();
        $businessPageNum++;
        $this->drawPageNumber($pdf, $businessPageNum);
        $y = 15;

        $issueDate = Carbon::today();
        $pdf->SetXY(150, $y);
        $pdf->SetFont('kozgopromedium', '', 12);
        $pdf->Write(0, "令和 {$reiwa} 年 {$issueDate->format('n')} 月 {$issueDate->format('j')} 日");
        $y += 12;

        $pdf->SetXY(20, $y);
        $pdf->Write(0, '市長 様');
        $pdf->SetXY(110, $y);
        $pdf->Write(0, '（請求者）');
        $y += 8;

        $address = $this->formatBusinessAddress($business);
        $representativeLine = $this->formatRepresentativeLine($business);
        $pdf->SetXY(100, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->Write(0, '所在地');

        $pdf->SetXY(120, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->MultiCell(80, 10, ($business->postal_code ? '〒'.$this->formatPostalCode($business->postal_code) : '')."\n".$address, 0, 'L', false);

        $y += 20;
        $pdf->SetXY(100, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->Write(0, '名称');

        $pdf->SetXY(120, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->MultiCell(80, 10, ($business->business_name ?? ''), 0, 'L', false);

        $y += 15;
        $pdf->SetXY(100, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->Write(0, '代表者');

        $pdf->SetXY(120, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->MultiCell(80, 10, ($representativeLine ?? ''), 0, 'L', false);

        $y += 20;

        $pageWidth = $pdf->getPageWidth();
        $titleW = 80;
        $titleX = ($pageWidth - $titleW) / 2;
        $pdf->SetXY($titleX, $y);
        $pdf->Write(0, '子どもの習い事応援事業請求書');
        $y += 10;
        $pdf->SetXY(25, $y);
        $pdf->Write(0, '子どもの習い事応援事業実施要綱第９条第２項の規定に基づき、下記のとおり請求します。');

        $y += 20;
        $titleW = 10;
        $titleX = ($pageWidth - $titleW) / 2;
        $pdf->SetXY($titleX, $y);
        $pdf->Write(0, '記');

        $y += 10;
        $pdf->SetXY(60, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->Write(0, '登録事業者');
        $pdf->SetXY(90, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->MultiCell(80, 10, ($business->business_name ?? ''), 0, 'L', false);

        $y += 15;
        $pdf->SetXY(60, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->Write(0, '利用月');
        $pdf->SetXY(90, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->MultiCell(80, 10, ($targetMonthLabel ?? ''), 0, 'L', false);

        $y += 8;
        $pdf->SetXY(60, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->Write(0, '請求額');
        $pdf->SetXY(90, $y);
        $pdf->setCellHeightRatio(1.5);
        $pdf->MultiCell(80, 10, number_format($totalAmount).' 円', 0, 'L', false);

        $y += 10;

        $bankName = $business->bank_code ? ($this->bankService->getBankName($business->bank_code) ?? "（{$business->bank_code}）") : '';
        $branchName = $business->bank_code && $business->branch_code
            ? ($this->bankService->getBranchName($business->bank_code, $business->branch_code) ?? "（{$business->branch_code}）")
            : '';

        $pdf->SetXY(20, $y);
        $pdf->Write(0, '【振込先口座】');
        $y += 10;

        $pdf->SetXY(20, $y);
        $pdf->Cell(30, 10, '銀行名', 1, 0, 'L');
        $pdf->Cell(60, 10, $bankName ? $bankName : null, 1, 0, 'L');
        $pdf->Cell(30, 10, '金融機関番号', 1, 0, 'L');
        $pdf->Cell(50, 10, $business->bank_code ? $business->bank_code : null, 1, 0, 'L');
        $pdf->Ln();
        $y += 10;
        $pdf->SetXY(20, $y);
        $pdf->Cell(30, 10, '支店名', 1, 0, 'L');
        $pdf->Cell(60, 10, $branchName ? $branchName : null, 1, 0, 'L');
        $pdf->Cell(30, 10, '支店番号', 1, 0, 'L');
        $pdf->Cell(50, 10, $business->branch_code ? $business->branch_code : null, 1, 0, 'L');
        $pdf->Ln();
        $y += 10;
        $pdf->SetXY(20, $y);
        $pdf->Cell(30, 10, '口座種別', 1, 0, 'L');
        $pdf->Cell(140, 10, $business->account_type ? $business->account_type : null, 1, 0, 'L');
        $pdf->Ln();
        $y += 10;
        $pdf->SetXY(20, $y);
        $pdf->Cell(30, 10, '口座番号', 1, 0, 'L');
        $pdf->Cell(140, 10, $business->account_number ? $business->account_number : null, 1, 0, 'L');
        $pdf->Ln();
        $y += 10;
        $pdf->SetXY(20, $y);
        $pdf->Cell(30, 10, '口座名義人', 1, 0, 'L');
        $pdf->Cell(140, 10, $business->account_holder_name ? $business->account_holder_name : null, 1, 0, 'L');
        $pdf->Ln();
        $y += 35;
    }

    private function drawPageNumber(Fpdi $pdf, int $pageNum): void
    {
        $pdf->SetY(-23);
        $pdf->SetFont('kozgopromedium', '', 8);
        $pdf->Cell(0, 8, "— {$pageNum} —", 0, 0, 'C');
    }

    private function addBreakdownPages(Fpdi $pdf, BusinessInfo $business, \Illuminate\Support\Collection $usages, string $targetMonthLabel, int &$businessPageNum): void
    {
        $margin = 25;
        $pageWidth = $pdf->getPageWidth();
        $colW = [
            'classroom' => 50,
            'amount' => 18,
            'month' => 22,
            'coupon' => 22,
            'guardian' => 35,
            'child' => 35,
            'confirm' => 22,
        ];
        $headerH = 7;
        $rowH = 6;

        $rows = $usages->all();
        $offset = 0;
        $businessName = $business->business_name ?? '';

        while ($offset < count($rows)) {
            $pdf->AddPage();
            $businessPageNum++;
            $this->drawPageNumber($pdf, $businessPageNum);
            $y = 15;
            $pdf->SetFont('kozgopromedium', '', 10);
            $pdf->SetXY($margin, $y);
            $pdf->Write(0, '子どもの習い事応援事業請求書（内訳）'.($businessName !== '' ? '　'.$businessName : ''));
            $y += 10;
            $pdf->SetXY($margin, $y);
            $pdf->Write(0, "利用月 {$targetMonthLabel}");
            $y += 8;
            $pdf->SetFont('kozgopromedium', '', 8);

            $pdf->SetXY($margin, $y);
            $pdf->Cell($colW['guardian'], $headerH, '利用者名', 1, 0, 'C');
            $pdf->Cell($colW['child'], $headerH, '児童名', 1, 0, 'C');
            $pdf->Cell($colW['classroom'], $headerH, '登録教室名', 1, 0, 'C');
            $pdf->Cell($colW['month'], $headerH, '利用日', 1, 0, 'C');
            $pdf->Cell($colW['coupon'], $headerH, 'クーポン利用額', 1, 0, 'C');
            $pdf->Ln();
            $y += $headerH;

            $count = 0;
            while ($offset < count($rows) && $count < self::ROWS_PER_PAGE) {
                $usage = $rows[$offset];
                $classroomName = $usage->classroomInfo?->classroom_name ?? '-';
                $guardianName = $usage->user?->beneficiary?->guardian_name ?? $usage->user?->name ?? '';
                $childName = $usage->user?->beneficiary?->child_name ?? '';
                // 利用日
                $usedAt = $usage->used_at ? Carbon::parse($usage->used_at)->format('Y年m月d日') : '';

                $pdf->SetXY($margin, $y);
                $pdf->Cell($colW['guardian'], $rowH, $this->truncate($guardianName, 10), 1, 0, 'L');
                $pdf->Cell($colW['child'], $rowH, $this->truncate($childName, 8), 1, 0, 'L');
                $pdf->Cell($colW['classroom'], $rowH, $this->truncate($classroomName, 12), 1, 0, 'L');
                $pdf->Cell($colW['month'], $rowH, $usedAt, 1, 0, 'C');
                $pdf->Cell($colW['coupon'], $rowH, number_format($usage->amount), 1, 0, 'R');
                $pdf->Ln();
                $y += $rowH;
                $offset++;
                $count++;
            }
        }
    }

    private function formatBusinessAddress(BusinessInfo $business): string
    {
        $parts = array_filter([
            $business->prefecture ?? '',
            $business->city ?? '',
            $business->address1 ?? '',
            $business->building_name ?? '',
        ]);

        return implode('', $parts);
    }

    private function formatPostalCode(?string $code): string
    {
        if ($code === null || $code === '') {
            return '';
        }
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) === 7) {
            return substr($code, 0, 3).'-'.substr($code, 3, 4);
        }

        return $code;
    }

    private function truncate(string $s, int $len): string
    {
        if (mb_strlen($s) <= $len) {
            return $s;
        }

        return mb_substr($s, 0, $len).'...';
    }

    private function formatRepresentativeLine(BusinessInfo $business): string
    {
        $parts = [];

        if (($business->representative_title ?? '') !== '') {
            $parts[] = (string) $business->representative_title;
        }

        if (($business->representative_family_name ?? '') !== '') {
            $parts[] = (string) $business->representative_family_name;
        }

        if (($business->representative_given_name ?? '') !== '') {
            $parts[] = (string) $business->representative_given_name;
        }

        if ($parts !== []) {
            return implode('　', $parts);
        }

        return (string) ($business->representative_name_full ?? ($business->representative_name ?? ''));
    }
}
