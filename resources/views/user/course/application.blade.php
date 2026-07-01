@extends('layouts.app')

@section('title', ($isAmountSpecified ? '金額指定利用' : ($course ? $course->course_name : '')) . ' - クーポン申し込み - 利用者マイページ')

@section('content')
<div class="min-h-screen bg-green-100">

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('user.dashboard') }}" class="hover:text-gray-700">ダッシュボード</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('user.course.search') }}" class="hover:text-gray-700">習い事検索</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('user.course.show', $classroom->id) }}" class="hover:text-gray-700">{{ $classroom->classroom_name }}</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>クーポン申し込み</span></li>
            </ol>
        </nav>		
	
    <!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
        <div class="px-4 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">クーポン申し込み</h2>

                    <!-- 教室情報 -->
                    <div class="mb-8 border-b border-gray-200 pb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">教室情報</h3>
                        
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">教室名</dt>
                                <dd class="text-sm text-gray-900">{{ $classroom->classroom_name }}</dd>
                            </div>

                            @if($classroom->classroom_introduction)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">紹介文</dt>
                                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $classroom->classroom_introduction }}</dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">住所</dt>
                                <dd class="text-sm text-gray-900">
                                    〒{{ $classroom->classroom_postal_code }}<br>
                                    {{ $classroom->classroom_prefecture }}{{ $classroom->classroom_city }}{{ $classroom->classroom_address1 }}{{ $classroom->classroom_building_name ?? '' }}
                                </dd>
                            </div>

                            @if($classroom->classroom_phone)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">電話番号</dt>
                                    <dd class="text-sm text-gray-900">{{ $classroom->classroom_phone }}</dd>
                                </div>
                            @endif

							@if($business->website_url)
								<div>
									<dt class="text-sm font-medium text-gray-500 mb-1">ウェブサイト</dt>
									<dd class="text-sm text-gray-900"><a href="{{ $business->website_url }}" target="_blank" class="text-blue-600 hover:text-blue-800">{{ $business->website_url }}</a></dd>
								</div>
							@endif

                            @if($classroom->lessonCategoryInfo)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">習い事の種別</dt>
                                    <dd class="text-sm text-gray-900">{{ $classroom->lessonCategoryInfo->name }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <!-- コース情報 -->
                    @if(!$isAmountSpecified && $course)
                    <div class="mb-8 border-b border-gray-200 pb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">コース情報</h3>
                        
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">コース名</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $course->course_name }}</dd>
                            </div>

                            @if($course->course_description)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">コース説明</dt>
                                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $course->course_description }}</dd>
                                </div>
                            @endif

                            @if($course->grades)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">対象学年</dt>
                                    <dd class="text-sm text-gray-900">{{ implode(', ', $course->grades) }}</dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">料金</dt>
                                <dd class="text-sm text-gray-900 font-bold text-lg">¥{{ number_format($usageAmount) }}</dd>
                                <dd class="text-xs text-gray-500 mt-1">
                                    @if($course->tax_type == 'inclusive')
                                        内税（¥{{ number_format($course->price) }}）
                                    @elseif($course->tax_type == 'exclusive')
                                        外税（¥{{ number_format($course->price) }} + 消費税{{ $subdomain->tax_rate ?? 10.0 }}%）
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                    @endif

                    <!-- 金額指定利用モード -->
                    @if($isAmountSpecified)
                    <div class="mb-8 border-b border-gray-200 pb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">利用金額入力</h3>
                        
                        <form id="amount-form">
                            <div class="space-y-4">
                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                        利用金額（税込）<span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           id="amount" 
                                           name="amount" 
                                           min="1" 
                                           step="1"
                                           value="{{ old('amount') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           required>
                                    <p class="mt-1 text-xs text-gray-500">利用可能残高: ¥{{ number_format($availableBalance) }}</p>
                                </div>

                                <div>
                                    <label for="memo" class="block text-sm font-medium text-gray-700 mb-2">
                                        備考（任意）
                                    </label>
                                    <textarea id="memo" 
                                              name="memo" 
                                              rows="4" 
                                              maxlength="1000"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">{{ old('memo') }}</textarea>
                                    <p class="mt-1 text-xs text-gray-500">最大1000文字まで入力可能です</p>
                                </div>

                                <div id="amount-error" class="hidden bg-red-50 border border-red-200 rounded-md p-4">
                                    <p class="text-sm text-red-800">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        <span id="amount-error-message"></span>
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif

                    <!-- クーポン残高情報 -->
                    <div class="mb-8 border-b border-gray-200 pb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">クーポン残高</h3>
                        
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">利用可能残高</dt>
                                <dd class="text-sm text-gray-900 font-bold text-lg" id="available-balance-display">¥{{ number_format($availableBalance) }}</dd>
                            </div>

                            @if(!$isAmountSpecified)
                                @if($canApply)
                                    <div class="bg-green-50 border border-green-200 rounded-md p-4">
                                        <p class="text-sm text-green-800">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            利用可能残高が利用料金以上です。申し込み可能です。
                                        </p>
                                    </div>
                                @else
                                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                                        <p class="text-sm text-red-800">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            利用可能残高が不足しています。申し込みできません。
                                        </p>
                                    </div>
                                @endif
                            @else
                                <div id="amount-validation-message" class="hidden">
                                    <!-- JavaScriptで動的に表示 -->
                                </div>
                            @endif
                        </dl>
                    </div>

                    <!-- 申込みボタン -->
                    <div class="flex justify-center gap-4">
                        <a href="{{ route('user.course.show', $classroom->id) }}" class="btn-base btn-back btn-m">
                            戻る
                        </a>
                        @if($isAmountSpecified)
                            <button type="button" id="apply-button" class="btn-base btn-create btn-m opacity-50 cursor-not-allowed" disabled onclick="openConfirmModal()">
                                申し込む
                            </button>
                        @else
                            @if($canApply)
                                <button type="button" class="btn-base btn-create btn-m" onclick="openConfirmModal()">
                                    申し込む
                                </button>
                            @else
                                <button type="button" class="btn-base btn-create btn-m opacity-50 cursor-not-allowed" disabled>
                                    申し込む
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 確認モーダル -->
<div id="confirm-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500/50 transition-opacity" style="z-index: 0;" aria-hidden="true" onclick="closeConfirmModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" style="z-index: 10;">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">申し込み確認</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeConfirmModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-700 mb-4">
                        以下の内容でクーポンを利用して申し込みますか？
                    </p>
                    <dl class="space-y-2 bg-gray-50 p-4 rounded-md" id="confirm-details">
						<div class="flex justify-between">
							<dt class="text-sm font-medium text-gray-500">教室名</dt>
							<dd class="text-sm text-gray-900">{{ $classroom->classroom_name }}</dd>
						</div>					
                        @if(!$isAmountSpecified && $course)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">コース名</dt>
                            <dd class="text-sm text-gray-900">{{ $course->course_name }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">利用料金</dt>
                            <dd class="text-sm text-gray-900 font-bold" id="confirm-amount">¥{{ number_format($usageAmount) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">利用可能残高</dt>
                            <dd class="text-sm text-gray-900">¥{{ number_format($availableBalance) }}</dd>
                        </div>
                        @if($isAmountSpecified)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">備考</dt>
                            <dd class="text-sm text-gray-900" id="confirm-memo">{{ old('memo') ?: 'なし' }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form method="POST" action="{{ route('user.course.application.store', ['classroom' => $classroom->id, 'course' => $isAmountSpecified ? -1 : ($course ? $course->id : -1)]) }}" id="confirm-form" class="inline">
                    @csrf
                    @if($isAmountSpecified)
                    <input type="hidden" name="amount" id="confirm-amount-input">
                    <input type="hidden" name="memo" id="confirm-memo-input">
                    @endif
					<button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeConfirmModal()">
                    キャンセル
	                </button>
					<button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        申し込む
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($isAmountSpecified)
const availableBalance = {{ $availableBalance }};
const amountInput = document.getElementById('amount');
const memoInput = document.getElementById('memo');
const applyButton = document.getElementById('apply-button');
const amountError = document.getElementById('amount-error');
const amountErrorMessage = document.getElementById('amount-error-message');
const amountValidationMessage = document.getElementById('amount-validation-message');

function validateAmount() {
    const amount = parseInt(amountInput.value) || 0;
    
    // エラーメッセージを非表示
    amountError.classList.add('hidden');
    amountValidationMessage.classList.add('hidden');
    
    if (amount <= 0) {
        applyButton.disabled = true;
        applyButton.classList.add('opacity-50', 'cursor-not-allowed');
        return false;
    }
    
    if (amount > availableBalance) {
        amountError.classList.remove('hidden');
        amountErrorMessage.textContent = '利用可能残高を超えています。利用可能残高: ¥' + availableBalance.toLocaleString();
        applyButton.disabled = true;
        applyButton.classList.add('opacity-50', 'cursor-not-allowed');
        return false;
    }
    
    // 有効な金額の場合
    amountValidationMessage.classList.remove('hidden');
    amountValidationMessage.className = 'bg-green-50 border border-green-200 rounded-md p-4';
    amountValidationMessage.innerHTML = '<p class="text-sm text-green-800"><i class="fas fa-check-circle mr-2"></i>利用可能残高が利用金額以上です。申し込み可能です。</p>';
    
    applyButton.disabled = false;
    applyButton.classList.remove('opacity-50', 'cursor-not-allowed');
    return true;
}

// 金額入力欄の変更を監視
amountInput.addEventListener('input', validateAmount);
amountInput.addEventListener('change', validateAmount);

// 初期バリデーション
validateAmount();
@endif

function openConfirmModal() {
    @if($isAmountSpecified)
    const amount = parseInt(amountInput.value) || 0;
    const memo = memoInput.value || 'なし';
    
    if (!validateAmount()) {
        return;
    }
    
    // 確認モーダルに値を設定
    document.getElementById('confirm-amount').textContent = '¥' + amount.toLocaleString();
    document.getElementById('confirm-memo').textContent = memo;
    document.getElementById('confirm-amount-input').value = amount;
    document.getElementById('confirm-memo-input').value = memoInput.value;
    @endif
    
    const modal = document.getElementById('confirm-modal');
    modal.classList.remove('hidden');
}function closeConfirmModal() {
    const modal = document.getElementById('confirm-modal');
    modal.classList.add('hidden');
}
</script>
@endpush
