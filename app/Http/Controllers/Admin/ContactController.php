<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\SubdomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * お問い合わせ一覧表示
     */
    public function index(Request $request): View
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル60以上のみアクセス可能
        if (! $role || $role->level < 60) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        // 現在表示しているURLのサブドメインを取得
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // クエリビルダーを開始
        $query = Contact::with(['updatedUser', 'subdomain'])
            ->where('subdomain_id', $subdomain->id);

        // フリーワード検索（名前とお問い合わせ内容を対象）
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('content', 'LIKE', "%{$keyword}%");
            });
        }

        // ステータス検索
        if ($request->filled('status') && $request->status !== '') {
            $query->where('is_confirmed', $request->status);
        }

        // 登録の新しいものからソート
        $contacts = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // リクエストパラメータをビューに渡す（絞り込み条件の保持用）
        $filters = $request->only(['keyword', 'status']);

        return view('admin.contacts.index', compact('contacts', 'filters'));
    }

    /**
     * お問い合わせ詳細表示
     */
    public function show(Request $request, Contact $contact): View
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル60以上のみアクセス可能
        if (! $role || $role->level < 60) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        // 現在表示しているURLのサブドメインを取得
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // アクセス権限チェック: 現在表示しているサブドメインのデータのみアクセス可能
        if ($contact->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        $contact->load(['updatedUser', 'subdomain']);

        return view('admin.contacts.show', compact('contact'));
    }

    /**
     * お問い合わせ更新処理
     */
    public function update(Request $request, Contact $contact): RedirectResponse
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル60以上のみアクセス可能
        if (! $role || $role->level < 60) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        // 現在表示しているURLのサブドメインを取得
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // アクセス権限チェック: 現在表示しているサブドメインのデータのみアクセス可能
        if ($contact->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
            'is_confirmed' => 'required|in:0,1,2',
        ]);

        // ラジオボタンの値をbooleanに変換
        $validated['updated_user_id'] = $user->id;

        $contact->update($validated);

        return redirect()->route('admin.contacts.show', $contact)
            ->with('success', 'お問い合わせを更新しました。');
    }
}
