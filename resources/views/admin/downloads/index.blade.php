@extends('layouts.app')

@section('title', 'ダウンロード管理 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mt-4 text-sm">
            <ol class="flex gap-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><span>ダウンロード管理</span></li>
            </ol>
        </nav>

        <div class="mt-4">
            <h1 class="text-2xl font-bold text-gray-900">ダウンロード管理</h1>

            @if(session('error'))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-filter text-gray-400 mr-2"></i>
                        検索条件
                    </h2>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.downloads.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="exported_at_from" class="block text-sm font-medium text-gray-700 mb-2">出力日（開始）</label>
                                <input type="date" id="exported_at_from" name="exported_at_from"
                                       value="{{ old('exported_at_from', $filters['exported_at_from'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="exported_at_to" class="block text-sm font-medium text-gray-700 mb-2">出力日（終了）</label>
                                <input type="date" id="exported_at_to" name="exported_at_to"
                                       value="{{ old('exported_at_to', $filters['exported_at_to'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="download_type" class="block text-sm font-medium text-gray-700 mb-2">出力種別</label>
                                <select id="download_type" name="download_type"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">すべて</option>
                                    <option value="user_application" {{ old('download_type', $filters['download_type'] ?? '') === 'user_application' ? 'selected' : '' }}>利用者申請CSV</option>
                                    <option value="beneficiary" {{ old('download_type', $filters['download_type'] ?? '') === 'beneficiary' ? 'selected' : '' }}>利用者CSV（受給者）</option>
                                    <option value="contact" {{ old('download_type', $filters['download_type'] ?? '') === 'contact' ? 'selected' : '' }}>お問い合わせCSV</option>
                                    <option value="inquiry" {{ old('download_type', $filters['download_type'] ?? '') === 'inquiry' ? 'selected' : '' }}>問い合わせ（利用者・事業者）CSV</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="btn-base btn-update btn-m">
                                <i class="fas fa-search mr-2"></i>検索
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-list text-gray-400 mr-2"></i>
                        利用者CSV月次 一覧
                    </h2>
                </div>
                <div class="p-6 overflow-x-auto">
                    @if($downloads->isEmpty())
                        <p class="text-gray-500 text-sm">該当する出力履歴がありません。</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">出力日</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">出力概要</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($downloads as $download)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $download->exported_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $download->summary }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <a href="{{ route('admin.downloads.download', $download) }}"
                                               class="btn-base btn-export btn-m">
                                                <i class="fas fa-download mr-2"></i>ダウンロード
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($downloads->hasPages())
                            <div class="mt-4">
                                {{ $downloads->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
