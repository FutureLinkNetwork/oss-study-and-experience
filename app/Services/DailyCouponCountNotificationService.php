<?php

namespace App\Services;

use App\Enums\CouponNotificationFrequency;
use App\Models\BusinessInfo;
use App\Models\Subdomain;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DailyCouponCountNotificationService
{
    private const JST = 'Asia/Tokyo';

    /**
     * 前日（JST）のクーポン受付件数を通知する。0件の事業者には送らない。
     * 戻り値に送信成功・スキップ・失敗件数とエラー概要を格納する。
     *
     * @return array{sent: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function sendDailyNotifications(): array
    {
        $result = ['sent' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        $yesterday = Carbon::today(self::JST)->subDay();
        $start = $yesterday->copy()->startOfDay();
        $end = $yesterday->copy()->endOfDay();

        $businesses = BusinessInfo::query()
            ->where('email_timing', CouponNotificationFrequency::Daily->value)
            ->with('subdomain')
            ->get();

        foreach ($businesses as $businessInfo) {
            $subdomain = $businessInfo->subdomain;
            if (! $subdomain) {
                $result['skipped']++;
                $msg = "事業者ID:{$businessInfo->id} サブドメインが紐づいていません";
                $result['errors'][] = $msg;
                Log::warning('日次クーポン件数通知スキップ: '.$msg);

                continue;
            }

            $count = VoucherUsage::query()
                ->where('business_info_id', $businessInfo->id)
                ->where('subdomain_id', $subdomain->id)
                ->where('is_cancelled', false)
                ->whereBetween('used_at', [$start, $end])
                ->count();

            if ($count === 0) {
                continue;
            }

            $email = $businessInfo->email;
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result['skipped']++;
                $msg = "事業者ID:{$businessInfo->id} メールアドレスが未設定または不正です";
                $result['errors'][] = $msg;
                Log::warning('日次クーポン件数通知スキップ: '.$msg);

                continue;
            }

            $sent = $this->sendOne($businessInfo, $subdomain, $count, $yesterday);
            if ($sent) {
                $result['sent']++;
            } else {
                $result['failed']++;
                $result['errors'][] = "事業者ID:{$businessInfo->id} ({$email}) 送信失敗";
            }
        }

        return $result;
    }

    /**
     * 1事業者に1通送信する。失敗時はログに詳細を残し false を返す。
     */
    public function sendOne(BusinessInfo $businessInfo, Subdomain $subdomain, int $count, Carbon $targetDate): bool
    {
        $email = $businessInfo->email;
        $systemName = $subdomain->system_name ?? config('app.name');
        $fromAddress = $this->buildFromAddress($subdomain);
        $subject = '【'.$systemName.'】前日のクーポン受付件数のお知らせ';
        $body = '前日（'.$targetDate->format('Y/m/d').'）のクーポン受付件数は '.$count.' 件でした。';

        try {
            Mail::raw($body, function ($message) use ($email, $fromAddress, $systemName, $subject): void {
                $message->to($email)
                    ->from($fromAddress, $systemName)
                    ->subject($subject);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('日次クーポン件数通知送信エラー', [
                'business_info_id' => $businessInfo->id,
                'email' => $email,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * サブドメイン付きホストを用いた From アドレス（no-reply@ホスト）を返す。
     * app.url のホストが既にサブドメイン付きの場合は二重に付けない。
     */
    public function buildFromAddress(Subdomain $subdomain): string
    {
        $host = $this->getMailHostForSubdomain($subdomain);

        return 'no-reply@'.$host;
    }

    /**
     * サブドメイン向けのメール用ホストを取得（サブドメイン付き1ホスト）
     */
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
}
