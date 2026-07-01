<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use HandlesAuth;
    /**
     * 管理者ログイン画面を表示
     */
    public function showLoginForm(Request $request)
    {
        return $this->showLoginFormWithClear($request, 'admin.login');
    }

    /**
     * 管理者ログイン処理
     */
    public function login(Request $request)
    {
        // 管理者ロールのみ許可（super_admin, subdomain_admin, subdomain_operator, subdomain_viewer）
        $allowedRoles = ['super_admin', 'subdomain_admin', 'subdomain_operator', 'subdomain_viewer'];
        $errorMessage = 'ログインIDもしくはパスワードが正しくありません。または管理者権限がありません。';
        $redirectRoute = 'admin.dashboard';
		$subdomain = $this->getCurrentSubdomain($request);

        return $this->performLogin($request, $allowedRoles, $errorMessage, $redirectRoute, $subdomain);
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        return $this->performLogout($request, 'admin.login');
    }
}
