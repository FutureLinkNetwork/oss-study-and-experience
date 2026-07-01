<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessPasswordChangeRequest;
use App\Models\BusinessInfo;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BusinessPasswordChangeController extends Controller
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
            return redirect()->route('business.dashboard');
        }

        // 事業者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        return view('business.password-change', compact('subdomain'));
    }

    /**
     * パスワード変更処理
     */
    public function update(BusinessPasswordChangeRequest $request)
    {
        $user = Auth::user()->load('role');

        // 既にlast_login_atが設定されている場合はダッシュボードへ
        if ($user->last_login_at !== null) {
            return redirect()->route('business.dashboard');
        }

        // 事業者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        // パスワードを更新
        $user->update([
            'password' => Hash::make($request->password),
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // 対応するBusinessInfoのステータスを「利用中」に更新
        if ($user->id) {
            $businessInfo = BusinessInfo::where('user_id', $user->id)->first();
            if ($businessInfo && $businessInfo->status === '審査通過メール送信済') {
                $businessInfo->update(['status' => '利用中']);

                Log::info('事業者ステータスを自動更新（初回ログイン時パスワード変更）', [
                    'business_id' => $businessInfo->id,
                    'user_id' => $user->id,
                    'old_status' => '審査通過メール送信済',
                    'new_status' => '利用中',
                ]);
            }
        }

        return redirect()->route('business.dashboard')
            ->with('success', 'パスワードを変更しました。');
    }
}
