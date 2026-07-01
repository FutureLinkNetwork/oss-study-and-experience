<?php

namespace App\Traits;

use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait HandlesAuth
{
    /**
     * 現在のサブドメインを取得
     * 
     * @param Request $request
     * @return Subdomain
     */
    protected function getCurrentSubdomain(Request $request): Subdomain
    {
        // ホスト名からサブドメインを判定（簡易実装）
        $host = $request->getHost();
		// サブドメイン名を抽出
		$subdomainName = $this->extractSubdomainFromHost($host);
		
        return Subdomain::where('subdomain', $subdomainName)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * ログイン画面表示時の共通処理（既存セッションのクリア）
     * 
     * @param Request $request
     * @param string $viewName 表示するビュー名
     * @return \Illuminate\View\View
     */
    protected function showLoginFormWithClear(Request $request, string $viewName)
    {
        // 既にログインしている場合はログアウト
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // サブドメインを取得
        $subdomain = $this->getCurrentSubdomain($request);
        
        return view($viewName, compact('subdomain'));
    }

    /**
     * 共通ログイン処理
     * 
     * @param Request $request
     * @param array $allowedRoles 許可されるロール名の配列
     * @param string $errorMessage カスタムエラーメッセージ
     * @param string $redirectRoute ログイン成功時のリダイレクト先ルート名
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function performLogin(Request $request, array $allowedRoles, string $errorMessage, string $redirectRoute)
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
            ->whereHas('role', function($query) use ($allowedRoles) {
                $query->whereIn('name', $allowedRoles)
                      ->where('is_active', 1);
            })
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login_id' => [$errorMessage],
            ]);
        }

        // ログイン情報更新
        Log::info('About to update last login', [
            'user_id' => $user->id,
            'login_id' => $user->login_id,
            'ip' => $request->ip()
        ]);
        
        $user->updateLastLogin($request->ip());

        Log::info('Last login updated successfully', [
            'user_id' => $user->id,
            'login_id' => $user->login_id
        ]);

        // セッションにログイン
        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        // 指定されたルートにリダイレクト
        return redirect()->intended(route($redirectRoute));
    }

    /**
     * 共通ログアウト処理
     * 
     * @param Request $request
     * @param string $redirectRoute ログアウト後のリダイレクト先ルート名
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function performLogout(Request $request, string $redirectRoute)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($redirectRoute);
    }

    /**
     * ホスト名からサブドメイン名を抽出
     * 
     * @param string $host
     * @return string
     */
    protected function extractSubdomainFromHost(string $host): string
    {
        // 例: demo.localhost -> demo
        $parts = explode('.', $host);
        
        // localhostの場合は特別処理
        if (in_array('localhost', $parts)) {
            return $parts[0] === 'localhost' ? 'www' : $parts[0];
        }
        
        // 通常のドメインの場合、最初の部分がサブドメイン
        return $parts[0];
    }

    /**
     * サブドメインが存在し、有効かチェック
     * 
     * @param string $subdomainName
     * @return bool
     */
    protected function isValidSubdomain(string $subdomainName): bool
    {
        return Subdomain::where('subdomain', $subdomainName)
            ->where('is_active', true)
            ->exists();
    }
}