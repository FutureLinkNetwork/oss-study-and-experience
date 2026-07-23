@extends('layouts.app')

@section('title', '利用者管理 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>利用者管理</span></li>
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

            <!-- CSV取り込みフォーム -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-file-upload text-gray-400 mr-2"></i>
                        CSV取り込み
                    </h2>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.beneficiaries.import') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="flex items-center space-x-4">
                            <input type="file" 
                                   name="csv_file" 
                                   accept=".csv,.txt"
                                   required
                                   class="block w-full text-sm text-gray-500 border border-gray-300 rounded-md px-3 py-2 bg-white file:mr-4 file:py-2 file:px-4 file:rounded-md file:border file:border-gray-300 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 hover:file:border-blue-300 cursor-pointer">
                            <button type="submit" class="btn-base btn-update btn-m btn-m w-64">
                                <i class="fas fa-upload mr-2"></i>取込
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 絞り込みフォーム -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-filter text-gray-400 mr-2"></i>
                        絞り込み条件
                    </h2>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.beneficiaries.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
							<!-- こどもID -->
                            <div>
                                <label for="child_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    こどもID
                                </label>
                                <input type="text" 
                                       id="child_id" 
                                       name="child_id" 
                                       value="{{ old('child_id', $filters['child_id'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

						<!-- 就学援助認定番号 -->
                            <div>
                                <label for="certification_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    就学援助認定番号
                                </label>
                                <input type="text" 
                                       id="certification_number" 
                                       name="certification_number" 
                                       value="{{ old('certification_number', $filters['certification_number'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- 保護者名 -->
                            <div>
                                <label for="guardian_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    保護者名
                                </label>
                                <input type="text" 
                                       id="guardian_name" 
                                       name="guardian_name" 
                                       value="{{ old('guardian_name', $filters['guardian_name'] ?? '') }}"
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
                                        $availableStatuses = ['決定通知書未送信', '決定通知書送信待ち', '決定通知書送信失敗', '決定通知書送信済', 'ログイン認証済み', '資格喪失予定', '資格喪失'];
                                        $selectedStatus = old('status', $filters['status'] ?? '');
                                    @endphp
                                    @foreach($availableStatuses as $status)
                                        <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }}>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- ラベル -->
                            <div class="md:col-span-2 lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ラベル
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                    @php
                                        $availableLabels = ['DV避難等'];
                                        $selectedLabels = old('labels', $filters['labels'] ?? []);
                                        if (!is_array($selectedLabels)) {
                                            $selectedLabels = [];
                                        }
                                    @endphp
                                    @foreach($availableLabels as $label)
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="label_{{ $loop->index }}" 
                                                   name="labels[]" 
                                                   value="{{ $label }}"
                                                   {{ in_array($label, $selectedLabels) ? 'checked' : '' }}
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="label_{{ $loop->index }}" class="ml-2 text-sm text-gray-700">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 mt-6">
                            <a href="{{ route('admin.beneficiaries.index') }}" 
                               class="btn-base btn-secondary btn-m">
                                <i class="fas fa-redo mr-2"></i>リセット
                            </a>
                            <button type="submit" class="btn-base btn-search btn-m">
                                <i class="fas fa-search mr-2"></i>検索
                            </button>
                            <a href="{{ route('admin.beneficiaries.export', request()->only(['child_id', 'certification_number', 'guardian_name', 'child_name', 'status', 'labels'])) }}"
                               class="btn-base btn-export btn-m">
                                <i class="fas fa-file-csv mr-2"></i>CSV出力
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 利用者一覧 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-list text-gray-400 mr-2"></i>
                        利用者一覧
                    </h2>
                    @if($hasPendingBulkSend)
                        <span class="inline-flex items-center px-4 py-2 rounded-md bg-amber-100 text-amber-800 text-sm font-medium">
                            メール送信処理中
                        </span>
                    @elseif($isStatusNotSentSearch && $hasResults)
                        <form method="POST" action="{{ route('admin.beneficiaries.send-bulk-login-info') }}" class="inline-flex items-center gap-3" onsubmit="return confirmBulkLoginInfoSend(this);">
                            @csrf
                            @foreach(['child_id', 'certification_number', 'guardian_name', 'child_name', 'status'] as $filterKey)
                                @if(!empty($filters[$filterKey]))
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ $filters[$filterKey] }}">
                                @endif
                            @endforeach
                            @if(!empty($filters['labels']) && is_array($filters['labels']))
                                @foreach($filters['labels'] as $label)
                                    @if($label !== null && $label !== '')
                                        <input type="hidden" name="labels[]" value="{{ $label }}">
                                    @endif
                                @endforeach
                            @endif
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="issue_voucher" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                クーポン付与
                            </label>
                            <button type="submit" class="btn-base btn-update btn-m">
                                <i class="fas fa-envelope mr-2"></i>メール一括送信
                            </button>
                        </form>
                        <script>
                            function confirmBulkLoginInfoSend(form) {
                                const issueVoucher = form.querySelector('input[name="issue_voucher"]')?.checked;
                                const message = issueVoucher
                                    ? '決定通知書未送信の利用者にログイン情報を一括送信し、送信成功時にクーポンを付与しますか？'
                                    : '決定通知書未送信の利用者にログイン情報を一括送信しますか？';
                                return confirm(message);
                            }
                        </script>
                    @endif
                </div>
                <div class="p-6">
                    <div class="">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">申請日</th>
                                    <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">こどもID</th>
                                    <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">就学援助認定番号</th>
                                    <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">保護者名</th>
                                    <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">対象児童名</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ラベル</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($beneficiaries as $beneficiary)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-2 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $beneficiary->application_date ? $beneficiary->application_date->format('Y-m-d') : '' }}
                                        </td>
                                        <td class="px-2 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $beneficiary->child_id ?? '-' }}
                                        </td>
                                        <td class="px-2 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $beneficiary->certification_number }}
                                        </td>
                                        <td class="px-2 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $beneficiary->guardian_name }}
                                        </td>
                                        <td class="px-2 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $beneficiary->child_name }}
                                        </td>
                                        <td class="px-2 py-4 text-center whitespace-nowrap">
                                            @php
                                                $status = $beneficiary->status ?? '決定通知書未送信';
                                                $statusColors = [
                                                    '決定通知書未送信' => 'bg-gray-100 text-gray-800',
                                                    '決定通知書送信待ち' => 'bg-amber-100 text-amber-800',
                                                    '決定通知書送信失敗' => 'bg-red-100 text-red-800',
                                                    '決定通知書送信済' => 'bg-blue-100 text-blue-800',
                                                    'ログイン認証済み' => 'bg-green-100 text-green-800',
                                                    '資格喪失予定' => 'bg-yellow-100 text-yellow-800',
                                                    '資格喪失' => 'bg-red-100 text-red-800',
                                                ];
                                                $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                            @endphp
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">
                                                {{ $status }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-4 text-center text-sm text-gray-900">
                                            @if($beneficiary->labels)
                                                @foreach($beneficiary->labels_array as $label)
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 mr-1">
                                                        {{ $label }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-4 text-center text-sm font-medium">
                                            <a href="{{ route('admin.beneficiaries.show', $beneficiary) }}" 
                                               class="btn-base btn-update btn-xs">
                                                <i class="fas fa-eye mr-1"></i>詳細
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-2 py-8 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                                <p>利用者がありません。</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($beneficiaries->hasPages())
                        <div class="mt-6 flex justify-center" id="pagination">
                            {{ $beneficiaries->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

