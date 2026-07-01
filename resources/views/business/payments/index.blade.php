@extends('layouts.app')

@section('title', '支払管理 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mt-4 text-sm">
            <ol class="flex gap-2 text-gray-500">
                <li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><span>支払管理</span></li>
            </ol>
        </nav>

        <div class="mt-4">
            <h1 class="text-2xl font-bold text-gray-900">支払管理</h1>

            @if(session('error'))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    <span class="text-red-700">{{ session('error') }}</span>
                </div>
            @endif

            @if($undownloadedMonths->isNotEmpty())
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-2">
                    <i class="fas fa-info-circle text-amber-600"></i>
                    <span class="text-amber-800">未確認の支払い明細があります。該当月のダウンロードをご確認ください。</span>
                </div>
            @endif

            @if($months->isEmpty())
                <p class="mt-6 p-6 bg-white shadow rounded-lg text-gray-500">支払データはまだありません。</p>
            @else
                <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">申込月</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">申込件数</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">クーポン利用額合計（円）</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($months as $m)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $m['label'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($m['total_count']) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($m['total_amount']) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex flex-wrap items-center justify-center gap-2">
                                                <a href="{{ route('business.payments.pdf', $m['year_month']) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded text-white bg-blue-600 hover:bg-blue-700" target="_blank" rel="noopener">
                                                    <i class="fas fa-file-pdf mr-1"></i>PDF
                                                </a>
                                                <a href="{{ route('business.payments.csv', $m['year_month']) }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50" download>
                                                    <i class="fas fa-file-csv mr-1"></i>CSV
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
