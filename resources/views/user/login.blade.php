@extends('layouts.app')

@section('title', '利用者マイページ ログイン - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-16 w-16 flex items-center justify-center bg-green-100 rounded-full">
                <i class="fas fa-user text-green-600 text-2xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                利用者マイページ ログイン
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
			{{ $subdomain->system_name }}
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="login_id" class="sr-only">ログインID</label>
                    <input id="login_id" name="login_id" type="text" autocomplete="username" required
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm @error('login_id') border-red-500 @enderror"
                           placeholder="利用者ログインID" value="{{ old('login_id') }}">
                </div>
                <div>
                    <label for="password" class="sr-only">パスワード</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm @error('login_id') border-red-500 @enderror"
                           placeholder="パスワード">
                    @error('login_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox"
                           class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-900">
                        ログイン状態を保持
                    </label>
                </div>
            </div>

            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-lock text-green-500 group-hover:text-green-400"></i>
                    </span>
                    利用者ログイン
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <div class="mt-2">
                <a href="{{ route('user.forgot-password') }}" class="text-sm text-green-600 hover:text-green-500">
                    ログインID、パスワードを忘れてしまった方
                </a>
            </div>
            <div class="mt-4">
                <a href="{{ route('contact') }}" target="_blank" class="text-xs text-green-600 hover:text-green-500">
                    問い合わせフォーム
                </a>
            </div>
            <div class="mt-2">
                <a href="{{ route('business.login') }}" class="text-xs text-green-600 hover:text-green-500">
                    事業者の方はこちら
                </a>
            </div>
        </div>
    </div>
</div>
@endsection