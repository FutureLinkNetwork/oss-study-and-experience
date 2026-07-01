@extends('layouts.app')

@section('title', '問い合わせ - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-blue-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex flex-wrap gap-2 text-gray-500">
                <li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><a href="{{ route('business.inquiries.index') }}" class="hover:text-gray-700">問い合わせ</a></li>
                <li><span>/</span></li>
                <li><span>新規問い合わせ</span></li>
            </ol>
        </nav>

        <div class="max-w-3xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">新規問い合わせ</h2>

                    <form method="POST" action="{{ route('business.inquiries.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                                問い合わせ内容 <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                name="content"
                                id="content"
                                rows="8"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('content') border-red-500 @enderror"
                                placeholder="問い合わせ内容を入力してください（2000文字以内）"
                                required
                            >{{ old('content') }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">2000文字以内で入力してください。</p>
                            @error('content')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                送信する
                            </button>
                            <a href="{{ route('business.inquiries.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                キャンセル
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
