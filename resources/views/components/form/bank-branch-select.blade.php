@props([
    'bankName' => 'bank_code',
    'branchName' => 'branch_code',
    'bankValue' => '',
    'branchValue' => '',
    'bankRequired' => true,
    'branchRequired' => true,
    'bankLabel' => '銀行名',
    'branchLabel' => '支店名',
    'disabled' => false,
    'mode' => 'input', // 'input' or 'confirm'
    'data' => [],
    'formToken' => null
])

@if($mode === 'confirm')
    <!-- 確認画面での表示 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 銀行名 -->
        <div class="md:col-span-2">
            <label class="field-label {{ $bankRequired ? 'required' : '' }} label-l">{{ $bankLabel }}</label>
            <p class="mt-1 text-gray-900">
                {{ $bankValue ? ($data['bank_name_display'] ?? $bankValue) : '未選択' }}
            </p>
            <input type="hidden" name="{{ $bankName }}" value="{{ $bankValue }}">
        </div>

        <!-- 支店名 -->
        <div class="md:col-span-2">
            <label class="field-label {{ $bankRequired ? 'required' : '' }} label-l">{{ $branchLabel }}</label>
            <p class="mt-1 text-gray-900">
                {{ $branchValue ? ($data['branch_name_display'] ?? $branchValue) : '未選択' }}
            </p>
            <input type="hidden" name="{{ $branchName }}" value="{{ $branchValue }}">
        </div>
    </div>
@else
    <!-- 入力画面でのセレクトボックス -->
    <div class="form-row cols-2">
        <!-- 銀行名 -->
		<label class="field-label {{ $bankRequired ? 'required' : '' }} label-l">{{ $bankLabel }}</label>
		<select name="{{ $bankName }}" 
				id="bank_select_{{ $bankName }}" 
				class="field-base field-select field-w-100 bank-select"
				{{ $bankRequired ? 'required' : '' }}
				{{ $disabled ? 'disabled' : '' }}
				data-branch-target="branch_select_{{ $branchName }}">
			<option value="">銀行を選択してください</option>
		</select>
	</div>

	<!-- 支店名 -->
	<div class="form-row cols-2">
		<label class="field-label {{ $bankRequired ? 'required' : '' }} label-l">{{ $branchLabel }}</label>
		<select name="{{ $branchName }}" 
				id="branch_select_{{ $branchName }}" 
				class="field-base field-select field-w-100 branch-select"
				{{ $branchRequired ? 'required' : '' }}
				{{ $disabled ? 'disabled' : '' }}
				disabled>
			<option value="">まず銀行を選択してください</option>
		</select>
    </div>

    @push('scripts')
    <script>
    (function() {
        'use strict';
        
        function initializeBankBranchComponent() {
            const bankSelect = document.getElementById('bank_select_{{ $bankName }}');
            const branchSelect = document.getElementById('branch_select_{{ $branchName }}');
            
            if (!bankSelect || !branchSelect) {
                return;
            }

            // jQueryとSelect2の確認（グローバルスコープから）
            if (typeof window.$ === 'undefined' || window.$ === null) {
                return;
            }
            
            if (typeof window.$.fn === 'undefined' || typeof window.$.fn.select2 === 'undefined') {
                return;
            }

            // window.$を明示的に使用
            const $ = window.$;
            
            // 銀行一覧を読み込み
            fetch('/api/banks', {
                headers: {
                    'Accept': 'application/json',
                    'X-Form-Token': '{{ $formToken ?? session("form_access_token") ?? "" }}',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 現在の選択肢をクリア（最初のoptionは残す）
                        bankSelect.innerHTML = '<option value="">銀行を選択してください</option>';
                        
                        // 銀行オプションを追加
                        data.data.forEach(bank => {
                            const option = document.createElement('option');
                            option.value = bank.value;
                            option.textContent = bank.text;
                            if ('{{ $bankValue }}' === bank.value) {
                                option.selected = true;
                            }
                            bankSelect.appendChild(option);
                        });

                        // Select2を初期化
                        try {
                            $(bankSelect).select2({
                                placeholder: '銀行を選択してください',
                                allowClear: true,
                                width: '100%'
                            });
                            
                            // Select2のchangeイベントをバインド
                            $(bankSelect).on('change', function() {
                                const bankCode = this.value;
                                
                                // 支店セレクトをリセット
                                branchSelect.innerHTML = '<option value="">支店を選択してください</option>';
                                branchSelect.disabled = !bankCode;
                                
                                // 支店のSelect2を再初期化
                                try {
                                    if ($(branchSelect).hasClass('select2-hidden-accessible')) {
                                        $(branchSelect).select2('destroy');
                                    }
                                    $(branchSelect).select2({
                                        placeholder: '支店を選択してください',
                                        allowClear: true,
                                        width: '100%'
                                    });
                                } catch (e) {
                                    // 支店Select2再初期化エラー
                                }

                                if (bankCode) {
                                    loadBranches(bankCode);
                                }
                            });
                        } catch (e) {
                            // 銀行Select2初期化エラー
                        }

                        // 初期値がある場合は支店を読み込み
                        if ('{{ $bankValue }}') {
                            loadBranches('{{ $bankValue }}');
                        }
                    }
                })
                .catch(error => {
                    // 銀行一覧の取得エラー
                });

            function loadBranches(bankCode) {
                fetch(`/api/branches?bank_code=${bankCode}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Form-Token': '{{ $formToken ?? session("form_access_token") ?? "" }}',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            branchSelect.innerHTML = '<option value="">支店を選択してください</option>';
                            
                            data.data.forEach(branch => {
                                const option = document.createElement('option');
                                option.value = branch.value;
                                option.textContent = branch.text;
                                if ('{{ $branchValue }}' === branch.value) {
                                    option.selected = true;
                                }
                                branchSelect.appendChild(option);
                            });

                            branchSelect.disabled = false;

                            // Select2を初期化
                            try {
                                if ($(branchSelect).hasClass('select2-hidden-accessible')) {
                                    $(branchSelect).select2('destroy');
                                }
                                $(branchSelect).select2({
                                    placeholder: '支店を選択してください',
                                    allowClear: true,
                                    width: '100%'
                                });
                            } catch (e) {
                                // 支店Select2初期化エラー
                            }
                        }
                    })
                    .catch(error => {
                        // 支店一覧の取得エラー
                    });
            }
        }
        
        // select2Readyイベントをリッスン
        function handleSelect2Ready() {
            initializeBankBranchComponent();
        }
        
        // DOM読み込み完了時の処理
        function setupEventListeners() {
            function waitForjQuery(attempt = 1) {
                const maxAttempts = 10; // 短縮（1秒間）
                
                // より厳密なjQueryチェック
                const hasJQuery = (typeof window.$ !== 'undefined' && window.$ !== null && typeof window.$.fn !== 'undefined');
                const hasSelect2 = (hasJQuery && typeof window.$.fn.select2 !== 'undefined');
                
                if (hasJQuery && hasSelect2) {
                    try {
                        // window.$を明示的に使用
                        window.$(document).on('select2Ready', handleSelect2Ready);
                        
                        // すでにライブラリが利用可能な場合は即座に初期化
                        initializeBankBranchComponent();
                        return; // 成功したので終了
                    } catch (error) {
                        // イベントリスナー設定エラー
                    }
                } else if (attempt < maxAttempts) {
                    setTimeout(() => waitForjQuery(attempt + 1), 100);
                } else {
                    // 最終的に利用可能かもしれないので、試してみる
                    if (typeof window.$ !== 'undefined' && typeof window.$.fn !== 'undefined' && typeof window.$.fn.select2 !== 'undefined') {
                        try {
                            window.$(document).on('select2Ready', handleSelect2Ready);
                            initializeBankBranchComponent();
                        } catch (error) {
                            // Final attempt failed
                        }
                    }
                }
            }
            
            // jQueryの待機を開始
            waitForjQuery();
        }
        
        // イベントリスナーを登録
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setupEventListeners();
            });
        } else {
            setupEventListeners();
        }
    })();
    </script>
    @endpush
@endif