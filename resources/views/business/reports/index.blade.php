@extends('layouts.app')

@section('title', 'レポート - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>レポート</span></li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">

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

            <!-- 年度選択 -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">会計年度を選択</h3>
                </div>
                <div class="px-6 py-4">
                    <form method="GET" action="{{ route('business.reports.index') }}" class="flex flex-wrap items-end gap-4">
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 mb-2">会計年度</label>
                            <select id="year" name="year" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm min-w-[140px]">
                                <option value="">選択してください</option>
                                @foreach($availableYears as $y)
                                    <option value="{{ $y }}" {{ $selectedYear === $y ? 'selected' : '' }}>
                                        {{ $y }}年度（{{ $y }}年4月〜{{ $y + 1 }}年3月）
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn-base btn-search btn-m">
                            <i class="fas fa-search mr-2"></i>表示
                        </button>
                    </form>
                </div>
            </div>

            @if($selectedYear === null)
                <div class="bg-white shadow rounded-lg px-6 py-12 text-center">
                    <i class="fas fa-chart-bar text-gray-400 text-4xl mb-4"></i>
                    @if(count($availableYears) === 0)
                        <p class="text-gray-600">申込データがまだありません。</p>
                    @else
                        <p class="text-gray-600">会計年度を選択して「表示」を押してください。</p>
                    @endif
                </div>
            @else
                {{-- グラフ用データ（スクリプトより前に必ず出力） --}}
                @if(count($chartMonthLabels) > 0 || count($chartClassrooms) > 0)
                    <script>
                        window.businessReportChartData = {
                            monthLabels: @json($chartMonthLabels ?? []),
                            classrooms: @json($chartClassrooms ?? []),
                        };
                    </script>
                @else
                    <script>
                        window.businessReportChartData = { monthLabels: [], classrooms: [] };
                    </script>
                @endif

                {{-- グラフ（教室別・月別 件数・金額 2軸） --}}
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">教室別・月別 申込件数・金額（{{ $selectedYear }}年度）</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="chart-container" style="position: relative; height: 320px;">
                            <canvas id="reportChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Chart.js は同期的にローカルから読み込み（CDN不要・確実に表示） --}}
                <script src="{{ asset('js/chart.umd.min.js') }}"></script>
                <script>
(function() {
  var data = window.businessReportChartData;
  var canvas = document.getElementById('reportChart');
  if (!data || !canvas || typeof Chart === 'undefined') return;
  var ctx = canvas.getContext('2d');
  if (!ctx) return;

  var COLOR_PALETTE = [
    { count: 'rgba(59, 130, 246, 0.7)', countBorder: 'rgb(59, 130, 246)' },
    { count: 'rgba(234, 88, 12, 0.7)', countBorder: 'rgb(234, 88, 12)' },
    { count: 'rgba(139, 92, 246, 0.7)', countBorder: 'rgb(139, 92, 246)' },
    { count: 'rgba(236, 72, 153, 0.7)', countBorder: 'rgb(236, 72, 153)' },
    { count: 'rgba(20, 184, 166, 0.7)', countBorder: 'rgb(20, 184, 166)' }
  ];
  var AMOUNT_COLOR = { amount: 'rgba(34, 197, 94, 0.6)', amountBorder: 'rgb(34, 197, 94)' };

  var datasets = [];
  var classrooms = data.classrooms || [];
  for (var i = 0; i < classrooms.length; i++) {
    var cls = classrooms[i];
    var color = COLOR_PALETTE[i % COLOR_PALETTE.length];
    datasets.push({
      label: cls.name + ' 件数',
      data: cls.counts || [],
      backgroundColor: color.count,
      borderColor: color.countBorder,
      borderWidth: 1,
      yAxisID: 'yCount'
    });
    datasets.push({
      label: cls.name + ' 金額',
      data: cls.amounts || [],
      backgroundColor: AMOUNT_COLOR.amount,
      borderColor: AMOUNT_COLOR.amountBorder,
      borderWidth: 1,
      yAxisID: 'yAmount'
    });
  }

  var monthLabels = (data.monthLabels && data.monthLabels.length > 0)
    ? data.monthLabels
    : ['4月','5月','6月','7月','8月','9月','10月','11月','12月','1月','2月','3月'];

  new Chart(ctx, {
    type: 'bar',
    data: { labels: monthLabels, datasets: datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'top' } },
      scales: {
        x: { display: true, grid: { display: false } },
        yCount: {
          type: 'linear',
          position: 'left',
          display: true,
          title: { display: true, text: '申込件数' },
          beginAtZero: true,
          min: 0,
          ticks: { stepSize: 1 }
        },
        yAmount: {
          type: 'linear',
          position: 'right',
          display: true,
          title: { display: true, text: '申込金額（円）' },
          beginAtZero: true,
          min: 0,
          grid: { drawOnChartArea: false }
        }
      }
    }
  });
})();
                </script>

                {{-- 月別内訳 --}}
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">月別内訳（{{ $selectedYear }}年4月〜{{ $selectedYear + 1 }}年3月）</h3>
                    </div>
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">月</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">申込件数</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">申込金額（円）</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($monthlyData as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['label'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($row['count']) }}件</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">¥{{ number_format($row['amount']) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 font-medium">
                                    <td class="px-6 py-4 text-sm text-gray-900">合計</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ number_format(collect($monthlyData)->sum('count')) }}件</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">¥{{ number_format(collect($monthlyData)->sum('amount')) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 教室別内訳 --}}
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">教室別内訳（{{ $selectedYear }}年度）</h3>
                    </div>
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">教室名</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">申込件数</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">申込金額（円）</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($classroomData as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $row['name'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($row['count']) }}件</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">¥{{ number_format($row['amount']) }}</td>
                                    </tr>
                                @endforeach
                                @if(count($classroomData) > 0)
                                    <tr class="bg-gray-50 font-medium">
                                        <td class="px-6 py-4 text-sm text-gray-900">合計</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ number_format(collect($classroomData)->sum('count')) }}件</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 text-right">¥{{ number_format(collect($classroomData)->sum('amount')) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
