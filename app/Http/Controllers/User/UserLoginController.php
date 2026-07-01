<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\User;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserLoginController extends Controller
{
    use HandlesAuth;

    /**
     * 利用者ログイン画面を表示
     */
    public function showLoginForm(Request $request)
    {
        return $this->showLoginFormWithClear($request, 'user.login');
    }

    /**
     * 利用者ログイン処理
     */
    public function login(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required',
        ]);

        $subdomain = $this->getCurrentSubdomain($request);

        // サブドメイン内でのユーザー認証（ログインIDで検索）
        $user = User::byLoginId($request->login_id, $subdomain->id)
            ->where('is_active', 1)
            ->with('role')
            ->whereHas('role', function ($query) {
                $query->where('name', 'subdomain_user')
                    ->where('is_active', 1);
            })
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login_id' => ['ログインIDもしくはパスワードが正しくありません。または利用者権限がありません。'],
            ]);
        }

        // セッションにログイン
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // 初回ログイン（last_login_atがnull）の場合はパスワード変更画面へ
        if ($user->last_login_at === null) {
            return redirect()->route('user.password.change');
        }

        // 通常のログイン処理（last_login_atを更新）
        Log::info('About to update last login', [
            'user_id' => $user->id,
            'login_id' => $user->login_id,
            'ip' => $request->ip(),
        ]);

        $user->updateLastLogin($request->ip());

        Log::info('Last login updated successfully', [
            'user_id' => $user->id,
            'login_id' => $user->login_id,
        ]);

        // ダッシュボードにリダイレクト
        return redirect()->intended(route('user.dashboard'));
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        return $this->performLogout($request, 'login');
    }
}
