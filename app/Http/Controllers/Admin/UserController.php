<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;


class UserController extends Controller
{
    use HandlesAuth;

    /**
     * ユーザー一覧表示
     */
    public function index(Request $request)
    {
        $currentUser = User::with(['role', 'subdomain'])->find(Auth::id());
        
        // サブドメイン内のユーザーのみ表示（グローバル管理者は全て表示）
        $query = User::with(['role', 'subdomain']);
        
        if (!$currentUser->isGlobalAdmin()) {
            $query->where('subdomain_id', $currentUser->subdomain_id);
        }
        
        // 検索機能
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
            });
        }
        
        // ロールでフィルタ
        if ($request->filled('role')) {
            $query->where('role_id', $request->role);
        }
        
        // アクティブ状態でフィルタ
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        
		// サブドメイン情報
		$subdomain = $this->getCurrentSubdomain($request);

        // フィルタ用のロール一覧
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles', 'currentUser', 'subdomain'));
    }

    /**
     * ユーザー新規作成フォーム表示
     */
    public function create(Request $request)
    {
        $currentUser = User::with(['role', 'subdomain'])->find(Auth::id());
        
        // 作成可能なロール（自分以下のレベル）
        $roles = $this->getAvailableRoles($currentUser);

		// サブドメイン情報
		$subdomain = $this->getCurrentSubdomain($request);

		return view('admin.users.user-form', compact('roles', 'currentUser', 'subdomain'));
    }

    /**
     * ユーザー新規作成処理
     */
    public function store(Request $request)
    {
        $currentUser = User::with(['role', 'subdomain'])->find(Auth::id());
        
        $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'login_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users')->where(function ($query) use ($currentUser) {
                    if (!$currentUser->isGlobalAdmin()) {
                        return $query->where('subdomain_id', $currentUser->subdomain_id);
                    }
                    return $query;
                }),
            ],
            'email' => [
                'required',
                'email',
                'max:255'
            ],
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ]);
        
        // ロール権限チェック
        $role = Role::findOrFail($request->role_id);
        if (!$this->canAssignRole($currentUser, $role)) {
            abort(403, '指定されたロールを割り当てる権限がありません。');
        }
        
        $userData = $request->all();
        $userData['password'] = Hash::make($request->password);
        
        // サブドメイン設定（グローバル管理者以外は自分のサブドメイン）
        if (!$currentUser->isGlobalAdmin()) {
            $userData['subdomain_id'] = $currentUser->subdomain_id;
        } else {
            $userData['subdomain_id'] = $request->subdomain_id ?? $currentUser->subdomain_id;
        }
        
        User::create($userData);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザーを作成しました。');
    }

    /**
     * ユーザー編集フォーム表示
     */
    public function edit(Request $request, User $user)
    {
        $currentUser = User::with(['role', 'subdomain'])->find(Auth::id());
        
        // 編集権限チェック
        if (!$this->canEditUser($currentUser, $user)) {
            abort(403, 'このユーザーを編集する権限がありません。');
        }
        
        $roles = $this->getAvailableRoles($currentUser);

		// サブドメイン情報
		$subdomain = $this->getCurrentSubdomain($request);
        
        return view('admin.users.user-form', compact('user', 'roles', 'currentUser', 'subdomain'));
    }

    /**
     * ユーザー更新処理
     */
    public function update(Request $request, User $user)
    {
        $currentUser = User::with(['role', 'subdomain'])->find(Auth::id());
        
        // 編集権限チェック
        if (!$this->canEditUser($currentUser, $user)) {
            abort(403, 'このユーザーを編集する権限がありません。');
        }

		// is_activeをbooleanに変換
		$request['is_active'] = $request->has('is_active') ? true : false;
        
        $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'login_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_.+-@]+$/',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                    return $query->where('subdomain_id', $user->subdomain_id);
                }),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                    return $query->where('subdomain_id', $user->subdomain_id);
                }),
            ],
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ]);
        
        // ロール権限チェック
        $role = Role::findOrFail($request->role_id);
        if (!$this->canAssignRole($currentUser, $role)) {
            abort(403, '指定されたロールを割り当てる権限がありません。');
        }
        
        $userData = $request->except(['password', 'password_confirmation']);
        
        // パスワード更新
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }
        
        $user->update($userData);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザー情報を更新しました。');
    }

    /**
     * ユーザー削除処理（無効化）
     */
    public function destroy(User $user)
    {
        $currentUser = User::with(['role', 'subdomain'])->find(Auth::id());
        
        // 削除権限チェック
        if (!$this->canEditUser($currentUser, $user)) {
            abort(403, 'このユーザーを無効化する権限がありません。');
        }
        
        // 自分自身は無効化できない
        if ($user->id === $currentUser->id) {
            return redirect()->route('admin.users.index')
                ->with('error', '自分自身を無効化することはできません。');
        }
        
        $user->update(['is_active' => false]);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザーを無効化しました。');
    }

    /**
     * 利用可能なロール一覧を取得
     */
    private function getAvailableRoles($currentUser)
    {
        $query = Role::orderBy('level', 'desc');
        
        // グローバル管理者以外は自分以下のレベルのロールのみ
        if (!$currentUser->isGlobalAdmin()) {
            $query->where('level', '<=', $currentUser->role->level)
                  ->where('is_global', false);
        }
        
        return $query->get();
    }

    /**
     * ロール割り当て権限チェック
     */
    private function canAssignRole($currentUser, $role): bool
    {
        // グローバル管理者は全て可能
        if ($currentUser->isGlobalAdmin()) {
            return true;
        }
        
        // 自分以下のレベルかつグローバルでないロールのみ
        return $role->level <= $currentUser->role->level && !$role->is_global;
    }

    /**
     * ユーザー編集権限チェック
     */
    private function canEditUser($currentUser, $targetUser): bool
    {
        // グローバル管理者は全て可能
        if ($currentUser->isGlobalAdmin()) {
            return true;
        }
        
        // 同じサブドメインかつ自分以下のレベルのユーザーのみ
        return $targetUser->subdomain_id === $currentUser->subdomain_id &&
               $targetUser->role->level <= $currentUser->role->level;
    }
}
