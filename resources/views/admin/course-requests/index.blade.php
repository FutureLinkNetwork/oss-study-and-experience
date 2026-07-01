@extends('layouts.app')

@section('title', '習い事リクエスト管理 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>習い事リクエスト管理</span></li>
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

            @if(session('info'))
                <div class="alert-base alert-info alert-m mb-6">
                    <div class="alert-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="alert-message">
                        {{ session('info') }}
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
                    <form method="GET" action="{{ route('admin.course-requests.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- フリーワード検索 -->
                            <div>
                                <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                                    フリーワード検索
                                </label>
                                <input type="text" 
                                       id="keyword" 
                                       name="keyword" 
                                       value="{{ old('keyword', $filters['keyword'] ?? '') }}"
                                       placeholder="教室名・教室所在地"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <!-- ステータス -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                    ステータス
                                </label>
                                <select id="status" 
                                        name="status" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">すべて</option>
                                    <option value="0" {{ old('status', $filters['status'] ?? '') == '0' ? 'selected' : '' }}>未処理</option>
                                    <option value="1" {{ old('status', $filters['status'] ?? '') == '1' ? 'selected' : '' }}>対応中</option>
                                    <option value="2" {{ old('status', $filters['status'] ?? '') == '2' ? 'selected' : '' }}>処理済み</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-4 mt-4">
                            <a href="{{ route('admin.course-requests.index') }}" class="btn-base btn-back btn-m">
                                <i class="fas fa-redo mr-2"></i>リセット
                            </a>
                            <button type="submit" class="btn-base btn-search btn-m">
                                <i class="fas fa-search mr-2"></i>検索
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 習い事リクエスト一覧 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-clipboard-list text-gray-400 mr-2"></i>
                        習い事リクエスト一覧
                    </h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">投稿日</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">教室名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($courseRequests as $courseRequest)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $courseRequest->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $courseRequest->classroom_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
										@if($courseRequest->is_confirmed == 2)
	                                       <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    処理済み
                                                </span>
                                            @elseif($courseRequest->is_confirmed == 1)
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    対応中
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    未処理
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.course-requests.show', $courseRequest) }}" 
                                               class="btn-base btn-update btn-xs">
                                                <i class="fas fa-eye mr-1"></i>詳細
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-4"></i>
                                                <p>習い事リクエストがありません。</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($courseRequests->hasPages())
                        <div class="mt-6 flex justify-center" id="pagination">
                            {{ $courseRequests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection




