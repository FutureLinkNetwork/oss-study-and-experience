<?php

namespace App\Console\Commands;

use App\Models\AdminDownload;
use App\Models\Subdomain;
use App\Models\UserApplication;
use App\Services\S3KeyPrefix;
use App\Services\UserApplicationCsvExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportUserApplicationCsvMonthly extends Command
{
    protected $signature = 'app:export-user-application-csv-monthly';

    protected $description = '毎月1日0時時点の利用者申請データ（user_applications）をCSVでS3に保存しadmin_downloadsに登録する（サブドメイン単位）';

    public function __construct(
        protected UserApplicationCsvExportService $csvExportService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $exportedAt = Carbon::now();
        $summary = $exportedAt->format('Y年n月j日 G時i分').' 利用者申請CSV 全件';

        $this->info("利用者申請CSV月次出力を開始します。出力日時: {$exportedAt->format('Y-m-d H:i:s')}");

        $subdomains = Subdomain::query()->get();
        $processed = 0;
        $errors = 0;

        foreach ($subdomains as $subdomain) {
            try {
                $this->processSubdomain($subdomain, $exportedAt, $summary);
                $processed++;
            } catch (\Throwable $e) {
                $errors++;
                Log::error('利用者申請CSV月次出力エラー', [
                    'subdomain_id' => $subdomain->id,
                    'exported_at' => $exportedAt->format('Y-m-d H:i:s'),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("サブドメイン ID {$subdomain->id} の処理に失敗しました: {$e->getMessage()}");
            }
        }

        $this->info("利用者申請CSV月次出力を完了しました。処理: {$processed} 件、エラー: {$errors} 件");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function processSubdomain(Subdomain $subdomain, Carbon $exportedAt, string $summary): void
    {
        $query = UserApplication::query()->where('subdomain_id', $subdomain->id);

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('一時ストリームを開けませんでした');
        }
        $this->csvExportService->streamCsvTo($stream, $query, $exportedAt);
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        $filename = 'riyousha_shinsei_'.$exportedAt->format('Y-m-d_His').'.csv';
        $s3Key = S3KeyPrefix::forSubdomain($subdomain->id).'/user_application_exports/'.$filename;
        $this->putToS3WithEnvGuard($s3Key, $content);

        AdminDownload::query()->create([
            'subdomain_id' => $subdomain->id,
            'exported_at' => $exportedAt,
            'summary' => $summary,
            's3_key' => $s3Key,
            'download_type' => 'user_application',
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
