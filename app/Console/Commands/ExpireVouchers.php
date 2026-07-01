<?php

namespace App\Console\Commands;

use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExpireVouchers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-vouchers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '有効期限が過ぎたクーポンのstatusをexpiredに更新する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $this->info("クーポン有効期限無効化バッチ処理を開始します。基準日: {$today->format('Y-m-d')}");

        // 対象レコードを取得
        $targetVouchers = Voucher::query()
            ->whereIn('status', ['unused', 'used'])
            ->whereDate('expiry_date', '<', $today)
            ->get();

        if ($targetVouchers->isEmpty()) {
            $this->info('更新対象のレコードはありません。');

            return Command::SUCCESS;
        }

        $this->info("対象レコード数: {$targetVouchers->count()}件");

        $successCount = 0;
        $errorCount = 0;
        $processedIds = [];

        foreach ($targetVouchers as $voucher) {
            try {
                DB::transaction(function () use ($voucher) {
                    $voucher->status = 'expired';
                    $voucher->save();
                });

                $successCount++;
                $processedIds[] = $voucher->id;
                $this->info("ID {$voucher->id} (クーポン番号: {$voucher->voucher_number}, 有効期限: {$voucher->expiry_date->format('Y-m-d')}) を更新しました。");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("ID {$voucher->id} の更新中にエラーが発生しました: {$e->getMessage()}");
            }
        }

        $this->info("処理完了: 成功 {$successCount}件, エラー {$errorCount}件");
        if (! empty($processedIds)) {
            $this->info('更新されたID: '.implode(', ', $processedIds));
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
