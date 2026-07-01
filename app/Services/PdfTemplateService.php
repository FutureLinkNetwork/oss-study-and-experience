<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfTemplateService
{
    /**
     * 決定通知書PDFを生成
     *
     * @param  Beneficiary  $beneficiary  利用者情報
     * @param  Subdomain  $subdomain  サブドメイン情報
     * @param  string  $loginId  ログインID（こどもID）
     * @param  \DateTime  $sendDate  送信日
     * @return string 一時ファイルパス
     *
     * @throws \Exception
     */
    public function generateNoticePdf(Beneficiary $beneficiary, Subdomain $subdomain, string $loginId, \DateTime $sendDate): string
    {
        // テンプレートPDFのパス
        $templatePath = resource_path("pdf/templates/{$subdomain->subdomain}/notice_template.pdf");

        if (! file_exists($templatePath)) {
            throw new \Exception("テンプレートPDFが見つかりません: {$templatePath}");
        }

        // 設定を取得
        $config = config('pdf.notice_template');

        // FPDI + TCPDFのインスタンスを作成
        $pdf = new Fpdi;

        // テンプレートPDFを読み込む
        $pageCount = $pdf->setSourceFile($templatePath);
        if ($pageCount < 1) {
            throw new \Exception('テンプレートPDFの読み込みに失敗しました');
        }

        // 最初のページをインポート
        $tplId = $pdf->importPage(1);

        // ページサイズを取得
        $size = $pdf->getTemplateSize($tplId);
        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

        // ページを追加
        $pdf->AddPage($orientation, [$size['width'], $size['height']]);

        // テンプレートを使用
        $pdf->useTemplate($tplId);

        // 日本語フォントを設定（TCPDFの標準日本語フォント）
        $pdf->SetFont('kozgopromedium', '', $config['name']['font_size']);

        // 名前（保護者名）を書き込む
        $pdf->SetXY($config['name']['x'], $config['name']['y']);
        $pdf->Write(0, $beneficiary->guardian_name);

        // 利用者名（保護者名）を書き込む
        $pdf->SetFont('kozgopromedium', '', $config['beneficiary_name']['font_size']);
        $pdf->SetXY($config['beneficiary_name']['x'], $config['beneficiary_name']['y']);
        $pdf->Write(0, $beneficiary->guardian_name);

        // 対象児童名を書き込む
        $pdf->SetFont('kozgopromedium', '', $config['child_name']['font_size']);
        $pdf->SetXY($config['child_name']['x'], $config['child_name']['y']);
        $pdf->Write(0, $beneficiary->child_name);

        // 送信日の年を書き込む
        $pdf->SetFont('kozgopromedium', '', $config['year']['font_size']);
        $pdf->SetXY($config['year']['x'], $config['year']['y']);
        $pdf->Write(0, (string) $sendDate->format('Y'));

        // 送信日の月を書き込む
        $pdf->SetFont('kozgopromedium', '', $config['month']['font_size']);
        $pdf->SetXY($config['month']['x'], $config['month']['y']);
        $pdf->Write(0, (string) $sendDate->format('m'));

        // 送信日の日を書き込む
        $pdf->SetFont('kozgopromedium', '', $config['day']['font_size']);
        $pdf->SetXY($config['day']['x'], $config['day']['y']);
        $pdf->Write(0, (string) $sendDate->format('d'));

        // ログインID（こどもID）を書き込む
        $pdf->SetFont('kozgopromedium', '', $config['login_id']['font_size']);
        $pdf->SetXY($config['login_id']['x'], $config['login_id']['y']);
        $pdf->Write(0, $loginId);

        // 利用期間を書き込む
        if ($beneficiary->certification_date) {
            // 利用期間は認定日の翌月1日よりなので計算する
            $certificationDate = $beneficiary->certification_date;
            $certificationDate->add(new \DateInterval('P1M'));
            $certificationDate->setDate($certificationDate->year, $certificationDate->month, 1);
            $pdf->SetFont('kozgopromedium', '', $config['certification_date_year']['font_size']);
            $pdf->SetXY($config['certification_date_year']['x'], $config['certification_date_year']['y']);
            $pdf->Write(0, (string) $certificationDate->format('Y'));
            $pdf->SetFont('kozgopromedium', '', $config['certification_date_month']['font_size']);
            $pdf->SetXY($config['certification_date_month']['x'], $config['certification_date_month']['y']);
            $pdf->Write(0, (string) $certificationDate->format('m'));
            $pdf->SetFont('kozgopromedium', '', $config['certification_date_day']['font_size']);
            $pdf->SetXY($config['certification_date_day']['x'], $config['certification_date_day']['y']);
            $pdf->Write(0, (string) $certificationDate->format('d'));

            // 利用期間の終了年を書き込む
            // 終了期間の終了年は年度末なので計算する。認定日が3月1日以降の場合は翌年度末、それ以前の場合は当年末とする
            if ($beneficiary->certification_date->month >= 3) {
                $endYear = $beneficiary->certification_date->year + 1;
            } else {
                $endYear = $beneficiary->certification_date->year;
            }
            $pdf->SetFont('kozgopromedium', '', $config['certification_date_end_year']['font_size']);
            $pdf->SetXY($config['certification_date_end_year']['x'], $config['certification_date_end_year']['y']);
            $pdf->Write(0, (string) $endYear);
        }

        // 一時ファイルに出力
        $tempPath = sys_get_temp_dir().'/notice_'.uniqid().'.pdf';
        $tempDir = dirname($tempPath);
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $pdf->Output($tempPath, 'F');

        return $tempPath;
    }
}
