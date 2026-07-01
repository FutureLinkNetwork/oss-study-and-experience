<?php

namespace App\Services;

use App\Models\BusinessInfo;
use App\Models\PaymentAggregate;
use App\Models\Subdomain;
use Carbon\Carbon;
use setasign\Fpdi\Tcpdf\Fpdi;

class PaymentNoticePdfService
{
    public function __construct(
        protected BankService $bankService
    ) {}

    /**
     * 支払通知PDFを生成する
     *
     * @param  array<int, PaymentAggregate>  $aggregates  同一事業者・同一申込月の集計（教室別）
     * @return string 一時ファイルパス
     *
     * @throws \Exception
     */
    public function generate(BusinessInfo $business, Subdomain $subdomain, string $targetYearMonth, array $aggregates): string
    {
        if (empty($aggregates)) {
            throw new \InvalidArgumentException('集計データがありません');
        }

        $totalAmount = (int) array_sum(array_map(fn (PaymentAggregate $a) => $a->total_amount, $aggregates));
        $targetMonth = Carbon::parse($targetYearMonth.'-01');
        $transferDate = $this->resolveTransferDate($subdomain, $targetMonth);

        $managementNumber = $this->buildManagementNumber($targetMonth, $subdomain, $business);
        $issueDate = Carbon::today()->format('Y年n月j日');

        $bankName = $business->bank_code ? ($this->bankService->getBankName($business->bank_code) ?? "（{$business->bank_code}）") : '';
        $branchName = $business->bank_code && $business->branch_code
            ? ($this->bankService->getBranchName($business->bank_code, $business->branch_code) ?? "（{$business->branch_code}）")
            : '';

        $businessAddress = $this->formatBusinessAddress($business);
        $subdomainAddress = $subdomain->address ?? '';

        $pdf = new Fpdi;
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetFont('kozgopromedium', '', 10);

        $y = 15;

        // ヘッダ: 発行日・管理番号
        $pdf->SetXY(20, $y);
        $pdf->Write(0, "発行日：{$issueDate}");
        $pdf->SetXY(130, $y);
        $pdf->Write(0, "管理番号：{$managementNumber}");
        $y += 14;

        // 「支払通知書」をページ中央に独立配置（罫線付き）
        $titleW = 70;
        $titleH = 12;
        $pageWidth = $pdf->getPageWidth();
        $titleX = ($pageWidth - $titleW) / 2;
        $pdf->SetFont('kozgopromedium', '', 16);
        $pdf->SetXY($titleX, $y);
        $pdf->Cell($titleW, $titleH, '支払通知書', 1, 0, 'C');
        $pdf->SetFont('kozgopromedium', '', 10);
        $y += $titleH + 8;

        // 事業者情報（左）・サブドメイン情報（右）
        $colW = 85;
        $pdf->SetXY(20, $y);
        $pdf->MultiCell($colW, 5, implode("\n", array_filter([
            '〒'.$this->formatPostalCode($business->postal_code),
            $businessAddress,
            $business->business_name ?? '',
            '代表者名：'.$this->formatRepresentativeLine($business),
            '振込先：',
            $bankName ? "  銀行名：{$bankName}" : null,
            $branchName ? "  支店名：{$branchName}" : null,
            $business->account_type ? "  口座種別：{$business->account_type}" : null,
            $business->account_number ? "  口座番号：{$business->account_number}" : null,
            $business->account_holder_name ? "  口座名義：{$business->account_holder_name}" : null,
        ])), 0, 'L', false);

        $pdf->SetXY(130, $y);
        $pdf->MultiCell($colW, 5, implode("\n", array_filter([
            $subdomain->postal_code ? '〒'.$this->formatPostalCode($subdomain->postal_code) : null,
            $subdomainAddress,
            $subdomain->phone ? "TEL：{$subdomain->phone}" : null,
            $subdomain->fax ? "FAX：{$subdomain->fax}" : null,
        ])), 0, 'L', false);

        $y += 65;

        // 表1: 振り込み日・合計金額
        $pdf->SetFont('kozgopromedium', '', 10);
        $pdf->SetXY(20, $y);
        $pdf->Cell(40, 7, '振り込み日', 1, 0, 'C');
        $pdf->Cell(50, 7, $transferDate->format('Y年n月j日'), 1, 0, 'R');
        $pdf->Ln();
        $pdf->SetXY(20, $y + 7);
        $pdf->Cell(40, 7, '合計金額', 1, 0, 'C');
        $pdf->Cell(50, 7, '¥'.number_format($totalAmount).' ', 1, 0, 'R');
        $y += 22;

        // 表2: 教室名・申込件数・金額（左右余白同じで横幅いっぱい）
        $margin = 20;
        $pageWidth = $pdf->getPageWidth();
        $tableWidth = $pageWidth - $margin * 2;
        $wClassroom = $tableWidth - 30 - 45;
        $wCount = 30;
        $wAmount = 45;

        $pdf->SetXY($margin, $y);
        $pdf->Cell($wClassroom, 7, '教室名', 1, 0, 'C');
        $pdf->Cell($wCount, 7, '申込件数', 1, 0, 'C');
        $pdf->Cell($wAmount, 7, '金額', 1, 0, 'C');
        $pdf->Ln();

        foreach ($aggregates as $agg) {
            $classroom = $agg->classroomInfo;
            $classroomName = $classroom instanceof \App\Models\ClassroomInfo ? $classroom->classroom_name : '-';
            $pdf->SetX($margin);
            $pdf->Cell($wClassroom, 7, $classroomName, 1, 0, 'L');
            $pdf->Cell($wCount, 7, (string) $agg->application_count, 1, 0, 'R');
            $pdf->Cell($wAmount, 7, '¥'.number_format($agg->total_amount).' ', 1, 0, 'R');
            $pdf->Ln();
        }

        $tempPath = sys_get_temp_dir().'/payment_notice_'.uniqid().'.pdf';
        $tempDir = dirname($tempPath);
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $pdf->Output($tempPath, 'F');

        return $tempPath;
    }

    private function buildManagementNumber(Carbon|\DateTimeInterface $targetMonth, Subdomain $subdomain, BusinessInfo $business): string
    {
        $yyyymm = $targetMonth->format('Ym');
        $sub = $subdomain->subdomain ?? '';
        $bid = (string) $business->id;

        return "{$yyyymm}-{$sub}-{$bid}";
    }

    private function resolveTransferDate(Subdomain $subdomain, Carbon|\DateTimeInterface $targetMonth): Carbon
    {
        $rule = $subdomain->transfer_date_rule ?? Subdomain::TRANSFER_DATE_RULE_NEXT_MONTH_END;
        $base = Carbon::parse($targetMonth)->startOfMonth();

        return match ($rule) {
            Subdomain::TRANSFER_DATE_RULE_MONTH_AFTER_NEXT_END => $base->copy()->addMonths(2)->endOfMonth(),
            default => $base->copy()->addMonth()->endOfMonth(),
        };
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
