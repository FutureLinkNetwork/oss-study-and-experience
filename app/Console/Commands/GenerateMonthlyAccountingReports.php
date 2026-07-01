<?php

namespace App\Console\Commands;

use App\Models\AccountingReportDownload;
use App\Models\Subdomain;
use App\Models\VoucherUsage;
use App\Services\AccountingInvoicePdfService;
use App\Services\S3KeyPrefix;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateMonthlyAccountingReports extends Command
{
    protected $signature = 'app:generate-monthly-accounting-reports';

    protected $description = '前月分の利用バウチャCSVと事業所別請求書PDFを公金振替対象・対象外に分けて生成しS3にアップロードする（サブドメイン単位）';

    public function __construct(
        protected AccountingInvoicePdfService $invoicePdfService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetMonth = Carbon::today()->subMonth();
        $monthStart = $targetMonth->copy()->startOfMonth()->startOfDay();
        $monthEnd = $targetMonth->copy()->endOfMonth()->endOfDay();
        $targetYearMonth = $targetMonth->format('Y-m');
        $targetMonthDate = $monthStart->toDateString();
        $extractDate = Carbon::today()->format('Y-m-d H:i:s');

        $this->info("会計用月次レポート生成を開始します。対象月: {$targetMonth->format('Y年n月')}");

        $subdomains = Subdomain::query()->get();
        $processed = 0;
        $errors = 0;

        foreach ($subdomains as $subdomain) {
            try {
                $this->processSubdomain($subdomain, $monthStart, $monthEnd, $targetYearMonth, $targetMonthDate, $extractDate);
                $processed++;
            } catch (\Throwable $e) {
                $errors++;
                Log::error('会計用月次レポート生成エラー', [
                    'subdomain_id' => $subdomain->id,
                    'target_month' => $targetYearMonth,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("サブドメイン ID {$subdomain->id} の処理に失敗しました: {$e->getMessage()}");
            }
        }

        $this->info("会計用月次レポート生成を完了しました。処理: {$processed} 件、エラー: {$errors} 件");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function processSubdomain(
        Subdomain $subdomain,
        Carbon $monthStart,
        Carbon $monthEnd,
        string $targetYearMonth,
        string $targetMonthDate,
        string $extractDate
    ): void {
        $tempDir = sys_get_temp_dir().'/accounting_'.uniqid();
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $payload = ['updated_at' => now()];
        $pdfPaths = [];

        try {
            $prefix = S3KeyPrefix::forSubdomain($subdomain->id);

            foreach ([true => 'target', false => 'non_target'] as $isTarget => $kind) {
                $usageCount = VoucherUsage::query()
                    ->where('subdomain_id', $subdomain->id)
                    ->where('is_cancelled', false)
                    ->whereBetween('used_at', [$monthStart, $monthEnd])
                    ->whereHas('businessInfo', fn ($q) => $q->where('is_public_funds_transfer_target', $isTarget))
                    ->count();

                if ($usageCount === 0) {
                    if (! $isTarget) {
                        $payload['csv_s3_key_non_target'] = null;
                        $payload['pdf_s3_key_non_target'] = null;
                    }

                    continue;
                }

                $fileSuffix = $isTarget ? '' : '_non_target';
                $csvPath = $this->buildCsv($subdomain->id, $monthStart, $monthEnd, $extractDate, $tempDir, $isTarget, $fileSuffix);
                $pdfPath = $this->invoicePdfService->generateForSubdomain($subdomain, $targetYearMonth, $isTarget);
                $pdfPaths[] = $pdfPath;

                $csvS3Key = "{$prefix}/accounting_reports/{$targetYearMonth}{$fileSuffix}.csv";
                $pdfS3Key = "{$prefix}/accounting_reports/{$targetYearMonth}{$fileSuffix}.pdf";

                $this->putToS3WithEnvGuard($csvS3Key, (string) file_get_contents($csvPath));
                $this->putToS3WithEnvGuard($pdfS3Key, (string) file_get_contents($pdfPath));

                if ($isTarget) {
                    $payload['csv_s3_key'] = $csvS3Key;
                    $payload['pdf_s3_key'] = $pdfS3Key;
                } else {
                    $payload['csv_s3_key_non_target'] = $csvS3Key;
                    $payload['pdf_s3_key_non_target'] = $pdfS3Key;
                }
            }

            AccountingReportDownload::query()->updateOrInsert(
                [
                    'subdomain_id' => $subdomain->id,
                    'target_month' => $targetMonthDate,
                ],
                $payload
            );
        } finally {
            foreach ($pdfPaths as $pdfPath) {
                if (file_exists($pdfPath)) {
                    @unlink($pdfPath);
                }
            }
            $this->removeDirectory($tempDir);
        }
    }

    private function buildCsv(
        int $subdomainId,
        Carbon $monthStart,
        Carbon $monthEnd,
        string $extractDate,
        string $tempDir,
        bool $isPublicFundsTransferTarget,
        string $fileSuffix
    ): string {
        $usages = VoucherUsage::query()
            ->where('subdomain_id', $subdomainId)
            ->where('is_cancelled', false)
            ->whereBetween('used_at', [$monthStart, $monthEnd])
            ->whereHas('businessInfo', fn ($q) => $q->where('is_public_funds_transfer_target', $isPublicFundsTransferTarget))
            ->with(['businessInfo', 'classroomInfo', 'courseInfo', 'user.beneficiary'])
            ->orderBy('used_at')
            ->get();

        $path = $tempDir.'/voucher_usage'.$fileSuffix.'.csv';
        $fp = fopen($path, 'w');
        if ($fp === false) {
            throw new \RuntimeException('CSVファイルを開けませんでした');
        }

        fwrite($fp, "\xEF\xBB\xBF");
        $header = ['No.', '申込日時', '事業者ID', '事業者名', '教室ID', '教室名', 'こどもID', '保護者名', '対象児童名', 'コースID', 'コース名', '利用金額', 'ステータス', '抽出日'];
        fputcsv($fp, $header);

        $no = 1;
        foreach ($usages as $u) {
            $usedAt = $u->used_at?->format('Y-m-d H:i:s') ?? '';
            $businessId = (string) ($u->business_info_id ?? '');
            $businessName = $u->businessInfo?->business_name ?? '';
            $classroomId = (string) ($u->classroom_info_id ?? '');
            $classroomName = $u->classroomInfo?->classroom_name ?? '';
            $childId = $u->user?->beneficiary?->child_id ?? '';
            $guardianName = $u->user?->beneficiary?->guardian_name ?? $u->user?->name ?? '';
            $childName = $u->user?->beneficiary?->child_name ?? '';
            $courseId = (string) ($u->course_info_id ?? '');
            $courseName = $u->courseInfo?->course_name ?? '';
            $amount = (string) ($u->amount ?? 0);
            $status = $u->is_cancelled ? 'キャンセル' : '利用済';

            fputcsv($fp, [
                (string) $no,
                $usedAt,
                $businessId,
                $businessName,
                $classroomId,
                $classroomName,
                $childId,
                $guardianName,
                $childName,
                $courseId,
                $courseName,
                $amount,
                $status,
                $extractDate,
            ]);
            $no++;
        }
        fclose($fp);

        return $path;
    }

    private function putToS3WithEnvGuard(string $key, string $content): void
    {
        $originalEnvKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? null;
        $originalEnvSecret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null;
        $getenvKey = getenv('AWS_ACCESS_KEY_ID');
        if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
            unset($_ENV['AWS_ACCESS_KEY_ID']);
            putenv('AWS_ACCESS_KEY_ID');
        }
        if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
            unset($_ENV['AWS_SECRET_ACCESS_KEY']);
            putenv('AWS_SECRET_ACCESS_KEY');
        }
        if ($getenvKey === 'null' || $getenvKey === '"null"') {
            putenv('AWS_ACCESS_KEY_ID');
        }
        if (getenv('AWS_SECRET_ACCESS_KEY') === 'null' || getenv('AWS_SECRET_ACCESS_KEY') === '"null"') {
            putenv('AWS_SECRET_ACCESS_KEY');
        }

        try {
            $putResult = Storage::disk('s3')->put($key, $content);
            if (! $putResult) {
                throw new \RuntimeException("S3 put が false を返しました。key={$key}");
            }
        } finally {
            if ($originalEnvKey !== null) {
                $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
            }
            if ($originalEnvSecret !== null) {
                $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
