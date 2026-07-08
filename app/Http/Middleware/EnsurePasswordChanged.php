<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * 初回ログイン（last_login_at が null）のユーザーをパスワード変更画面へ強制する。
     * 認証済みであること（auth ミドルウェアの後）を前提とする。
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $passwordChangeRoute  パスワード変更画面のルート名（例: user.password.change）
     */
    public function handle(Request $request, Closure $next, string $passwordChangeRoute): Response
    {
        $user = $request->user();

        if ($user && $user->last_login_at === null) {
            // パスワード変更画面自体（表示・更新）は通す
            if ($request->routeIs($passwordChangeRoute)) {
                return $next($request);
            }

            return redirect()->route($passwordChangeRoute);
        }

        return $next($request);
    }
}
