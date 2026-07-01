<?php

namespace App\Services;

use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function __construct(
        private MailLogService $mailLogService
    ) {}

    /**
     * メールアドレスとサブドメインID、ロール名でユーザーを1件検索
     *
     * @param  string  $email  メールアドレス
     * @param  int  $subdomainId  サブドメインID
     * @param  string  $roleName  ロール名
     */
    public function findUserByEmail(string $email, int $subdomainId, string $roleName): ?User
    {
        return User::where('email', $email)
            ->where('subdomain_id', $subdomainId)
            ->where('is_active', 1)
            ->with('role')
            ->whereHas('role', function ($query) use ($roleName) {
                $query->where('name', $roleName)
                    ->where('is_active', 1);
            })
            ->first();
    }

    /**
     * メールアドレスとサブドメインID、ロール名でユーザーを全件検索（同一メールの重複含む）
     *
     * @param  string  $email  メールアドレス
     * @param  int  $subdomainId  サブドメインID
     * @param  string  $roleName  ロール名
     * @return Collection<int, User>
     */
    public function findUsersByEmail(string $email, int $subdomainId, string $roleName): Collection
    {
        return User::where('email', $email)
            ->where('subdomain_id', $subdomainId)
            ->where('is_active', 1)
            ->with('role')
            ->whereHas('role', function ($query) use ($roleName) {
                $query->where('name', $roleName)
                    ->where('is_active', 1);
            })
            ->get();
    }

    /**
     * メールビュー名を解決（サブドメイン固有ビューを優先）
     *
     * @param  string  $baseViewName  ベースビュー名（例: emails.user.password-reset）
     * @param  Subdomain  $subdomain  サブドメイン
     * @return string 解決されたビュー名
     *
     * @throws \InvalidArgumentException
     */
    public function resolveEmailView(string $baseViewName, Subdomain $subdomain): string
    {
        if (! $subdomain) {
            throw new \InvalidArgumentException('サブドメイン情報が必要です。');
        }

        // サブドメイン固有ビュー名を生成
        // emails.user.password-reset -> default.{subdomain}.emails.user.password-reset
        $subdomainViewName = 'default.'.$subdomain->subdomain.'.'.$baseViewName;

        // サブドメイン固有ビューが存在するか確認
        if (view()->exists($subdomainViewName)) {
            return $subdomainViewName;
        }

        // 存在しない場合はデフォルトビューを返す
        return $baseViewName;
    }

    /**
     * トークンを生成し、ユーザーに保存
     *
     * @param  User  $user  ユーザー
     * @return string 生成されたトークン
     */
    public function generateAndStoreToken(User $user): string
    {
        $token = Str::random(30);

        $user->update([
            'remember_token' => $token,
            'password_reset_token_expires_at' => now()->addHour(),
        ]);

        return $token;
    }

    /**
     * パスワードリセットメールを送信
     *
     * @param  User  $user  ユーザー
     * @param  string  $token  トークン
     * @param  Subdomain  $subdomain  サブドメイン
     * @param  string  $resetUrl  リセットURL
     * @param  string  $mailableClass  Mailableクラス名
     * @param  string  $baseViewName  ベースビュー名（例: emails.user.password-reset）
     *
     * @throws \Exception
     */
    public function sendResetEmail(
        User $user,
        string $token,
        Subdomain $subdomain,
        string $resetUrl,
        string $mailableClass,
        string $baseViewName
    ): void {
        try {
            // ビュー名を解決（サブドメイン固有ビューを優先）
            $resolvedViewName = $this->resolveEmailView($baseViewName, $subdomain);

            // メール送信
            Mail::to($user->email)->send(new $mailableClass(
                $user->login_id,
                $resetUrl,
                $subdomain,
                $resolvedViewName
            ));

            // メール送信内容をログファイルに保存
            $subject = $subdomain->system_name.' - パスワードリセットのご案内';
            $body = view($resolvedViewName, [
                'loginId' => $user->login_id,
                'resetUrl' => $resetUrl,
                'subdomain' => $subdomain,
            ])->render();

            $this->mailLogService->logMail($user->email, $subject, $body);

            Log::info('パスワードリセットメール送信', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subdomain_id' => $subdomain->id,
                'view_name' => $resolvedViewName,
            ]);
        } catch (\Exception $e) {
            Log::error('パスワードリセットメール送信エラー', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * トークンを検証し、ユーザーを取得
     *
     * @param  string  $token  トークン
     * @param  string  $roleName  ロール名
     */
    public function validateToken(string $token, string $roleName): ?User
    {
        $user = User::where('remember_token', $token)
            ->where('is_active', 1)
            ->whereHas('role', function ($query) use ($roleName) {
                $query->where('name', $roleName)
                    ->where('is_active', 1);
            })
            ->first();

        if (! $user) {
            return null;
        }

        // トークンの有効期限をチェック
        if (! $user->password_reset_token_expires_at || $user->password_reset_token_expires_at->isPast()) {
            return null;
        }

        return $user;
    }

    /**
     * パスワードをリセット
     *
     * @param  User  $user  ユーザー
     * @param  string  $password  新しいパスワード
     */
    public function resetPassword(User $user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
            'remember_token' => null,
            'password_reset_token_expires_at' => null,
        ]);

        Log::info('パスワードリセット完了', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
