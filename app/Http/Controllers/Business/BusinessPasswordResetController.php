<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessPasswordResetConfirmRequest;
use App\Http\Requests\BusinessPasswordResetRequest;
use App\Mail\BusinessPasswordResetMail;
use App\Services\PasswordResetService;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class BusinessPasswordResetController extends Controller
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

        return view('business.forgot-password', compact('subdomain'));
    }

    /**
     * パスワードリセットリンクを送信
     */
    public function sendResetLink(BusinessPasswordResetRequest $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'サブドメイン情報を取得できませんでした。'])->withInput();
        }

        // メールアドレスとサブドメインIDでユーザーを検索（subdomain_businessロールのみ）
        $user = $this->passwordResetService->findUserByEmail(
            $request->email,
            $subdomain->id,
            'subdomain_business'
        );

        // ユーザーが見つからない場合でも、セキュリティのため同じメッセージを返す
        if (! $user) {
            return back()->with('status', 'メールアドレス「'.$request->email.'」の登録が見つかりませんでした。');
        }

        // トークンを生成して保存
        $token = $this->passwordResetService->generateAndStoreToken($user);

        // パスワードリセットURLを生成（利用中のドメインを使用）
        $resetUrl = $request->getSchemeAndHttpHost().'/business/reset?token='.$token;

        try {
            // メール送信
            $this->passwordResetService->sendResetEmail(
                $user,
                $token,
                $subdomain,
                $resetUrl,
                BusinessPasswordResetMail::class,
                'emails.business.password-reset'
            );
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
            return redirect()->route('business.forgot-password')
                ->withErrors(['token' => '無効なリンクです。']);
        }

        // トークンを検証
        $user = $this->passwordResetService->validateToken($token, 'subdomain_business');

        if (! $user) {
            return redirect()->route('business.forgot-password')
                ->withErrors(['token' => '無効なリンクです。再度パスワードリセットを申請してください。']);
        }

        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        return view('business.reset-password', compact('subdomain', 'token'));
    }

    /**
     * パスワードリセット処理
     */
    public function reset(BusinessPasswordResetConfirmRequest $request)
    {
        $token = $request->input('token');

        // トークンを検証
        $user = $this->passwordResetService->validateToken($token, 'subdomain_business');

        if (! $user) {
            return redirect()->route('business.forgot-password')
                ->withErrors(['token' => '無効なリンクです。再度パスワードリセットを申請してください。']);
        }

        // パスワードをリセット
        $this->passwordResetService->resetPassword($user, $request->password);

        return redirect()->route('business.login')
            ->with('success', 'パスワードをリセットしました。新しいパスワードでログインしてください。');
    }
}
