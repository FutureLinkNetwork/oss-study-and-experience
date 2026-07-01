@extends('layouts.app')

@section('title', 'クーポンの利用状況管理 - '.($subdomain->system_name ?? ''))

@section('content')
<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mt-4 text-sm">
            <ol class="flex gap-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><span>クーポンの利用状況管理</span></li>
            </ol>
        </nav>

        <div class="mt-4">
            <h1 class="text-2xl font-bold text-gray-900">クーポンの利用状況管理</h1>

            @if(session('success'))
                <div class="mt-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg mb-6 mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-search text-gray-400 mr-2"></i>
                        検索条件
                    </h2>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.coupon-usages.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="used_at_from" class="block text-sm font-medium text-gray-700 mb-2">利用日（から）</label>
                                <input type="date" id="used_at_from" name="used_at_from"
                                       value="{{ old('used_at_from', $filters['used_at_from'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="used_at_to" class="block text-sm font-medium text-gray-700 mb-2">利用日（まで）</label>
                                <input type="date" id="used_at_to" name="used_at_to"
                                       value="{{ old('used_at_to', $filters['used_at_to'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="child_name" class="block text-sm font-medium text-gray-700 mb-2">利用者（児童名）</label>
                                <input type="text" id="child_name" name="child_name"
                                       value="{{ old('child_name', $filters['child_name'] ?? '') }}"
                                       placeholder="児童名で検索"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label for="classroom_name" class="block text-sm font-medium text-gray-700 mb-2">教室名</label>
                                <input type="text" id="classroom_name" name="classroom_name"
                                       value="{{ old('classroom_name', $filters['classroom_name'] ?? '') }}"
                                       placeholder="教室名で検索"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>
                        <div class="flex justify-end gap-4 mt-4">
                            <a href="{{ route('admin.coupon-usages.index') }}" class="btn-base btn-back btn-m">
                                <i class="fas fa-redo mr-2"></i>リセット
                            </a>
                            <button type="submit" class="btn-base btn-search btn-m">
                                <i class="fas fa-search mr-2"></i>検索
                            </button>
                            <a href="{{ route('admin.coupon-usages.export-csv', request()->query()) }}" class="btn-base btn-m btn-export">
                                <i class="fas fa-file-csv mr-2"></i>CSV出力
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-list text-gray-400 mr-2"></i>
                        利用一覧
                    </h2>
                </div>
                @if($usages->isEmpty())
                    <p class="p-6 text-gray-500">該当する利用データはありません。</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">利用日</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">利用者（児童名）</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">教室名</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">金額</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">キャンセル</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($usages as $usage)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $usage->used_at?->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $usage->user?->beneficiary?->child_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $usage->classroomInfo?->classroom_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($usage->amount) }}円</td>
                                        <td class="px-4 py-3 text-sm text-center">
                                            @if($usage->is_cancelled)
                                                <span class="text-red-600">キャンセル済</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <a href="{{ route('admin.coupon-usages.show', $usage) }}" class="text-blue-600 hover:text-blue-800">
                                                詳細
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200" id="pagination">
                        {{ $usages->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
