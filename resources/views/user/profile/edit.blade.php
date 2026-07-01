@extends('layouts.app')

@section('title', '利用者情報管理 - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-green-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('user.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>設定</span></li>
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

        <!-- エラーメッセージ -->
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="px-4 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <!-- メールアドレス表示 -->
                    <div class="mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">メールアドレス</h2>
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="break-words min-w-0 flex-1 max-w-full">
                                <p class="text-sm text-gray-500 mb-1">現在のメールアドレス</p>
                                <p class="text-base font-medium text-gray-900 break-words" style="overflow-wrap: break-word;">{{ $user->email }}</p>
                            </div>
                            <a href="{{ route('user.profile.edit.email') }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium ml-2 mt-4 md:mt-0">
                                <i class="fas fa-edit mr-2"></i>メールアドレスを変更
                            </a>
                        </div>
                    </div>

                    <!-- パスワード変更 -->
                    <div class="border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">パスワード</h2>
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <a href="{{ route('user.profile.edit.password') }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium ml-2 mt-4 md:mt-0">
                                <i class="fas fa-key mr-2"></i>パスワードを変更
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
