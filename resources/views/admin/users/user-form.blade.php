@extends('layouts.app')

@php
    // 変数の安全な初期化
    $user = $user ?? null;
    $isEdit = !is_null($user);
@endphp

@section('title', ($isEdit ? 'ユーザー編集' : 'ユーザー新規作成') . ' - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('admin.users.index') }}" class="hover:text-gray-700">ユーザー管理</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>{{ $isEdit ? 'ユーザー編集' : 'ユーザー新規作成' }}</span></li>
            </ol>
        </nav>		
<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">


    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- フォーム -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-{{ $isEdit ? 'edit' : 'user-plus' }} text-gray-400 mr-2"></i>
                {{ $isEdit ? 'ユーザー情報編集' : 'ユーザー情報入力' }}
            </h2>
        </div>
        
        <form method="POST" 
              action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}" 
              class="p-6 space-y-6">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <!-- 基本情報 -->
            <div class="form-row cols-2">
                <!-- ユーザー名 -->
                <div class="field-group">
                    <label for="name" class="field-label required">ユーザー名</label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name', $user->name ?? '') }}"
                           class="field-base field-w-100 @error('name') error @enderror">
                    @error('name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <!-- 表示名 -->
                <div class="field-group">
                    <label for="display_name" class="field-label">表示名</label>
                    <input type="text" name="display_name" id="display_name"
                           value="{{ old('display_name', $user->display_name ?? '') }}"
                           class="field-base field-w-100 @error('display_name') error @enderror">
                    @error('display_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- メールアドレス -->
            <div class="field-group">
                <label for="email" class="field-label required">メールアドレス</label>
                <input type="email" name="email" id="email" required
                       value="{{ old('email', $user->email ?? '') }}"
                       class="field-base field-w-100 @error('email') error @enderror">
                @error('email')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <!-- ログインID -->
            <div class="field-group">
                <label for="login_id" class="field-label required">ログインID</label>
                <input type="text" name="login_id" id="login_id" required
                       value="{{ old('login_id', $user->login_id ?? '') }}"
                       class="field-base field-w-100 @error('login_id') error @enderror">
                @error('login_id')
                    <span class="field-error">{{ $message }}</span>
                @enderror
                <span class="field-help">
                    同一サブドメイン内で一意である必要があります。英数字とアンダースコアのみ使用可能です。
                </span>
            </div>

            <!-- パスワード -->
            <div class="form-row cols-2">
                <div class="field-group">
                    <label for="password" class="field-label {{ $isEdit ? '' : 'required' }}">
                        {{ $isEdit ? '新しいパスワード' : 'パスワード' }}
                    </label>
                    <input type="password" name="password" id="password" {{ $isEdit ? '' : 'required' }}
                           class="field-base field-w-100 @error('password') error @enderror">
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    <span class="field-help">
                        {{ $isEdit ? '変更しない場合は空白にしてください' : '8文字以上で入力してください' }}
                    </span>
                </div>

                <div class="field-group">
                    <label for="password_confirmation" class="field-label {{ $isEdit ? '' : 'required' }}">
                        パスワード確認
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation" {{ $isEdit ? '' : 'required' }}
                           class="field-base field-w-100">
                </div>
            </div>

            <!-- ロール選択 -->
            <div class="field-group">
                <label for="role_id" class="field-label required">ロール</label>
                <select name="role_id" id="role_id" required
                        class="field-base field-select field-w-50 @error('role_id') error @enderror">
                    <option value="">ロールを選択してください</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id ?? '') == $role->id ? 'selected' : '' }}>
                        {{ $role->display_name }} (レベル: {{ $role->level }})
                    </option>
                    @endforeach
                </select>
                @error('role_id')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <!-- アカウント状態 -->
            <div class="field-group">
                <label class="field-label">アカウント状態</label>
                <div class="field-checkbox">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', $user->is_active ?? 1) ? 'checked' : '' }}
                           class="field-checkbox-input">
                    <label for="is_active" class="field-checkbox-label">有効</label>
                </div>
            </div>

            @if($isEdit)
                <!-- ユーザー情報表示（編集時のみ） -->
                <div class="info-panel">
                    <h3 class="info-panel-title">ユーザー情報</h3>
                    <div class="form-row cols-3">
                        <div class="info-item">
                            <dt class="info-label">作成日</dt>
                            <dd class="info-value">{{ $user->created_at->format('Y/m/d H:i') }}</dd>
                        </div>
                        <div class="info-item">
                            <dt class="info-label">最終更新</dt>
                            <dd class="info-value">{{ $user->updated_at->format('Y/m/d H:i') }}</dd>
                        </div>
                        <div class="info-item">
                            <dt class="info-label">最終ログイン</dt>
                            <dd class="info-value">
                                {{ $user->last_login_at ? $user->last_login_at->format('Y/m/d H:i') : '未ログイン' }}
                            </dd>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ボタン -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.users.index') }}" 
                   class="btn-base btn-cancel btn-m">
                    キャンセル
                </a>
                <button type="submit" 
                        class="btn-base {{ $isEdit ? 'btn-update' : 'btn-create' }} btn-m">
                    <i class="fas fa-save mr-2"></i>{{ $isEdit ? '更新' : '作成' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection