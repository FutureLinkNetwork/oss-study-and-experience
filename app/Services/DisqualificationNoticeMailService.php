<?php

namespace App\Services;

use App\Mail\DisqualificationNoticeMail;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DisqualificationNoticeMailService
{
    private const FALLBACK_SYSTEM_NAME = '子どもの習い事応援事業';

    public function __construct(
        protected MailLogService $mailLogService
    ) {}

    /**
     * 資格喪失通知メールを1件送信する。
     * メールアドレス不正や送信失敗時は例外を投げず false を返す。
     */
    public function send(Beneficiary $beneficiary): bool
    {
        $email = $beneficiary->guardian_email;
        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('資格喪失通知メールスキップ: メールアドレスが未設定または不正', [
                'beneficiary_id' => $beneficiary->id,
                'email' => $email,
            ]);

            return false;
        }

        $beneficiary->loadMissing('subdomain');
        $systemName = $this->systemDisplayName($beneficiary);
        $mailable = new DisqualificationNoticeMail($systemName);

        try {
            Mail::to($email)->send($mailable);

            $this->mailLogService->logMail(
                $email,
                $mailable->envelope()->subject ?? '',
                $mailable->render()
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('資格喪失通知メール送信エラー', [
                'beneficiary_id' => $beneficiary->id,
                'email' => $email,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    protected function systemDisplayName(Beneficiary $beneficiary): string
    {
        $name = trim((string) ($beneficiary->subdomain?->system_name ?? ''));

        return $name !== '' ? $name : self::FALLBACK_SYSTEM_NAME;
    }
}
