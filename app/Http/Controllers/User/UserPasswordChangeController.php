<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserPasswordChangeRequest;
use App\Traits\HandlesAuth;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserPasswordChangeController extends Controller
{
    use HandlesAuth;

    /**
     * パスワード変更画面を表示
     */
    public function show(Request $request)
    {
        $user = Auth::user()->load('role');

        // 既にlast_login_atが設定されている場合はダッシュボードへ
        if ($user->last_login_at !== null) {
            return redirect()->route('user.dashboard');
        }

        // 利用者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_user') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', '利用者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        return view('user.password-change', compact('subdomain'));
    }

    /**
     * パスワード変更処理
     */
    public function update(UserPasswordChangeRequest $request)
    {
        $user = Auth::user()->load('role');

        // 既にlast_login_atが設定されている場合はダッシュボードへ
        if ($user->last_login_at !== null) {
            return redirect()->route('user.dashboard');
        }

        // 利用者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_user') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', '利用者権限がありません。');
        }

        // パスワードを更新
        $user->update([
            'password' => Hash::make($request->password),
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

		// 対応するbeneficiaryのステータスを「ログイン認証済み」に更新
		if ($user->id) {
			$beneficiary = Beneficiary::where('user_id', $user->id)->first();
			if ($beneficiary && $beneficiary->status == '決定通知書送信済') {
				$beneficiary->update(['status' => 'ログイン認証済み']);
			}
		}

        return redirect()->route('user.dashboard')
            ->with('success', 'パスワードを変更しました。');
    }
}
