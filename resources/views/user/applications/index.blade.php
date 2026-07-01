@extends('layouts.app')

@section('title', '申込管理 - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-green-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('user.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>クーポン利用履歴</span></li>
            </ol>
        </nav>

        <!-- メインコンテンツ -->
        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
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

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">クーポン利用履歴</h2>

                    @if($applications->count() > 0)
                        <!-- スマートフォン向けカード形式 -->
                        <div class="block sm:hidden space-y-4">
                            @foreach($applications as $application)
                                <a href="{{ route('user.applications.show', $application->id) }}" class="block bg-white border border-gray-200 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                                    <div class="font-medium text-gray-900">{{ $application->classroomInfo->classroom_name ?? '不明' }}</div>
                                    <div class="text-sm text-gray-500 mt-1">{{ $application->used_at->format('Y/m/d') }}</div>
									<div class="text-sm text-gray-500 mt-1">¥{{ number_format($application->amount) }}</div>
                                    <div class="mt-2">
                                        @if($application->is_cancelled)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                キャンセル済み
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                申込済み
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <!-- デスクトップ向けテーブル形式 -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">申込日</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">申し込んだ教室</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($applications as $application)
                                        <tr class="cursor-pointer hover:bg-gray-50" data-href="{{ route('user.applications.show', $application->id) }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $application->used_at->format('Y/m/d') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $application->classroomInfo->classroom_name ?? '不明' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ¥{{ number_format($application->amount) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($application->is_cancelled)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        キャンセル済み
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        申込済み
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <i class="fas fa-chevron-right text-gray-400"></i>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- ページネーション -->
                        @if($applications->hasPages())
                            <div class="mt-6">
                                {{ $applications->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">クーポン利用履歴がありません。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // テーブル行をクリック可能にする
    const rows = document.querySelectorAll('tr[data-href]');
    rows.forEach(function(row) {
        row.addEventListener('click', function() {
            window.location.href = this.getAttribute('data-href');
        });
    });
});
</script>
@endpush
@endsection
