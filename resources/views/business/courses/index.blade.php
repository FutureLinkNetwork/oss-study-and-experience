@extends('layouts.app')

@section('title', 'コース管理 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>コース管理</span></li>
            </ol>
        </nav>
	<!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
            <!-- エラーメッセージ -->
            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                {{ session('error') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- コース一覧 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between">
                        <h3 class="text-lg font-medium text-gray-900">コース一覧</h3>
                        <span class="text-sm text-gray-500">{{ $courses->total() }}件のコース</span>
						<a href="{{ route('business.courses.create') }}" 
                       class="btn-base btn-create btn-m">
                        <i class="fas fa-plus mr-2"></i>新規コース作成
                    </a>

                    </div>
                </div>

                @if($courses->count() > 0)
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        コース名
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        教室
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        料金
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        利用可能期間
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
                                @foreach($courses as $course)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $course->course_name }}
                                            </div>
                                            @if($course->course_description)
                                                <div class="text-sm text-gray-500">
                                                    {{ Str::limit($course->course_description, 50) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                {{ $course->classroomInfo->classroom_name ?? '未設定' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                ¥{{ number_format($course->price) }}
                                            </div>
                                            @if($course->tax_type)
                                                <div class="text-sm text-gray-500">
                                                @if($course->tax_type == 'inclusive')
                                                    <span class="text-green-500">内税</span>
                                                @elseif($course->tax_type == 'exclusive')
                                                    <span class="text-red-500">外税</span>
                                                @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                @if($course->open_date || $course->end_date)
                                                    @if($course->open_date)
                                                        {{ is_string($course->open_date) ? $course->open_date : $course->open_date->format('Y/m/d') }}
                                                    @endif
                                                    @if($course->open_date && $course->end_date)
                                                        ～
                                                    @endif
                                                    @if($course->end_date)
                                                        {{ is_string($course->end_date) ? $course->end_date : $course->end_date->format('Y/m/d') }}
                                                    @endif
                                                @else
                                                    未設定
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($course->is_active)
                                                <span class="label-base label-active label-xs">
                                                    <i class="fas fa-check-circle mr-1"></i>有効
                                                </span>
                                            @else
                                                <span class="label-base label-inactive label-xs">
                                                    <i class="fas fa-times-circle mr-1"></i>無効
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('business.courses.show', $course) }}" 
                                                   class="btn-base btn-search btn-xs">
                                                    <i class="fas fa-eye mr-1"></i>詳細
                                                </a>
                                                <a href="{{ route('business.courses.edit', $course) }}" 
                                                   class="btn-base btn-update btn-xs">
                                                    <i class="fas fa-edit mr-1"></i>編集
                                                </a>
                                                <a href="{{ route('business.courses.duplicate', $course) }}" 
                                                   class="btn-base btn-duplicate btn-xs">
                                                    <i class="fas fa-copy mr-1"></i>複製
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- ページネーション -->
                    @if($courses->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $courses->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-chalkboard-teacher text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">コースが登録されていません</h3>
                        <p class="text-gray-500 mb-4">
                            最初のコースを作成して、事業を開始しましょう。
                        </p>
                        <a href="{{ route('business.courses.create') }}" 
                           class="btn-base btn-create btn-m">
                            <i class="fas fa-plus mr-2"></i>新規コース作成
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<!-- 作成・編集完了ダイアログ -->
<div id="course-success-dialog" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="dialog-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" data-dialog-backdrop></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-full bg-green-100 p-3">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="flex-1 pt-0.5">
                    <h3 id="dialog-title" class="text-lg font-medium text-gray-900">完了</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ session('success') }}</p>
                    <div class="mt-4">
                        <button type="button" data-dialog-close
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            閉じる
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var dialog = document.getElementById('course-success-dialog');
    if (!dialog) return;
    function closeDialog() {
        dialog.classList.add('hidden');
    }
    dialog.querySelectorAll('[data-dialog-close], [data-dialog-backdrop]').forEach(function(el) {
        el.addEventListener('click', closeDialog);
    });
})();
</script>
@endif
@endsection