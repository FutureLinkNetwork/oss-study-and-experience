@extends('layouts.app')

@section('title', '問い合わせ詳細 - '.$subdomain->system_name)

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
                <li><span>詳細</span></li>
            </ol>
        </nav>

        <div class="max-w-3xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 space-y-6">
                    <h2 class="text-lg font-medium text-gray-900">問い合わせ詳細</h2>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">問い合わせ日時</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inquiry->created_at->format('Y年n月j日 H:i') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">問い合わせ内容</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $inquiry->content }}</dd>
                    </div>


                    <div class="pt-4 border-t border-gray-200">
                        <a href="{{ route('business.inquiries.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
