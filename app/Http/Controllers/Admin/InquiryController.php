<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateInquiryRequest;
use App\Models\Inquiry;
use App\Services\SubdomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InquiryController extends Controller
{
    /**
     * 問い合わせ一覧表示（利用者・事業者）
     */
    public function index(Request $request): View
    {
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;
        if (! $role || $role->level < 60) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        $query = Inquiry::with(['user.beneficiary', 'user.businessInfos'])
            ->forSubdomain($subdomain->id);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where('content', 'LIKE', "%{$keyword}%");
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->filled('inquiry_type') && $request->inquiry_type !== '') {
            $query->where('inquiry_type', $request->inquiry_type);
        }

        $inquiries = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $filters = $request->only(['keyword', 'status', 'inquiry_type']);

        return view('admin.inquiries.index', compact('inquiries', 'filters'));
    }

    /**
     * 問い合わせ詳細表示
     */
    public function show(Request $request, Inquiry $inquiry): View
    {
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;
        if (! $role || $role->level < 60) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        if ($inquiry->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        $inquiry->load(['user.beneficiary', 'user.businessInfos', 'subdomain']);

        return view('admin.inquiries.show', compact('inquiry'));
    }

    /**
     * 問い合わせ更新（ステータス・備考の保存）
     */
    public function update(UpdateInquiryRequest $request, Inquiry $inquiry): RedirectResponse
    {
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;
        if (! $role || $role->level < 60) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        if ($inquiry->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        $inquiry->update([
            'status' => $request->validated('status'),
            'remarks' => $request->validated('remarks'),
            'updated_user_id' => $user->id,
        ]);

        return redirect()->route('admin.inquiries.show', $inquiry)
            ->with('success', '問い合わせを更新しました。');
    }
}
