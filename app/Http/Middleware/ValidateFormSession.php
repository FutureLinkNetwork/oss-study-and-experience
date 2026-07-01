<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateFormSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // セッションが有効かチェック
        if (!$request->session()->has('form_access_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session'
            ], 403);
        }

        // リクエストヘッダーにトークンが含まれているかチェック
        $requestToken = $request->header('X-Form-Token');
        $sessionToken = $request->session()->get('form_access_token');

        if (!$requestToken || $requestToken !== $sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 403);
        }

        // Refererチェック（オプション - 同一ドメインからのアクセスのみ許可）
        $referer = $request->header('Referer');
        $allowedHost = $request->getHost();
        
        if ($referer && !str_contains($referer, $allowedHost)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referer'
            ], 403);
        }

        return $next($request);
    }
}