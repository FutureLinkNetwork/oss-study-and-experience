<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserEmailUpdateRequest;
use App\Http\Requests\UserPasswordUpdateRequest;
use App\Models\Beneficiary;
use App\Services\MailLogService;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    use HandlesAuth;

    public function __construct(
        private MailLogService $mailLogService
    ) {}

    /**
     * 利用者情報管理画面を表示
     */
    public function show(Request $request)
    {
        $user = Auth::user()->load('role');

        // 利用者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_user') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', '利用者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        return view('user.profile.edit', compact('user', 'subdomain'));
    }

    /**
     * メールアドレス変更画面を表示
     */
    public function showEmailEdit(Request $request)
    {
        $user = Auth::user()->load('role');

        // 利用者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_user') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', '利用者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        return view('user.profile.edit-email', compact('user', 'subdomain'));
    }

    /**
     * パスワード変更画面を表示
     */
    public function showPasswordEdit(Request $request)
    {
        $user = Auth::user()->load('role');

        // 利用者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_user') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', '利用者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        return view('user.profile.edit-password', compact('user', 'subdomain'));
    }

    /**
     * メールアドレス更新処理
     */
    public function updateEmail(UserEmailUpdateRequest $request)
    {
        $user = Auth::user()->load('role');

        // 利用者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_user') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', '利用者権限がありません。');
        }

        $oldEmail = $user->email;

        // メールアドレスが変更されている場合のみ更新
        if ($request->email !== $user->email) {
            $user->update(['email' => $request->email]);

            // 対応する利用者のメールアドレスを変更
            $beneficiary = Beneficiary::where('user_id', $user->id)->first();
            if ($beneficiary) {
                $beneficiary->update(['guardian_email' => $request->email]);
            }

            // // メールアドレス変更の確認メールを送信（ログファイルに保存）
            // $subject = 'メールアドレス変更の確認';
            // $body = sprintf(
            //     "%s 様\n\n".
            //     "習い事クーポン管理システムのメールアドレスが変更されました。\n\n".
            //     "旧メールアドレス: %s\n".
            //     "新しいメールアドレス: %s\n".
            //     "変更日時: %s\n\n".
            //     "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
            //     "このメールは自動送信されています。\n".
            //     "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n",
            //     $user->name,
            //     $oldEmail,
            //     $request->email,
            //     now()->format('Y年m月d日 H:i')
            // );

            // $this->mailLogService->logMail($request->email, $subject, $body);

            return redirect()->route('user.profile.edit.email')
                ->with('success', 'メールアドレスを更新しました。');
        }

        return redirect()->route('user.profile.edit.email')
            ->with('info', 'メールアドレスに変更はありません。');
    }

    /**
     * パスワード更新処理
     */
    public function updatePassword(UserPasswordUpdateRequest $request)
    {
        $user = Auth::user()->load('role');

        // 利用者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_user') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', '利用者権限がありません。');
        }

        // 現在のパスワードを確認
        if (! Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['current_password' => '現在のパスワードが正しくありません。']);
        }

        // パスワードを更新
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('user.profile.edit.password')
            ->with('success', 'パスワードを更新しました。');
    }
}
