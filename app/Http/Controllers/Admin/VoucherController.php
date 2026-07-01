<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\SubdomainService;
use App\Services\VoucherAttributeCsvExportService;
use App\Services\VoucherCsvExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherController extends Controller
{
    /**
     * クーポン一覧表示
     */
    public function index(Request $request): View
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        // 現在表示しているURLのサブドメインを取得
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // クエリビルダーを開始（beneficiaryリレーションをeager load）
        $query = Voucher::with('beneficiary', 'subdomain')
            ->where('subdomain_id', $subdomain->id);

        // 絞り込み条件を適用
        if ($request->filled('voucher_number')) {
            $query->where('voucher_number', 'like', '%'.$request->voucher_number.'%');
        }

        if ($request->filled('child_name')) {
            $query->whereHas('beneficiary', function ($q) use ($request) {
                $q->where('child_name', 'like', '%'.$request->child_name.'%');
            });
        }

        // ステータス検索
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 発行日の範囲検索
        if ($request->filled('issue_date_from')) {
            $query->where('issue_date', '>=', $request->issue_date_from);
        }

        if ($request->filled('issue_date_to')) {
            $query->where('issue_date', '<=', $request->issue_date_to);
        }

        // 登録の新しいものからソート
        $vouchers = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // リクエストパラメータをビューに渡す（絞り込み条件の保持用）
        $filters = $request->only([
            'voucher_number',
            'child_name',
            'status',
            'issue_date_from',
            'issue_date_to',
        ]);

        return view('admin.vouchers.index', compact('vouchers', 'filters', 'user', 'subdomain'));
    }

    /**
     * クーポン一覧をCSV出力（検索絞り込み条件を反映）
     */
    public function exportCsv(Request $request, VoucherCsvExportService $exportService): StreamedResponse|RedirectResponse
    {
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        return $exportService->downloadResponse($request, $subdomain);
    }

    /**
     * 属性別CSV出力（検索絞り込み条件を反映・No./年度/学校名/学年/利用者ラベル/金額/抽出日）
     */
    public function exportAttributeCsv(Request $request, VoucherAttributeCsvExportService $exportService): StreamedResponse|RedirectResponse
    {
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        return $exportService->downloadResponse($request, $subdomain);
    }
}
