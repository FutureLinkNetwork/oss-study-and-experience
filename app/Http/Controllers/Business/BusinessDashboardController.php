<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessInfo;
use App\Models\BusinessPaymentDownload;
use App\Models\Notice;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessDashboardController extends Controller
{
    use HandlesAuth;

    /**
     * 事業者ダッシュボード画面を表示
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 事業者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        // サブドメインを取得
        $subdomain = $this->getCurrentSubdomain($request);

        // 事業者向けお知らせデータを取得（ページネーション対応）
        $notices = Notice::query()
            ->notDeleted()
            ->published()
            ->businessDashboard()
            ->forSubdomain($subdomain->id)
            ->orderBy('id', 'desc')
            ->paginate(5);

        $businessInfo = BusinessInfo::where('user_id', $user->id)->first();
        $undownloadedQuery = $businessInfo
            ? BusinessPaymentDownload::query()
                ->undownloaded()
                ->forBusiness($businessInfo->id)
                ->forSubdomain($subdomain->id)
            : null;
        $undownloadedPaymentsCount = $undownloadedQuery?->count() ?? 0;
        $hasUndownloadedPayments = $undownloadedPaymentsCount > 0;
        $undownloadedPaymentsLatestCreatedAt = $hasUndownloadedPayments
            ? $undownloadedQuery->orderByDesc('created_at')->first()?->created_at?->format('Y.m.d')
            : null;

        return view('business.dashboard', compact('subdomain', 'notices', 'hasUndownloadedPayments', 'undownloadedPaymentsCount', 'undownloadedPaymentsLatestCreatedAt'));
    }
}
