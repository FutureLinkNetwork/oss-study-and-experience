@extends('layouts.app')

@section('title', '申込詳細 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('business.applications.index') }}" class="hover:text-gray-700">クーポン受付管理</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>クーポン受付詳細</span></li>
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

            <!-- 申込詳細 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">クーポン受付詳細</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">申込日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $application->used_at->format('Y/m/d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">申込教室</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $application->classroomInfo->classroom_name ?? '不明' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">コース</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($application->courseInfo)
                                    {{ $application->courseInfo->course_name }}
                                @else
                                    金額指定利用
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">金額</dt>
                            <dd class="mt-1 text-sm text-gray-900">¥{{ number_format($application->amount) }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">申込者名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $application->user->name ?? '不明' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">備考</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $application->memo ?? '（なし）' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">状態</dt>
                            <dd class="mt-1 text-sm">
                                @if($application->is_cancelled)
                                    <span class="label-base label-inactive label-xs">
                                        <i class="fas fa-times-circle mr-1"></i>キャンセル済み
                                    </span>
                                    @if($application->cancelled_at)
                                        <span class="ml-2 text-xs text-gray-500">（{{ $application->cancelled_at->format('Y/m/d H:i') }}にキャンセル）</span>
                                    @endif
                                @else
                                    <span class="label-base label-active label-xs">
                                        <i class="fas fa-check-circle mr-1"></i>申込済み
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <!-- 事業者メモ（編集可能） -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-2">事業者メモ</dt>
                            <dd class="mt-1">
                                <form method="POST" action="{{ route('business.applications.update', $application) }}" class="space-y-2">
                                    @csrf
                                    @method('PUT')
                                    <textarea name="business_memo" 
                                              id="business_memo" 
                                              rows="5" 
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                              placeholder="事業者メモを入力してください">{{ old('business_memo', $application->business_memo) }}</textarea>
                                    @error('business_memo')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <div class="flex gap-2 w-full justify-end">
										<a href="{{ route('business.applications.index') }}" class="btn-base btn-back btn-m">
                				            <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
				                        </a>

										<button type="submit" class="btn-base btn-update btn-m">
                                            <i class="fas fa-save mr-2"></i>保存
                                        </button>
									</div>
                                </form>
                            </dd>
                        </div>
                    </dl>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
