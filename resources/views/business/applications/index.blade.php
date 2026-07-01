@extends('layouts.app')

@section('title', '申込管理 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>クーポン受付管理</span></li>
            </ol>
        </nav>
	<!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">

            <!-- 成功メッセージ -->
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

            <!-- エラーメッセージ -->
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

            <!-- 検索フォーム -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">検索条件</h3>
                </div>
                <div class="px-6 py-4">
                    <form id="search-form" method="GET" action="{{ route('business.applications.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- 申込み年 -->
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                    申し込み年
                                </label>
                                <select id="year" name="year" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">すべて</option>
                                    @php
                                        $currentYear = now()->year;
                                        $startYear = 2026; // サービス開始年
                                        for ($year = $currentYear; $year >= $startYear; $year--) {
                                            $selected = $searchYear == $year ? 'selected' : '';
                                            echo "<option value=\"{$year}\" {$selected}>{$year}年</option>";
                                        }
                                    @endphp
                                </select>
                            </div>

                            <!-- 申込み月 -->
                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                                    申し込み月
                                </label>
                                <select id="month" name="month" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">すべて</option>
                                    @for($month = 1; $month <= 12; $month++)
                                        <option value="{{ $month }}" {{ $searchMonth == $month ? 'selected' : '' }}>
                                            {{ $month }}月
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <!-- 申込教室 -->
                            <div>
                                <label for="classroom_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    申込教室
                                </label>
                                <select id="classroom_id" name="classroom_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">すべて</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" {{ $searchClassroomId == $classroom->id ? 'selected' : '' }}>
                                            {{ $classroom->classroom_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 申込者名 -->
                            <div>
                                <label for="applicant_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    申込者名
                                </label>
                                <input type="text" id="applicant_name" name="applicant_name" 
                                       value="{{ $searchApplicantName }}" 
                                       placeholder="申込者名で検索"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <!-- キャンセル状態 -->
                            <div>
                                <label for="cancelled" class="block text-sm font-medium text-gray-700 mb-2">
                                    キャンセル状態
                                </label>
                                <select id="cancelled" name="cancelled" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="all" {{ $searchCancelled === 'all' ? 'selected' : '' }}>すべて</option>
                                    <option value="only" {{ $searchCancelled === 'only' ? 'selected' : '' }}>キャンセル済みのみ</option>
                                    <option value="exclude" {{ $searchCancelled === 'exclude' ? 'selected' : '' }}>キャンセル済みを除く</option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="flex justify-center gap-2 mt-4">
                        <button type="submit" form="search-form" class="btn-base btn-search btn-m">
                            <i class="fas fa-search mr-2"></i>検索
                        </button>
                        <a href="{{ route('business.applications.index') }}" class="btn-base btn-back btn-m">
                            <i class="fas fa-redo mr-2"></i>リセット
                        </a>
                        @if($applications->total() > 0)
                            <form method="GET" action="{{ route('business.applications.export') }}" class="inline">
                                @if($searchYear !== null && $searchYear !== '')
                                    <input type="hidden" name="year" value="{{ $searchYear }}">
                                @endif
                                @if($searchMonth !== null && $searchMonth !== '')
                                    <input type="hidden" name="month" value="{{ $searchMonth }}">
                                @endif
                                @if($searchClassroomId !== null && $searchClassroomId !== '')
                                    <input type="hidden" name="classroom_id" value="{{ $searchClassroomId }}">
                                @endif
                                @if($searchApplicantName !== null && $searchApplicantName !== '')
                                    <input type="hidden" name="applicant_name" value="{{ $searchApplicantName }}">
                                @endif
                                @if($searchCancelled !== null && $searchCancelled !== '')
                                    <input type="hidden" name="cancelled" value="{{ $searchCancelled }}">
                                @endif
                                <button type="submit" class="btn-base btn-update btn-m">
                                    <i class="fas fa-file-csv mr-2"></i>CSVダウンロード
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 申込一覧 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">受付一覧</h3>
                        <span class="text-sm text-gray-500">{{ $applications->total() }}件の受付</span>
                    </div>
                </div>

                @if($applications->count() > 0)
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        申込日
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        申込教室
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        コース
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        金額
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        申込者名
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        状態
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($applications as $application)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ $application->used_at->format('Y/m/d H:i') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                {{ $application->classroomInfo->classroom_name ?? '不明' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                @if($application->courseInfo)
                                                    {{ $application->courseInfo->course_name }}
                                                @else
                                                    金額指定利用
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                ¥{{ number_format($application->amount) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                {{ $application->user->name ?? '不明' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($application->is_cancelled)
                                                <span class="label-base label-inactive label-xs">
                                                    <i class="fas fa-times-circle mr-1"></i>キャンセル済み
                                                </span>
                                            @else
                                                <span class="label-base label-active label-xs">
                                                    <i class="fas fa-check-circle mr-1"></i>申込済み
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <a href="{{ route('business.applications.show', $application) }}" 
                                               class="btn-base btn-update btn-xs">
                                                <i class="fas fa-eye mr-1"></i>詳細
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- ページネーション -->
                    @if($applications->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200" id="pagination">
                            {{ $applications->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-clipboard-list text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">申込みがありません</h3>
                        <p class="text-gray-500">
                            検索条件を変更して再度お試しください。
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
