<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // カスタムミドルウェアエイリアスを登録
        $middleware->alias([
            'basic.auth' => \App\Http\Middleware\BasicAuth::class,
            'clear.auth' => \App\Http\Middleware\ClearPreviousAuth::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'require.password.change' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'validate.form.session' => \App\Http\Middleware\ValidateFormSession::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // 資格喪失となった受益者の処理
        $schedule->command('app:disqualify-expired-beneficiaries')
            ->daily()
            ->at('00:00')
            ->timezone('Asia/Tokyo');

        // 月次クーポン発行
        $schedule->command('app:issue-monthly-vouchers')
            ->daily()
            ->at('00:05')
            ->timezone('Asia/Tokyo');

        // クーポン失効処理
        $schedule->command('app:expire-vouchers')
            ->daily()
            ->at('00:10')
            ->timezone('Asia/Tokyo');

        // 利用者CSV月次出力
        $schedule->command('app:export-beneficiary-csv-monthly')
            ->monthlyOn(1, '00:00')
            // ->everyMinute()
            ->timezone('Asia/Tokyo');

        // 利用者申請CSV月次出力
        $schedule->command('app:export-user-application-csv-monthly')
            ->monthlyOn(1, '00:01')
            // ->everyMinute()
            ->timezone('Asia/Tokyo');

        // お問い合わせCSV月次出力
        $schedule->command('app:export-contact-csv-monthly')
            ->monthlyOn(1, '00:02')
            // ->everyMinute()
            ->timezone('Asia/Tokyo');

        // 問い合わせ（利用者・事業者）CSV月次出力
        $schedule->command('app:export-inquiry-csv-monthly')
            ->monthlyOn(1, '00:03')
            // ->everyMinute()
            ->timezone('Asia/Tokyo');

        // 決定通知書送信待ちの利用者にメールを送信する（5分毎バッチ）
        $schedule->command('app:send-pending-decision-notices')
            ->everyFiveMinutes()
            ->timezone('Asia/Tokyo');

        // 日次クーポン件数通知
        $schedule->command('app:send-daily-coupon-count-notifications')
            ->daily()
            ->at('09:00')
            ->timezone('Asia/Tokyo');

        // 支払集計バッチを毎月2日の00:01に実行
        $schedule->command('app:aggregate-business-payments')
            ->monthlyOn(2, '00:01')
            // 毎回実行
            // ->everyMinute()
            ->timezone('Asia/Tokyo');

        // 会計レポート生成
        $schedule->command('app:generate-monthly-accounting-reports')
            ->monthlyOn(2, '00:05')
            // 毎回実行
            // ->everyMinute()
            ->timezone('Asia/Tokyo');

        // クーポン利用の修正検知：5分ごとにadmin_corrected_atを確認し、変更があれば支払集計・会計レポートを実行
        $schedule->command('app:check-correction-and-aggregate')
            ->everyFiveMinutes()
            ->timezone('Asia/Tokyo');

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
