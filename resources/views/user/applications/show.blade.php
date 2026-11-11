@extends('layouts.app')

@section('title', '申込詳細 - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-green-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('user.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('user.applications.index') }}" class="hover:text-gray-700">クーポン利用履歴</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>クーポン利用詳細</span></li>
            </ol>
        </nav>

        <!-- メインコンテンツ -->
        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
            <!-- 成功メッセージ -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- エラーメッセージ -->
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">申込詳細</h2>

                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">教室名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->classroomInfo->classroom_name ?? '不明' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">コース名</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($voucherUsage->courseInfo)
                                    {{ $voucherUsage->courseInfo->course_name }}
                                @else
                                    金額指定利用
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">金額</dt>
                            <dd class="mt-1 text-sm text-gray-900">¥{{ number_format($voucherUsage->amount) }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">備考</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->memo ?? '' }}</dd>
                        </div>

						<div>
                            <dt class="text-sm font-medium text-gray-500">申込日時</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->used_at->format('Y/m/d H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">ステータス</dt>
                            <dd class="mt-1 text-sm">
                                @if($voucherUsage->is_cancelled)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        キャンセル済み
                                    </span>
                                    @if($voucherUsage->cancelled_at)
                                        <span class="ml-2 text-xs text-gray-500">（{{ $voucherUsage->cancelled_at->format('Y/m/d H:i') }}にキャンセル）</span>
                                    @endif
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        申込済み
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <!-- ボタンエリア -->
                    <div class="mt-6 flex flex-col sm:flex-row gap-3">
                        <!-- 戻るボタン -->
                        <a href="{{ route('user.applications.index') }}" class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-arrow-left mr-2"></i>戻る
                        </a>

                        <!-- 再度申し込むボタン（条件を満たさない場合は無効表示） -->
                        @if($canReapply)
                            <a href="{{ route('user.course.application', ['classroom' => $voucherUsage->classroom_info_id, 'course' => $voucherUsage->courseInfo ? $voucherUsage->course_info_id : -1]) }}" 
							class="btn-base btn-create btn-m">
                                <i class="fas fa-redo mr-2"></i>再度申し込む
                            </a>
                        @else
                            <span class="btn-base btn-create btn-m opacity-50 cursor-not-allowed pointer-events-none" aria-disabled="true">
                                <i class="fas fa-redo mr-2"></i>再度申し込む
                            </span>
                        @endif

                        <!-- キャンセルボタン（条件を満たす場合のみ表示） -->
                        @if(!$voucherUsage->is_cancelled)
                            @php
                                $hoursSinceUsed = now()->diffInHours($voucherUsage->used_at);
                                $canCancel = $hoursSinceUsed > -24 && !$voucherUsage->qr_flag;
                            @endphp
                            @if($canCancel)
                                <button 
                                    type="button"
                                    onclick="openCancelModal()"
                                    class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fas fa-times mr-2"></i>キャンセル
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- キャンセル確認モーダル -->
<div id="cancel-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500/50 transition-opacity" style="z-index: 0;" aria-hidden="true" onclick="closeCancelModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" style="z-index: 10;">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">キャンセル確認</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeCancelModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-4">以下の申込をキャンセルしますか？</p>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">申込日時</dt>
                            <dd class="text-sm text-gray-900">{{ $voucherUsage->used_at->format('Y/m/d H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">教室</dt>
                            <dd class="text-sm text-gray-900">{{ $voucherUsage->classroomInfo->classroom_name ?? '不明' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">金額</dt>
                            <dd class="text-sm text-gray-900">¥{{ number_format($voucherUsage->amount) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form action="{{ route('user.applications.cancel', $voucherUsage->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        キャンセルする
                    </button>
					<button type="button" onclick="closeCancelModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    閉じる
                </button>

				</form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openCancelModal() {
    const modal = document.getElementById('cancel-modal');
    modal.classList.remove('hidden');
}

function closeCancelModal() {
    const modal = document.getElementById('cancel-modal');
    modal.classList.add('hidden');
}
</script>
@endpush
@endsection
