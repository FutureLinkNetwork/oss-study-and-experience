<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class SessionKeepAliveController extends Controller
{
    /**
     * セッションの最終アクセスを更新し、現在の CSRF トークンを返す（長時間入力フォーム用）。
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'token' => csrf_token(),
        ]);
    }
}
