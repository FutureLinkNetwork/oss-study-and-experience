@extends('layouts.app')

@section('title', 'ユーザー管理 - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>ユーザー管理</span></li>
            </ol>
        </nav>		
	


	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">

    <!-- 検索・フィルタ -->
    <div class="filter-panel">
        <form method="GET" action="{{ route('admin.users.index') }}">
            <div class="form-row cols-4">
                <!-- 検索 -->
                <div class="field-group">
                    <label for="search" class="field-label">検索</label>
                    <input type="text" name="search" id="search" 
                           value="{{ request('search') }}"
                           placeholder="名前・メールで検索"
                           class="field-base field-w-100">
                </div>

                <!-- ロールフィルタ -->
                <div class="field-group">
                    <label for="role" class="field-label">ロール</label>
                    <select name="role" id="role" 
                            class="field-base field-select field-w-100">
                        <option value="">全てのロール</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- ステータスフィルタ -->
                <div class="field-group">
                    <label for="status" class="field-label">ステータス</label>
                    <select name="status" id="status" 
                            class="field-base field-select field-w-100">
                        <option value="">全てのステータス</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>有効</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>無効</option>
                    </select>
                </div>

                <!-- 検索ボタン -->
                <div class="field-group">
                    <label class="field-label">&nbsp;</label>
                    <button type="submit" 
                            class="btn-base btn-search btn-m w-full">
                        <i class="fas fa-search mr-2"></i>検索
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ユーザー一覧テーブル -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between">
            <h2 class="text-lg font-medium text-gray-900">
                ユーザー一覧 ({{ $users->total() }}件)
            </h2>
            @if($currentUser->hasLevelOrAbove(60))
            <a href="{{ route('admin.users.create') }}" 
               class="btn-base btn-create btn-m">
                <i class="fas fa-plus mr-2"></i>新規ユーザー作成
            </a>
            @endif
        </div>
        
        @if($users->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ユーザー情報
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            サブドメイン
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ロール
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ステータス
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            最終ログイン
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $user->display_name ?? $user->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: {{ $user->login_id }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $user->email }}
                                    </div>
                                </div>
                            </div>
                        </td>
						<td>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $user->subdomain->name }}
                            </span>
						</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                @if($user->role->level >= 80) bg-red-100 text-red-800
                                @elseif($user->role->level >= 60) bg-yellow-100 text-yellow-800
                                @elseif($user->role->level >= 40) bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $user->role->display_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>有効
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>無効
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('Y/m/d H:i') }}
                            @else
                                <span class="text-gray-400">未ログイン</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                @if($currentUser->hasLevelOrAbove(60))
                                <a href="{{ route('admin.users.edit', $user) }}" 
                                   class="btn-base btn-update btn-xs">
                                    <i class="fas fa-edit mr-1"></i>編集
                                </a>
                                @endif
                                
                                @if($currentUser->hasLevelOrAbove(80) && $user->id !== $currentUser->id)
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" 
                                      class="inline" 
                                      onsubmit="return confirm('このユーザーを無効化しますか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-base btn-disable btn-xs">
                                        <i class="fas fa-times mr-1"></i>無効化
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200" id="pagination">
            {{ $users->withQueryString()->links() }}
        </div>
        @else
        <div class="px-6 py-12 text-center">
            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">ユーザーが見つかりません</h3>
            <p class="text-gray-500 mb-6">検索条件を変更してください。</p>
            @if($currentUser->hasLevelOrAbove(60))
            <a href="{{ route('admin.users.create') }}" 
               class="btn-base btn-create btn-m">
                <i class="fas fa-plus mr-2"></i>新規ユーザー作成
            </a>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection