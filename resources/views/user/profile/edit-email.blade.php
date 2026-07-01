@extends('layouts.app')

@section('title', 'メールアドレス変更 - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-green-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('user.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('user.profile.edit') }}" class="hover:text-gray-700">設定</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>メールアドレス変更</span></li>
            </ol>
        </nav>		
	
    <!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
        <!-- 成功メッセージ -->
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- 情報メッセージ -->
        @if(session('info'))
            <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('info') }}</span>
            </div>
        @endif

        <!-- エラーメッセージ -->
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="px-4 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form method="POST" action="{{ route('user.profile.update.email') }}" id="email-update-form">
                        @csrf
                        @method('PUT')

                        <!-- 現在のメールアドレス表示 -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                現在のメールアドレス
                            </label>
                            <p class="text-base text-gray-900 bg-gray-50 px-3 py-2 rounded-md border border-gray-300">
                                {{ $user->email }}
                            </p>
                        </div>

                        <!-- 新しいメールアドレス -->
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                新しいメールアドレス <span class="text-red-500">*</span>
                            </label>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                   value="{{ old('email', $user->email) }}"
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('email') border-red-500 @enderror"
                                   placeholder="example@example.com">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 送信ボタン -->
                        <div class="mt-6 flex justify-end space-x-4">
                            <a href="{{ route('user.profile.edit') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md text-sm font-medium">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i>メールアドレスを更新
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
