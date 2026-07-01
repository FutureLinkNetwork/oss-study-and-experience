<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DisqualifyExpiredBeneficiaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:disqualify-expired-beneficiaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '資格喪失日が過ぎた受益者のステータスを「資格喪失」に更新する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $this->info("資格喪失バッチ処理を開始します。基準日: {$today->format('Y-m-d')}");

        // 対象レコードを取得（ステータスが「資格喪失」でない、かつ資格喪失日が過ぎたレコード）
        $targetBeneficiaries = Beneficiary::query()
            ->where('status', '!=', '資格喪失')
            ->whereNotNull('disqualification_date')
            ->whereDate('disqualification_date', '<=', $today)
            ->get();

        if ($targetBeneficiaries->isEmpty()) {
            $this->info('更新対象のレコードはありません。');

            return Command::SUCCESS;
        }

        $this->info("対象レコード数: {$targetBeneficiaries->count()}件");

        $successCount = 0;
        $errorCount = 0;
        $processedIds = [];

        foreach ($targetBeneficiaries as $beneficiary) {
            try {
                DB::transaction(function () use ($beneficiary) {
                    $beneficiary->status = '資格喪失';
                    $beneficiary->save();
                });

                $successCount++;
                $processedIds[] = $beneficiary->id;
                $this->info("ID {$beneficiary->id} (認定番号: {$beneficiary->certification_number}, 資格喪失日: {$beneficiary->disqualification_date->format('Y-m-d')}) を更新しました。");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("ID {$beneficiary->id} の更新中にエラーが発生しました: {$e->getMessage()}");
            }
        }

        $this->info("処理完了: 成功 {$successCount}件, エラー {$errorCount}件");
        if (! empty($processedIds)) {
            $this->info('更新されたID: '.implode(', ', $processedIds));
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
