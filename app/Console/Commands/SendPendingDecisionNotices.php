<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use App\Services\DecisionNoticeMailService;
use Illuminate\Console\Command;

class SendPendingDecisionNotices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-pending-decision-notices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '決定通知書送信待ちの利用者にメールを送信する（5分毎バッチ）';

    /**
     * Execute the console command.
     */
    public function handle(DecisionNoticeMailService $decisionNoticeMailService): int
    {
        $this->info('決定通知書送信バッチを開始します。');

        $subdomains = Subdomain::all();

        if ($subdomains->isEmpty()) {
            $this->info('サブドメインがありません。');

            return Command::SUCCESS;
        }

        $totalSuccess = 0;
        $totalFailure = 0;

        foreach ($subdomains as $subdomain) {
            $beneficiaries = Beneficiary::where('subdomain_id', $subdomain->id)
                ->where('status', '決定通知書送信待ち')
                ->get();

            if ($beneficiaries->isEmpty()) {
                continue;
            }

            $this->info("サブドメイン: {$subdomain->name} (ID: {$subdomain->id}) - 送信待ち {$beneficiaries->count()}件");

            foreach ($beneficiaries as $beneficiary) {
                $success = $decisionNoticeMailService->sendDecisionNotice($beneficiary, $subdomain);
                if ($success) {
                    $totalSuccess++;
                } else {
                    $totalFailure++;
                }
            }
        }

        $this->info("処理完了: 成功 {$totalSuccess}件, 失敗 {$totalFailure}件");

        return Command::SUCCESS;
    }
}
