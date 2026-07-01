<?php

namespace App\Console\Commands;

use App\Models\VoucherUsage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckCorrectionAndAggregate extends Command
{
    private const CACHE_KEY_LAST_CHECK_AT = 'check_correction_last_run_at';

    private const CACHE_TTL_MINUTES = 60 * 24 * 30; // 30日

    protected $signature = 'app:check-correction-and-aggregate';

    protected $description = 'admin_corrected_atの更新を検知し、変更があれば支払集計と会計レポート生成を実行する';

    public function handle(): int
    {
        $lastCheckAt = Cache::get(self::CACHE_KEY_LAST_CHECK_AT);
        // $lastCheckAtをログに出力
        Log::info('lastCheckAt: '.$lastCheckAt);

        if ($lastCheckAt === null) {
            Cache::put(self::CACHE_KEY_LAST_CHECK_AT, now(), self::CACHE_TTL_MINUTES);
            $this->info('初回実行のため前回実行時刻を保存して終了しました。');

            return Command::SUCCESS;
        }

        $lastCheckAt = Carbon::parse($lastCheckAt);
        $hasCorrection = VoucherUsage::query()
            ->whereNotNull('admin_corrected_at')
            ->where('admin_corrected_at', '>', $lastCheckAt)
            ->exists();

        if (! $hasCorrection) {
            Cache::put(self::CACHE_KEY_LAST_CHECK_AT, now(), self::CACHE_TTL_MINUTES);

            return Command::SUCCESS;
        }

        $this->info('クーポン利用の修正を検知しました。支払集計と会計レポート生成を実行します。');

        Artisan::call('app:aggregate-business-payments', ['--skip-notification' => true]);
        Artisan::call('app:generate-monthly-accounting-reports');

        Cache::put(self::CACHE_KEY_LAST_CHECK_AT, now(), self::CACHE_TTL_MINUTES);
        $this->info('処理を完了し、前回実行時刻を更新しました。');

        return Command::SUCCESS;
    }
}
