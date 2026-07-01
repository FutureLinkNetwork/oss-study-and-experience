<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserPasswordResetConfirmRequest;
use App\Http\Requests\UserPasswordResetRequest;
use App\Mail\UserPasswordResetMail;
use App\Services\PasswordResetService;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class UserPasswordResetController extends Controller
{
    use HandlesAuth;

    public function __construct(
        private PasswordResetService $passwordResetService
    ) {}

    /**
     * パスワードリセット申請画面を表示
     */
    public function showForgotPasswordForm(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        return view('user.forgot-password', compact('subdomain'));
    }

    /**
     * パスワードリセットリンクを送信
     */
    public function sendResetLink(UserPasswordResetRequest $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'サブドメイン情報を取得できませんでした。'])->withInput();
        }

        // メールアドレスとサブドメインIDでユーザーを全件検索（同一メールの重複含む・subdomain_userロールのみ）
        $users = $this->passwordResetService->findUsersByEmail(
            $request->email,
            $subdomain->id,
            'subdomain_user'
        );

        // ユーザーが見つからない場合でも、セキュリティのため同じメッセージを返す
        if ($users->isEmpty()) {
            return back()->with('status', 'メールアドレス「'.$request->email.'」の登録が見つかりませんでした。');
        }

        try {
            foreach ($users as $user) {
                // トークンを生成して保存（ユーザーごとに別トークン）
                $token = $this->passwordResetService->generateAndStoreToken($user);
                $resetUrl = $request->getSchemeAndHttpHost().'/user/reset?token='.$token;

                $this->passwordResetService->sendResetEmail(
                    $user,
                    $token,
                    $subdomain,
                    $resetUrl,
                    UserPasswordResetMail::class,
                    'emails.user.password-reset'
                );
            }
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'メール送信中にエラーが発生しました。しばらくしてから再度お試しください。'])->withInput();
        }

        return back()->with('status', 'メールアドレスが登録されている場合、パスワードリセットの案内を送信しました。');
    }

    /**
     * パスワードリセット画面を表示
     */
    public function showResetForm(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return redirect()->route('user.forgot-password')
                ->withErrors(['token' => '無効なリンクです。']);
        }

        // トークンを検証
        $user = $this->passwordResetService->validateToken($token, 'subdomain_user');

        if (! $user) {
            return redirect()->route('user.forgot-password')
                ->withErrors(['token' => '無効なリンクです。再度パスワードリセットを申請してください。']);
        }

        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        return view('user.reset-password', compact('subdomain', 'token'));
    }

    /**
     * パスワードリセット処理
     */
    public function reset(UserPasswordResetConfirmRequest $request)
    {
        $token = $request->input('token');

        // トークンを検証
        $user = $this->passwordResetService->validateToken($token, 'subdomain_user');

        if (! $user) {
            return redirect()->route('user.forgot-password')
                ->withErrors(['token' => '無効なリンクです。再度パスワードリセットを申請してください。']);
        }

        // パスワードをリセット
        $this->passwordResetService->resetPassword($user, $request->password);

        return redirect()->route('login')
            ->with('success', 'パスワードをリセットしました。新しいパスワードでログインしてください。');
    }
}
