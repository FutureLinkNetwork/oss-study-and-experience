@extends('layouts.app')

@section('title', '問い合わせ管理（利用者・事業者） - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex flex-wrap gap-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><span>問い合わせ管理（利用者・事業者）</span></li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
            @if(session('success'))
                <div class="alert-base alert-success alert-m mb-6">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-message">
                        {{ session('success') }}
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert-base alert-error alert-m mb-6">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-message">
                        {{ session('error') }}
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            <!-- 検索フォーム -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-search text-gray-400 mr-2"></i>
                        検索条件
                    </h2>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.inquiries.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                                    フリーワード検索
                                </label>
                                <input type="text"
                                       id="keyword"
                                       name="keyword"
                                       value="{{ old('keyword', $filters['keyword'] ?? '') }}"
                                       placeholder="問い合わせ内容"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                    ステータス
                                </label>
                                <select id="status"
                                        name="status"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">すべて</option>
                                    <option value="pending" {{ old('status', $filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>未処理</option>
                                    <option value="in_progress" {{ old('status', $filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>対応中</option>
                                    <option value="completed" {{ old('status', $filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>処理済み</option>
                                </select>
                            </div>
                            <div>
                                <label for="inquiry_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    種別
                                </label>
                                <select id="inquiry_type"
                                        name="inquiry_type"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">すべて</option>
                                    <option value="user" {{ old('inquiry_type', $filters['inquiry_type'] ?? '') === 'user' ? 'selected' : '' }}>利用者</option>
                                    <option value="business" {{ old('inquiry_type', $filters['inquiry_type'] ?? '') === 'business' ? 'selected' : '' }}>事業者</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end gap-4 mt-4">
                            <a href="{{ route('admin.inquiries.index') }}" class="btn-base btn-back btn-m">
                                <i class="fas fa-redo mr-2"></i>リセット
                            </a>
                            <button type="submit" class="btn-base btn-search btn-m">
                                <i class="fas fa-search mr-2"></i>検索
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 問い合わせ一覧 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-envelope text-gray-400 mr-2"></i>
                        問い合わせ一覧
                    </h2>
                </div>
                <div class="p-6">
                    <div class="">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">問い合わせ日時</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">利用者／事業者名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:200px">内容（先頭15文字）</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">種別</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($inquiries as $inquiry)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $inquiry->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $inquiry->sender_name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ Str::limit($inquiry->content, 15) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $inquiry->inquiry_type->label() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $inquiry->status->value === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($inquiry->status->value === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                                                {{ $inquiry->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.inquiries.show', $inquiry) }}"
                                               class="btn-base btn-update btn-xs">
                                                <i class="fas fa-eye mr-1"></i>詳細
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-envelope text-4xl text-gray-300 mb-4"></i>
                                                <p>問い合わせがありません。</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($inquiries->hasPages())
                        <div class="mt-6 flex justify-center" id="pagination">
                            {{ $inquiries->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
