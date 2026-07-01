<?php

namespace App\Services;

use App\Enums\CouponNotificationFrequency;
use App\Mail\ImmediateCouponAppliedMail;
use App\Mail\ImmediateCouponCancelledMail;
use App\Models\BusinessInfo;
use App\Models\Subdomain;
use App\Models\VoucherUsage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class ImmediateCouponAppliedNotificationService
{
    public function __construct(
        protected MailLogService $mailLogService
    ) {}

    /**
     * 事業者のメール通知設定が「都度」の場合のみ、クーポン申し込み通知メールを送信する。
     * 送信失敗・スキップ時も例外は投げず、申し込み処理の成功には影響しない。
     */
    public function sendIfImmediate(BusinessInfo $business, Subdomain $subdomain, VoucherUsage $voucherUsage): void
    {
        $frequency = CouponNotificationFrequency::tryFrom($business->email_timing ?? '');
        if ($frequency !== CouponNotificationFrequency::Immediate) {
            return;
        }

        $email = $business->email;
        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('都度クーポン申し込み通知スキップ: メールアドレスが未設定または不正', [
                'business_info_id' => $business->id,
            ]);

            return;
        }

        $fromAddress = $this->buildFromAddress($subdomain);
        $systemName = $subdomain->system_name ?? config('app.name');

        try {
            Mail::to($email)->send(new ImmediateCouponAppliedMail(
                $voucherUsage,
                $subdomain,
                $fromAddress,
                $systemName
            ));

            $subject = '【'.$systemName.'】クーポン申し込みがありました';
            $body = $this->buildMailBody($voucherUsage, $subdomain);
            $this->mailLogService->logMail($email, $subject, $body);
        } catch (\Throwable $e) {
            Log::error('都度クーポン申し込み通知送信エラー', [
                'business_info_id' => $business->id,
                'email' => $email,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 事業者のメール通知設定が「都度」の場合のみ、クーポン申し込みキャンセル通知メールを送信する。
     * 送信失敗・スキップ時も例外は投げず、キャンセル処理の成功には影響しない。
     */
    public function sendCancellationIfImmediate(BusinessInfo $business, Subdomain $subdomain, VoucherUsage $voucherUsage): void
    {
        $frequency = CouponNotificationFrequency::tryFrom($business->email_timing ?? '');
        if ($frequency !== CouponNotificationFrequency::Immediate) {
            return;
        }

        $email = $business->email;
        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('都度クーポンキャンセル通知スキップ: メールアドレスが未設定または不正', [
                'business_info_id' => $business->id,
            ]);

            return;
        }

        $fromAddress = $this->buildFromAddress($subdomain);
        $systemName = $subdomain->system_name ?? config('app.name');

        try {
            Mail::to($email)->send(new ImmediateCouponCancelledMail(
                $voucherUsage,
                $subdomain,
                $fromAddress,
                $systemName
            ));

            $subject = '【'.$systemName.'】クーポン申し込みがキャンセルされました';
            $body = $this->buildCancelledMailBody($voucherUsage, $subdomain);
            $this->mailLogService->logMail($email, $subject, $body);
        } catch (\Throwable $e) {
            Log::error('都度クーポンキャンセル通知送信エラー', [
                'business_info_id' => $business->id,
                'email' => $email,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function buildFromAddress(Subdomain $subdomain): string
    {
        $host = $this->getMailHostForSubdomain($subdomain);

        return 'no-reply@'.$host;
    }

    protected function getMailHostForSubdomain(Subdomain $subdomain): string
    {
        $appUrl = config('app.url');
        $parsed = parse_url($appUrl);
        $host = $parsed['host'] ?? 'localhost';

        $prefix = $subdomain->subdomain.'.';
        if (str_starts_with($host, $prefix)) {
            return $host;
        }

        return $subdomain->subdomain.'.'.$host;
    }

    protected function buildMailBody(VoucherUsage $voucherUsage, Subdomain $subdomain): string
    {
        $voucherUsage->loadMissing(['classroomInfo', 'courseInfo']);
        $classroomName = $voucherUsage->classroomInfo?->classroom_name ?? '';
        $courseName = $voucherUsage->course_info_id
            ? ($voucherUsage->courseInfo?->course_name ?? '')
            : '金額指定利用';
        $amount = $voucherUsage->amount;
        $usedAt = $voucherUsage->used_at?->format('Y年n月j日 H:i') ?? '';

        return View::make('emails.business.immediate_coupon_applied', [
            'subdomain' => $subdomain,
            'classroomName' => $classroomName,
            'courseName' => $courseName,
            'amount' => $amount,
            'usedAt' => $usedAt,
        ])->render();
    }

    protected function buildCancelledMailBody(VoucherUsage $voucherUsage, Subdomain $subdomain): string
    {
        $voucherUsage->loadMissing(['classroomInfo', 'courseInfo']);
        $classroomName = $voucherUsage->classroomInfo?->classroom_name ?? '';
        $courseName = $voucherUsage->course_info_id
            ? ($voucherUsage->courseInfo?->course_name ?? '')
            : '金額指定利用';
        $amount = $voucherUsage->amount;
        $usedAt = $voucherUsage->used_at?->format('Y年n月j日 H:i') ?? '';
        $cancelledAt = $voucherUsage->cancelled_at?->format('Y年n月j日 H:i') ?? '';

        return View::make('emails.business.immediate_coupon_cancelled', [
            'subdomain' => $subdomain,
            'classroomName' => $classroomName,
            'courseName' => $courseName,
            'amount' => $amount,
            'usedAt' => $usedAt,
            'cancelledAt' => $cancelledAt,
        ])->render();
    }
}
