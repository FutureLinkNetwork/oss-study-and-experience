<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseRequestController extends Controller
{
    /**
     * 習い事リクエスト一覧表示
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

        // クエリビルダーを開始
        $query = CourseRequest::with(['updatedUser', 'subdomain'])
            ->where('subdomain_id', $user->subdomain_id);

        // フリーワード検索（教室名と教室所在地を対象）
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('classroom_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('address', 'LIKE', "%{$keyword}%");
            });
        }

        // ステータス検索
        if ($request->filled('status') && $request->status !== '') {
            $query->where('is_confirmed', $request->status);
        }

        // 登録の新しいものからソート
        $courseRequests = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // リクエストパラメータをビューに渡す（絞り込み条件の保持用）
        $filters = $request->only(['keyword', 'status']);

        return view('admin.course-requests.index', compact('courseRequests', 'filters'));
    }

    /**
     * 習い事リクエスト詳細表示
     */
    public function show(CourseRequest $courseRequest): View
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

        // アクセス権限チェック: ログインユーザーのサブドメインのデータのみアクセス可能
        if ($courseRequest->subdomain_id !== $user->subdomain_id) {
            abort(403, 'アクセス権限がありません。');
        }

        $courseRequest->load(['updatedUser', 'subdomain']);

        return view('admin.course-requests.show', compact('courseRequest'));
    }

    /**
     * 習い事リクエスト更新処理
     */
    public function update(Request $request, CourseRequest $courseRequest): RedirectResponse
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

        // アクセス権限チェック: ログインユーザーのサブドメインのデータのみアクセス可能
        if ($courseRequest->subdomain_id !== $user->subdomain_id) {
            abort(403, 'アクセス権限がありません。');
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
            'is_confirmed' => 'required|in:0,1,2',
        ]);

        // ラジオボタンの値をbooleanに変換
        $validated['updated_user_id'] = $user->id;

        $courseRequest->update($validated);

        return redirect()->route('admin.course-requests.show', $courseRequest)
            ->with('success', '習い事リクエストを更新しました。');
    }
}
