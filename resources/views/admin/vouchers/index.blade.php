@extends('layouts.app')

@section('title', 'クーポン管理 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>クーポン管理</span></li>
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

            <!-- 絞り込みフォーム -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-filter text-gray-400 mr-2"></i>
                        絞り込み条件
                    </h2>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.vouchers.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- クーポン番号 -->
                            <div>
                                <label for="voucher_number" class="block text-sm font-medium text-gray-700 mb-2">
								クーポン番号
                                </label>
                                <input type="text" 
                                       id="voucher_number" 
                                       name="voucher_number" 
                                       value="{{ old('voucher_number', $filters['voucher_number'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- 対象児童名 -->
                            <div>
                                <label for="child_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    対象児童名
                                </label>
                                <input type="text" 
                                       id="child_name" 
                                       name="child_name" 
                                       value="{{ old('child_name', $filters['child_name'] ?? '') }}"
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
                                    <option value="">全て</option>
                                    @php
                                        $availableStatuses = ['unused' => '未使用', 'used' => '使用中', 'expired' => '期限切れ'];
                                        $selectedStatus = old('status', $filters['status'] ?? '');
                                    @endphp
                                    @foreach($availableStatuses as $value => $label)
                                        <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 発行日（開始） -->
                            <div>
                                <label for="issue_date_from" class="block text-sm font-medium text-gray-700 mb-2">
                                    発行日（開始）
                                </label>
                                <input type="date" 
                                       id="issue_date_from" 
                                       name="issue_date_from" 
                                       value="{{ old('issue_date_from', $filters['issue_date_from'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- 発行日（終了） -->
                            <div>
                                <label for="issue_date_to" class="block text-sm font-medium text-gray-700 mb-2">
                                    発行日（終了）
                                </label>
                                <input type="date" 
                                       id="issue_date_to" 
                                       name="issue_date_to" 
                                       value="{{ old('issue_date_to', $filters['issue_date_to'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>

                        <div class="flex justify-end gap-4 mt-6">
                            <a href="{{ route('admin.vouchers.index') }}" 
                               class="btn-base btn-back btn-m">
                                <i class="fas fa-redo mr-2"></i>リセット
                            </a>
                            <button type="submit" class="btn-base btn-update btn-m">
                                <i class="fas fa-search mr-2"></i>検索
                            </button>
                            <a href="{{ route('admin.vouchers.export-csv', request()->query()) }}" class="btn-base btn-m btn-export">
                                <i class="fas fa-file-csv mr-2"></i>CSV出力
                            </a>
                            <a href="{{ route('admin.vouchers.export-attribute-csv', request()->query()) }}" class="btn-base btn-m btn-export">
                                <i class="fas fa-file-csv mr-2"></i>属性別CSV出力
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- クーポン一覧 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-list text-gray-400 mr-2"></i>
                        クーポン一覧
                    </h2>
                </div>
                <div class="p-6">
                    <div class="">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">クーポン番号</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">発行日</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">有効期限</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">対象児童名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($vouchers as $voucher)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $voucher->voucher_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $voucher->issue_date ? $voucher->issue_date->format('Y-m-d') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $voucher->expiry_date ? $voucher->expiry_date->format('Y-m-d') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($voucher->amount) }}円
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($voucher->beneficiary)
                                                <a href="{{ route('admin.beneficiaries.show', $voucher->beneficiary) }}" 
                                                   class="text-blue-600 hover:text-blue-800 hover:underline">
                                                    {{ $voucher->beneficiary->child_name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusLabels = ['unused' => '未使用', 'used' => '使用中', 'expired' => '期限切れ'];
                                                $statusLabel = $statusLabels[$voucher->status] ?? $voucher->status;
                                                $statusClasses = [
                                                    'unused' => 'bg-green-100 text-green-800',
                                                    'used' => 'bg-blue-100 text-blue-800',
                                                    'expired' => 'bg-red-100 text-red-800',
                                                ];
                                                $statusClass = $statusClasses[$voucher->status] ?? 'bg-gray-100 text-gray-800';
                                            @endphp
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-ticket-alt text-4xl text-gray-300 mb-4"></i>
                                                <p>クーポンがありません。</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($vouchers->hasPages())
                        <div class="mt-6 flex justify-center" id="pagination">
                            {{ $vouchers->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
