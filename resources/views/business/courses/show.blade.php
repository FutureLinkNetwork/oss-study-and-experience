@extends('layouts.app')

@section('title', 'コース詳細 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('business.courses.index') }}" class="hover:text-gray-700">コース管理</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>コース詳細</span></li>
            </ol>
        </nav>
    <!-- メインコンテンツ -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">            
            <!-- 成功メッセージ -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
                
                <!-- 基本情報 -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">コース名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $course->course_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">所属教室</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $course->classroomInfo->classroom_name ?? '未設定' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">コース説明</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($course->course_description)
                                    {{ $course->course_description }}
                                @else
                                    <span class="text-gray-400">未設定</span>
                                @endif
                            </dd>
                        </div>
						<div>
							<dt class="text-sm font-medium text-gray-500">対象学年</dt>
							<dd class="mt-1 text-sm text-gray-900">
								@if($course->grades)
									{{ implode(', ', $course->grades) }}
								@else
									<span class="text-gray-400">未設定</span>
								@endif
							</dd>
						</div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1">
                                @if($course->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>有効
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>無効
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- 料金・期間情報 -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">料金・期間情報</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">料金</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">¥{{ number_format($course->price) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">税区分</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($course->tax_type)
                                    {{ $course->tax_type }}
                                @else
                                    <span class="text-gray-400">未設定</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">開始日</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($course->open_date)
                                    {{ is_string($course->open_date) ? $course->open_date : $course->open_date->format('Y年m月d日') }}
                                @else
                                    <span class="text-gray-400">未設定</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">終了日</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($course->end_date)
                                    {{ is_string($course->end_date) ? $course->end_date : $course->end_date->format('Y年m月d日') }}
                                @else
                                    <span class="text-gray-400">未設定</span>
                                @endif
                            </dd>
                        </div>
                        @if($course->open_date && $course->end_date)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">期間</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if(!is_string($course->open_date) && !is_string($course->end_date))
                                        {{ $course->open_date->diffInDays($course->end_date) + 1 }}日間
                                    @else
                                        期間計算不可
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 管理情報 -->
            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">管理情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">作成日時</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $course->created_at->format('Y年m月d日 H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">最終更新日時</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $course->updated_at->format('Y年m月d日 H:i') }}</dd>
                        </div>
                        @if($course->createdUser)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">作成者</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $course->createdUser->name }}</dd>
                            </div>
                        @endif
                        @if($course->updatedUser)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">最終更新者</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $course->updatedUser->name }}</dd>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="mt-6 flex justify-center space-x-4">
                <a href="{{ route('business.courses.edit', $course) }}" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-edit mr-2"></i>このコースを編集
                </a>
                <a href="{{ route('business.courses.duplicate', $course) }}" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                    <i class="fas fa-copy mr-2"></i>このコースを複製
                </a>
            </div>
        </div>
    </div>
</div>
@endsection