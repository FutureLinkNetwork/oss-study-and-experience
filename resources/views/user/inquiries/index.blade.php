@extends('layouts.app')

@section('title', '問い合わせ - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-green-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex flex-wrap gap-2 text-gray-500">
                <li><a href="{{ route('user.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><span>問い合わせ</span></li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4">
                        <h2 class="text-lg font-medium text-gray-900">問い合わせ</h2>
                        <a href="{{ route('user.inquiries.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <i class="fas fa-plus mr-2"></i>新規問い合わせ
                        </a>
                    </div>

                    @if($inquiries->count() > 0)
                        <div class="block sm:hidden space-y-4">
                            @foreach($inquiries as $inquiry)
                                <a href="{{ route('user.inquiries.show', $inquiry) }}" class="block bg-white border border-gray-200 rounded-lg shadow-sm p-4 hover:shadow-md transition-shadow">
                                    <div class="text-sm text-gray-500">{{ $inquiry->created_at->format('Y/m/d H:i') }}</div>
                                    <div class="font-medium text-gray-900 mt-1">{{ Str::limit($inquiry->content, 40) }}</div>
                                </a>
                            @endforeach
                        </div>

                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">問い合わせ日時</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">内容</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($inquiries as $inquiry)
                                        <tr class="hover:bg-gray-50" data-href="{{ route('user.inquiries.show', $inquiry) }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $inquiry->created_at->format('Y/m/d H:i') }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ Str::limit($inquiry->content, 50) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('user.inquiries.show', $inquiry) }}" class="text-blue-600 hover:text-blue-800">詳細</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($inquiries->hasPages())
                            <div class="mt-6">
                                {{ $inquiries->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">問い合わせがありません。</p>
                            <a href="{{ route('user.inquiries.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                新規問い合わせ
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($inquiries->count() > 0)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tr[data-href]');
    rows.forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (!e.target.closest('a')) {
                window.location.href = this.getAttribute('data-href');
            }
        });
    });
});
</script>
@endpush
@endif
@endsection
