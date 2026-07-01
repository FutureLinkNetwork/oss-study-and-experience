@extends('layouts.app')

@section('title', '支払集計 - '.($subdomain->system_name ?? ''))

@section('content')
<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mt-4 text-sm">
            <ol class="flex gap-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><span>支払集計</span></li>
            </ol>
        </nav>

        <div class="mt-4">
            <h1 class="text-2xl font-bold text-gray-900">支払集計</h1>
            @if(isset($adminDownloadLastCreatedAt) && $adminDownloadLastCreatedAt)
                <p class="mt-1 text-sm text-gray-500">最終更新時刻: {{ $adminDownloadLastCreatedAt->format('Y-m-d H:i') }}</p>
            @endif

            @if(session('error'))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if($availableMonths->isNotEmpty())
            <form method="GET" action="{{ route('admin.payments.index') }}" class="mt-4 flex flex-wrap items-center gap-4">
                <label for="month" class="text-sm font-medium text-gray-700">申込月</label>
                <select name="month" id="month" class="rounded border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" onchange="this.form.submit()">
                    @foreach($availableMonths as $m)
                        <option value="{{ $m['value'] }}" {{ $selectedMonth === $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </form>
            @endif

            @if($selectedMonth)
            <div class="mt-4 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-sm font-medium text-gray-900">会計用月次レポート（申込月 {{ $selectedMonthLabel }}）</h2>
                </div>
                <div class="p-4">
                    @if($accountingReport)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">区分</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CSV</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CSVダウンロード日時</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PDF</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PDFダウンロード日時</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">公金振替対象</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if(!empty($accountingReport->csv_s3_key))
                                            <a href="{{ route('admin.accounting-reports.download-csv', ['month' => $selectedMonth, 'category' => 'target']) }}"
                                               class="btn-base btn-export btn-m">
                                                CSVをダウンロード
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $accountingReport->csv_downloaded_at ? $accountingReport->csv_downloaded_at->format('Y-m-d H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if(!empty($accountingReport->pdf_s3_key))
                                            <a href="{{ route('admin.accounting-reports.download-pdf', ['month' => $selectedMonth, 'category' => 'target']) }}"
                                               class="btn-base btn-export btn-m">
                                                PDFをダウンロード
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $accountingReport->pdf_downloaded_at ? $accountingReport->pdf_downloaded_at->format('Y-m-d H:i') : '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">公金振替対象外</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if(!empty($accountingReport->csv_s3_key_non_target))
                                            <a href="{{ route('admin.accounting-reports.download-csv', ['month' => $selectedMonth, 'category' => 'non_target']) }}"
                                               class="btn-base btn-export btn-m">
                                                CSVをダウンロード
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $accountingReport->csv_non_target_downloaded_at ? $accountingReport->csv_non_target_downloaded_at->format('Y-m-d H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if(!empty($accountingReport->pdf_s3_key_non_target))
                                            <a href="{{ route('admin.accounting-reports.download-pdf', ['month' => $selectedMonth, 'category' => 'non_target']) }}"
                                               class="btn-base btn-export btn-m">
                                                PDFをダウンロード
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $accountingReport->pdf_non_target_downloaded_at ? $accountingReport->pdf_non_target_downloaded_at->format('Y-m-d H:i') : '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-sm text-gray-600">この月の会計用月次レポートはまだ生成されていません。</p>
                    @endif
                </div>
            </div>
            @endif

            @if($selectedMonth)
                @php
                    $hasTargetAggregates = $aggregates->contains(fn ($agg) => $agg->is_public_funds_transfer_target);
                    $hasNonTargetAggregates = $aggregates->contains(fn ($agg) => ! $agg->is_public_funds_transfer_target);
                @endphp
                <div class="mt-4 flex flex-wrap items-center gap-4">
                    <span class="text-sm font-medium text-gray-700 w-full sm:w-auto">全銀フォーマット</span>
                    @if($hasTargetAggregates)
                        <a href="{{ route('admin.payments.download-zengin', ['month' => $selectedMonth, 'category' => 'target']) }}"
                           class="btn-base btn-export btn-m">
                            公金振替対象
                        </a>
                    @else
                        <button type="button" disabled class="btn-base btn-export btn-m">
                            公金振替対象
                        </button>
                    @endif
                    @if($hasNonTargetAggregates)
                        <a href="{{ route('admin.payments.download-zengin', ['month' => $selectedMonth, 'category' => 'non_target']) }}"
                           class="btn-base btn-export btn-m">
                            公金振替対象外
                        </a>
                    @else
                        <button type="button" disabled class="btn-base btn-export btn-m">
                            公金振替対象外
                        </button>
                    @endif
                </div>
            @endif

            <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
                @if($aggregates->isEmpty())
                    <p class="p-6 text-gray-500">この月の集計データはありません。</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">事業者名</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">公金振替</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">教室名</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">申込件数</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">クーポン利用額合計（円）</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">支払通知PDF</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($aggregates as $agg)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $agg->businessInfo?->business_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $agg->is_public_funds_transfer_target ? '対象' : '対象外' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $agg->classroomInfo?->classroom_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($agg->application_count) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($agg->total_amount) }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <a href="{{ route('admin.payments.pdf', ['month' => $selectedMonth, 'business_id' => $agg->business_info_id]) }}"
                                               class="btn-base btn-export btn-m"
                                               target="_blank"
                                               rel="noopener">
                                                PDFをダウンロード
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
