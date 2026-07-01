<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MailLogService
{
    /**
     * メール送信内容をLaravel標準のログに保存
     *
     * @param  string  $to  送信先メールアドレス
     * @param  string  $subject  件名
     * @param  string  $body  本文
     */
    public function logMail(string $to, string $subject, string $body): void
    {
        $logContent = sprintf(
            "========================================\n".
            "送信日時: %s\n".
            "送信先: %s\n".
            "件名: %s\n".
            "----------------------------------------\n".
            "%s\n".
            "========================================\n",
            now()->format('Y-m-d H:i:s'),
            $to,
            $subject,
            $body
        );

        Log::info($logContent);
    }
}
