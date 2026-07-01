<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DecisionNoticeMailService
{
    public function __construct(
        protected MailLogService $mailLogService,
        protected PdfTemplateService $pdfTemplateService
    ) {}

    /**
     * 決定通知書メールを1件送信する。
     * 成功時は beneficiary の status を「決定通知書送信済」に更新し true を返す。
     * 失敗・スキップ時は system_message に理由を保存し status を「決定通知書送信失敗」に更新して false を返す。
     */
    public function sendDecisionNotice(Beneficiary $beneficiary, Subdomain $subdomain): bool
    {
        $tempPdfPath = null;

        try {
            if (! empty($beneficiary->user_id)) {
                $this->recordFailure($beneficiary, '既にアカウントがあるためスキップしました。');
                Log::error('メール一括送信エラー: 既にuser_idが設定されています', [
                    'beneficiary_id' => $beneficiary->id,
                ]);

                return false;
            }

            if (empty($beneficiary->child_id)) {
                $this->recordFailure($beneficiary, 'こどもIDが空です');
                Log::error('メール一括送信エラー: こどもIDが空です', [
                    'beneficiary_id' => $beneficiary->id,
                    'certification_number' => $beneficiary->certification_number,
                ]);

                return false;
            }

            if (empty($beneficiary->guardian_email) || ! filter_var($beneficiary->guardian_email, FILTER_VALIDATE_EMAIL)) {
                $this->recordFailure($beneficiary, 'メールアドレスが無効');
                Log::error('メール一括送信エラー: メールアドレスが無効', [
                    'beneficiary_id' => $beneficiary->id,
                    'child_id' => $beneficiary->child_id,
                    'email' => $beneficiary->guardian_email,
                ]);

                return false;
            }

            $existingUser = User::where('subdomain_id', $subdomain->id)
                ->where('login_id', $beneficiary->child_id)
                ->first();

            if ($existingUser) {
                $this->recordFailure($beneficiary, 'ログインIDが既に使用されています');
                Log::error('メール一括送信エラー: ログインIDが既に使用されています', [
                    'beneficiary_id' => $beneficiary->id,
                    'child_id' => $beneficiary->child_id,
                ]);

                return false;
            }

            $subdomainUserRole = Role::where('name', 'subdomain_user')->first();
            if (! $subdomainUserRole) {
                $this->recordFailure($beneficiary, 'subdomain_userロールが見つかりません');

                return false;
            }

            $sendDate = now();
            $password = Str::random(10);

            try {
                $tempPdfPath = $this->pdfTemplateService->generateNoticePdf(
                    $beneficiary,
                    $subdomain,
                    $beneficiary->child_id,
                    $sendDate
                );
                Log::info('tempPdfPath: '.$tempPdfPath);
            } catch (\Exception $e) {
                $this->recordFailure($beneficiary, 'PDF生成失敗: '.$e->getMessage());
                Log::error('メール一括送信エラー: PDF生成失敗', [
                    'beneficiary_id' => $beneficiary->id,
                    'child_id' => $beneficiary->child_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return false;
            }

            $loginUrl = $this->buildLoginUrlForSubdomain($subdomain);
            $subject = 'ログイン情報のお知らせ';
            $systemName = ! empty($subdomain->system_name) ? $subdomain->system_name : '習い事クーポン管理システム';
            $body = sprintf(
                "%s 様\n\n".
                "%sのログイン情報をお知らせいたします。\n\n".
				"添付されている交付決定通知書に記載の注意事項等をご確認の上、ご利用ください。\n".
				"なお、本メールはクーポンの交付決定のご連絡となります。習い事の申し込み状況等は、事前に各教室へ直接ご連絡ください。\n\n".	
                "ログインID: %s\n".
                "パスワード: %s\n\n".
                "ログインURL: %s\n\n".
                "初回ログイン後、パスワードの変更をお願いいたします。\n\n".
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
                "このメールは自動送信されています。\n".
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n",
                $beneficiary->guardian_name,
                $systemName,
                $beneficiary->child_id,
                $password,
                $loginUrl,
            );

            DB::transaction(function () use (
                $beneficiary,
                $subdomain,
                $subdomainUserRole,
                $password,
                $subject,
                $body,
                $systemName,
                $tempPdfPath
            ) {
                $newUser = User::create([
                    'subdomain_id' => $subdomain->id,
                    'login_id' => $beneficiary->child_id,
                    'password' => Hash::make($password),
                    'name' => $beneficiary->child_name,
                    'display_name' => $beneficiary->child_name,
                    'email' => $beneficiary->guardian_email,
                    'role_id' => $subdomainUserRole->id,
                    'is_active' => true,
                ]);

                $beneficiary->update(['user_id' => $newUser->id]);

                Mail::raw($body, function ($message) use ($beneficiary, $subject, $systemName, $tempPdfPath) {
                    $message->to($beneficiary->guardian_email)
                        ->from('no-reply@study-and-experience.jp', $systemName)
                        ->subject($subject);

                    if ($tempPdfPath && file_exists($tempPdfPath)) {
                        $message->attach($tempPdfPath, [
                            'as' => "決定通知書_{$beneficiary->child_id}.pdf",
                            'mime' => 'application/pdf',
                        ]);
                    }
                });

                $this->mailLogService->logMail($beneficiary->guardian_email, $subject, $body);

                $beneficiary->update([
                    'status' => '決定通知書送信済',
                    'system_message' => '',
                ]);
            });

            if ($tempPdfPath && file_exists($tempPdfPath)) {
                unlink($tempPdfPath);
            }

            Log::info('利用者ログイン情報を新規作成', [
                'beneficiary_id' => $beneficiary->id,
                'child_id' => $beneficiary->child_id,
                'email' => $beneficiary->guardian_email,
            ]);

            return true;
        } catch (\Exception $e) {
            if ($tempPdfPath && file_exists($tempPdfPath)) {
                unlink($tempPdfPath);
            }

            $this->recordFailure($beneficiary, $e->getMessage());
            Log::error('メール一括送信エラー', [
                'beneficiary_id' => $beneficiary->id,
                'child_id' => $beneficiary->child_id ?? $beneficiary->certification_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    protected function recordFailure(Beneficiary $beneficiary, string $reason): void
    {
        $beneficiary->update([
            'status' => '決定通知書送信失敗',
            'system_message' => $reason,
        ]);
    }

    /**
     * サブドメイン向けのログインURLを生成（コンソール等でリクエストがない場合に config から組み立て）
     * app.url のホストが既にサブドメイン付き（例: www.study-and-experience.jp）の場合は二重に付けない。
     */
    protected function buildLoginUrlForSubdomain(Subdomain $subdomain): string
    {
        $appUrl = config('app.url');
        $parsed = parse_url($appUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? null;

        $prefix = $subdomain->subdomain.'.';
        if (str_starts_with($host, $prefix)) {
            $baseUrl = $scheme.'://'.$host;
        } else {
            $baseUrl = $scheme.'://'.$subdomain->subdomain.'.'.$host;
        }
        if ($port !== null && ! in_array($port, [80, 443], true)) {
            $baseUrl .= ':'.$port;
        }

        return $baseUrl.'/login';
    }
}
