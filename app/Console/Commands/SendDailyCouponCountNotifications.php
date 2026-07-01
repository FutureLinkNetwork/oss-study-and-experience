<?php

namespace App\Console\Commands;

use App\Services\DailyCouponCountNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyCouponCountNotifications extends Command
{
    protected $signature = 'app:send-daily-coupon-count-notifications';

    protected $description = '毎日9時（JST）に「一日1回（9時頃）」設定の事業者へ前日クーポン受付件数を通知する';

    public function handle(DailyCouponCountNotificationService $service): int
    {
        $this->info('日次クーポン件数通知バッチを開始します。');

        $result = $service->sendDailyNotifications();

        $this->info("処理完了: 送信 {$result['sent']}件, スキップ {$result['skipped']}件, 失敗 {$result['failed']}件");

        $hasErrors = $result['failed'] > 0 || $result['skipped'] > 0;
        if ($hasErrors && ! empty($result['errors'])) {
            $adminAddress = config('mail.admin_for_errors');
            if (! empty($adminAddress)) {
                $this->notifyAdmin($result, $adminAddress);
            } else {
                Log::warning('日次クーポン件数通知: エラー・スキップが発生しましたが、管理者通知先が未設定のためログのみ記録', [
                    'errors' => $result['errors'],
                ]);
            }
        }

        return Command::SUCCESS;
    }

    private function notifyAdmin(array $result, string $adminAddress): void
    {
        $lines = [
            '日次クーポン件数通知バッチでエラーまたはスキップが発生しました。',
            '',
            '送信: '.$result['sent'].'件',
            'スキップ: '.$result['skipped'].'件',
            '失敗: '.$result['failed'].'件',
            '',
            '詳細:',
        ];
        foreach (array_slice($result['errors'], 0, 50) as $err) {
            $lines[] = '・'.$err;
        }
        if (count($result['errors']) > 50) {
            $lines[] = '・... 他 '.(count($result['errors']) - 50).' 件';
        }
        $body = implode("\n", $lines);
        $subject = '【管理者通知】日次クーポン件数通知バッチでエラーが発生しました';

        try {
            Mail::raw($body, function ($message) use ($adminAddress, $subject): void {
                $message->to($adminAddress)
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('日次クーポン件数通知: 管理者へのエラー通知送信に失敗しました', [
                'admin' => $adminAddress,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
