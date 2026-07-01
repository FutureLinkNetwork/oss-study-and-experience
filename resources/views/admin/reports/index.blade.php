@extends('layouts.app')

@section('title', 'レポート - ' . ($subdomain->system_name ?? ''))

@section('content')
<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mt-4 text-sm">
            <ol class="flex gap-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><span>レポート</span></li>
            </ol>
        </nav>

        <div class="mt-4">
            <h1 class="text-2xl font-bold text-gray-900">レポート（過去12ヶ月）</h1>

            {{-- グラフ1: クーポン関連 --}}
            <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">クーポン・利用状況</h2>
                </div>
                <div class="p-4">
                    <div class="chart-container" style="position: relative; height: 400px;">
                        <canvas id="adminReportCouponChart"></canvas>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">集計基準</h3>
                        <dl class="text-sm text-gray-600 space-y-1">
                            @foreach($couponDescriptions as $key => $text)
                                <div class="flex gap-2 items-start">
                                    <dt class="font-medium text-gray-700 shrink-0 flex items-center gap-1.5">
                                        <span class="inline-block w-3 h-3 rounded shrink-0 border border-gray-300" style="background-color: {{ $couponColors[$key] ?? '#9ca3af' }}"></span>
                                        {{ \Illuminate\Support\Arr::get([
                                            'issued_user_count' => '月次クーポン発行利用者数:',
                                            'used_user_count' => '月次クーポン利用者数:',
                                            'application_rate' => '月次クーポン利用者申請割合:',
                                            'issued_amount' => '月次クーポン発行金額:',
                                            'used_amount' => '月次クーポン利用金額:',
                                            'balance' => '月次クーポン残高:',
                                            'avg_balance_per_user' => '月次クーポン1人あたり平均残高:',
                                        ], $key, $key . ':') }}
                                    </dt>
                                    <dd class="min-w-0">{{ $text }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </div>

            {{-- グラフ2: 申請・審査の推移 --}}
            <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">申請・審査の推移</h2>
                </div>
                <div class="p-4">
                    <div class="chart-container" style="position: relative; height: 400px;">
                        <canvas id="adminReportApplicationApprovalChart"></canvas>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">集計基準</h3>
                        <p class="text-sm text-gray-600">{{ $applicationApprovalChartDescription }}</p>
                    </div>
                </div>
            </div>

            {{-- グラフ3: 事業者・教室・コース数 --}}
            <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">事業者・教室・コース数</h2>
                </div>
                <div class="p-4">
                    <div class="chart-container" style="position: relative; height: 320px;">
                        <canvas id="adminReportEntityChart"></canvas>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">集計基準</h3>
                        <dl class="text-sm text-gray-600 space-y-1">
                            @foreach($entityDescriptions as $key => $text)
                                <div class="flex gap-2 items-start">
                                    <dt class="font-medium text-gray-700 shrink-0 flex items-center gap-1.5">
                                        <span class="inline-block w-3 h-3 rounded shrink-0 border border-gray-300" style="background-color: {{ $entityColors[$key] ?? '#9ca3af' }}"></span>
                                        {{ \Illuminate\Support\Arr::get([
                                            'business_count' => '月次事業者数:',
                                            'classroom_count' => '月次教室数:',
                                            'course_count' => '月次コース数:',
                                        ], $key, $key . ':') }}
                                    </dt>
                                    <dd class="min-w-0">{{ $text }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </div>

            {{-- グラフ3: 登録事業者の種別分布（積み上げ横棒・12ヶ月） --}}
            <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">登録事業者の種別分布</h2>
                </div>
                <div class="p-4">
                    <div class="chart-container" style="position: relative; height: 400px;">
                        <canvas id="adminReportApplicantTypeChart"></canvas>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">集計基準</h3>
                        <p class="text-sm text-gray-600">{{ $applicantTypeChartDescription }}</p>
                    </div>
                </div>
            </div>

            {{-- グラフ4: クーポン利用の習い事の種別分布（積み上げ横棒・12ヶ月） --}}
            <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">クーポン利用の習い事の種別分布</h2>
                </div>
                <div class="p-4">
                    <div class="chart-container" style="position: relative; height: 400px;">
                        <canvas id="adminReportUsageByLessonCategoryChart"></canvas>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">集計基準</h3>
                        <p class="text-sm text-gray-600">{{ $usageByLessonCategoryChartDescription }}</p>
                    </div>
                </div>
            </div>

            {{-- グラフ5: 月次人気教室トップ20バンプチャート --}}
            <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">月次人気教室トップ20</h2>
                </div>
                <div class="p-4">
                    <div class="chart-container" style="position: relative; height: 400px;">
                        <canvas id="adminReportBumpChart"></canvas>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">集計基準</h3>
                        <p class="text-sm text-gray-600">{{ $bumpChartDescription }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.adminReportChartData = @json($chartData);
</script>
@push('scripts')
    @vite(['resources/js/admin-report-chart.js'])
@endpush
@endsection
