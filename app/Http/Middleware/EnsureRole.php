<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * 許可されたロール名以外のアクセスを 403 で拒否する。
     * 認証済みであること（auth ミドルウェアの後）を前提とする。
     *
     * @param  string  $allowedRoles  許可するロール名（Role.name）。複数は | 区切りで指定
     */
    public function handle(Request $request, Closure $next, string $allowedRoles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'このページにアクセスするにはログインが必要です。');
        }

        $allowed = array_map('trim', explode('|', $allowedRoles));
        $user->load('role');
        $roleName = $user->role?->name;

        if (! $roleName || ! in_array($roleName, $allowed, true)) {
            abort(403, 'このページにアクセスする権限がありません。');
        }

        return $next($request);
    }
}
