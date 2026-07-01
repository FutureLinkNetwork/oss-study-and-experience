<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use HandlesAuth;
    /**
     * ログイン画面を表示
     */
    public function showLoginForm(Request $request)
    {
        return $this->showLoginFormWithClear($request, 'auth.login');
    }

    /**
     * ログイン処理（旧バージョン - 全ロール許可）
     */
    public function login(Request $request)
    {
        // 全ロールを許可（後方互換性のため）
        $allowedRoles = [
            'super_admin', 
            'subdomain_admin', 
            'subdomain_operator', 
            'subdomain_viewer', 
            'subdomain_business', 
            'subdomain_user'
        ];
        $errorMessage = 'ログインIDもしくはパスワードが正しくありません。';
        $redirectRoute = 'admin.dashboard';

        return $this->performLogin($request, $allowedRoles, $errorMessage, $redirectRoute);
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        return $this->performLogout($request, 'login');
    }
}
