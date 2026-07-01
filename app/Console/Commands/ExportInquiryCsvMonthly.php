<?php

namespace App\Console\Commands;

use App\Models\AdminDownload;
use App\Models\Inquiry;
use App\Models\Subdomain;
use App\Services\InquiryCsvExportService;
use App\Services\S3KeyPrefix;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportInquiryCsvMonthly extends Command
{
    protected $signature = 'app:export-inquiry-csv-monthly';

    protected $description = '毎月1日0時に先月分の問い合わせ（inquiries・利用者・事業者）をCSVでS3に保存しadmin_downloadsに登録する（サブドメイン単位）';

    public function __construct(
        protected InquiryCsvExportService $csvExportService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $exportedAt = Carbon::now('Asia/Tokyo');
        $summary = $exportedAt->format('Y年n月j日 G時i分').' 問い合わせ（利用者・事業者）CSV 先月分';

        $this->info("問い合わせCSV月次出力を開始します。出力日時: {$exportedAt->format('Y-m-d H:i:s')}");

        $subdomains = Subdomain::query()->get();
        $processed = 0;
        $errors = 0;

        foreach ($subdomains as $subdomain) {
            try {
                $this->processSubdomain($subdomain, $exportedAt, $summary);
                $processed++;
            } catch (\Throwable $e) {
                $errors++;
                Log::error('問い合わせCSV月次出力エラー', [
                    'subdomain_id' => $subdomain->id,
                    'exported_at' => $exportedAt->format('Y-m-d H:i:s'),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("サブドメイン ID {$subdomain->id} の処理に失敗しました: {$e->getMessage()}");
            }
        }

        $this->info("問い合わせCSV月次出力を完了しました。処理: {$processed} 件、エラー: {$errors} 件");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function processSubdomain(Subdomain $subdomain, Carbon $exportedAt, string $summary): void
    {
        $lastMonthStart = $exportedAt->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $exportedAt->copy()->subMonth()->endOfMonth();

        $query = Inquiry::query()
            ->where('subdomain_id', $subdomain->id)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd]);

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('一時ストリームを開けませんでした');
        }
        $this->csvExportService->streamCsvTo($stream, $query);
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        $filename = 'inquiry_'.$exportedAt->format('Y-m-d_His').'.csv';
        $s3Key = S3KeyPrefix::forSubdomain($subdomain->id).'/inquiry_exports/'.$filename;
        $this->putToS3WithEnvGuard($s3Key, $content);

        AdminDownload::query()->create([
            'subdomain_id' => $subdomain->id,
            'exported_at' => $exportedAt,
            'summary' => $summary,
            's3_key' => $s3Key,
            'download_type' => 'inquiry',
        ]);
    }

    private function putToS3WithEnvGuard(string $key, string $content): void
    {
        $originalEnvKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? null;
        $originalEnvSecret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null;
        if (($originalEnvKey === 'null' || $originalEnvKey === '"null"')) {
            unset($_ENV['AWS_ACCESS_KEY_ID']);
            putenv('AWS_ACCESS_KEY_ID');
        }
        if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
            unset($_ENV['AWS_SECRET_ACCESS_KEY']);
            putenv('AWS_SECRET_ACCESS_KEY');
        }
        if (getenv('AWS_ACCESS_KEY_ID') === 'null' || getenv('AWS_ACCESS_KEY_ID') === '"null"') {
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
}
