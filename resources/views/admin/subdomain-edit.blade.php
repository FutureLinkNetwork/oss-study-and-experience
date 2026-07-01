@extends('layouts.app')

@section('title', 'システム管理 - ' . $subdomain->system_name)

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li class="text-gray-900">システム管理</li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- フォーム -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-edit text-gray-400 mr-2"></i>
                        サブドメイン情報編集
                    </h2>
                </div>

                <form method="POST" action="{{ route('admin.subdomain.update') }}" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- システム名 -->
                    <div class="field-group">
                        <label for="system_name" class="field-label required">システム名</label>
                        <input type="text" name="system_name" id="system_name" required
                               value="{{ old('system_name', $subdomain->system_name ?? '') }}"
                               class="field-base field-w-100 @error('system_name') error @enderror">
                        @error('system_name')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- 説明 -->
                    <div class="field-group">
                        <label for="description" class="field-label">説明</label>
                        <textarea name="description" id="description" rows="4"
                                  class="field-base field-w-100 @error('description') error @enderror">{{ old('description', $subdomain->description ?? '') }}</textarea>
                        @error('description')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- クーポン設定 -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-md font-medium text-gray-900 mb-4">
                            <i class="fas fa-ticket-alt text-gray-400 mr-2"></i>
                            クーポン設定
                        </h3>

                        <!-- クーポン金額 -->
                        <div class="field-group">
                            <label for="voucher_amount" class="field-label required">クーポン金額（円）</label>
                            <input type="number" name="voucher_amount" id="voucher_amount" required
                                   min="0" step="1"
                                   value="{{ old('voucher_amount', $subdomain->voucher_amount ?? 0) }}"
                                   class="field-base field-w-100 @error('voucher_amount') error @enderror">
                            @error('voucher_amount')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- クーポン有効期限 -->
                        <div class="field-group">
                            <label for="voucher_expiry" class="field-label required">クーポン有効期限（月数）</label>
                            <div class="flex items-center gap-4">
                                <input type="number" name="voucher_expiry" id="voucher_expiry" required
                                       min="0" step="1"
                                       value="{{ old('voucher_expiry', $subdomain->voucher_expiry ?? 0) }}"
                                       class="field-base field-w-80 @error('voucher_expiry') error @enderror">
                                <span id="expiry-display" class="text-sm text-gray-600"></span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">0を入力すると年度末（3月31日）まで有効となります</p>
                            @error('voucher_expiry')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- クーポン発行日 -->
                        <div class="field-group">
                            <label for="voucher_publish_date" class="field-label required">クーポン発行日（日）</label>
                            <select name="voucher_publish_date" id="voucher_publish_date" required
                                    class="field-base field-w-100 @error('voucher_publish_date') error @enderror">
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}" {{ old('voucher_publish_date', $subdomain->voucher_publish_date ?? 1) == $i ? 'selected' : '' }}>
                                        {{ $i }}日
                                    </option>
                                @endfor
                            </select>
                            @error('voucher_publish_date')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- 支払通知用・連絡先（PDFに表示） -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-md font-medium text-gray-900 mb-4">
                            <i class="fas fa-address-card text-gray-400 mr-2"></i>
                            支払通知用・連絡先
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">支払通知PDFの右肩に表示されます。</p>

                        <div class="field-group">
                            <label for="postal_code" class="field-label">郵便番号</label>
                            <input type="text" name="postal_code" id="postal_code" maxlength="8"
                                   value="{{ old('postal_code', $subdomain->postal_code ?? '') }}"
                                   class="field-base field-w-80 @error('postal_code') error @enderror" placeholder="例: 1234567">
                            @error('postal_code')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="address" class="field-label">住所</label>
                            <input type="text" name="address" id="address" maxlength="500"
                                   value="{{ old('address', $subdomain->address ?? '') }}"
                                   class="field-base field-w-100 @error('address') error @enderror">
                            @error('address')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="phone" class="field-label">電話番号</label>
                            <input type="text" name="phone" id="phone" maxlength="20"
                                   value="{{ old('phone', $subdomain->phone ?? '') }}"
                                   class="field-base field-w-80 @error('phone') error @enderror">
                            @error('phone')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="fax" class="field-label">FAX番号</label>
                            <input type="text" name="fax" id="fax" maxlength="20"
                                   value="{{ old('fax', $subdomain->fax ?? '') }}"
                                   class="field-base field-w-80 @error('fax') error @enderror">
                            @error('fax')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="transfer_date_rule" class="field-label">振込日ルール</label>
                            <select name="transfer_date_rule" id="transfer_date_rule"
                                    class="field-base field-w-100 @error('transfer_date_rule') error @enderror">
                                <option value="">未設定</option>
                                <option value="next_month_end" {{ old('transfer_date_rule', $subdomain->transfer_date_rule ?? '') === 'next_month_end' ? 'selected' : '' }}>翌月末</option>
                                <option value="month_after_next_end" {{ old('transfer_date_rule', $subdomain->transfer_date_rule ?? '') === 'month_after_next_end' ? 'selected' : '' }}>翌々月末</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">支払通知PDFの振り込み日を申込月の翌月末または翌々月末で表示します。</p>
                            @error('transfer_date_rule')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- 全銀用依頼人情報 -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-md font-medium text-gray-900 mb-4">
                            <i class="fas fa-university text-gray-400 mr-2"></i>
                            全銀用依頼人情報
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">支払集計ページで全銀フォーマットをダウンロードする際のヘッダーに使用します。すべて入力するとダウンロードが可能になります。</p>

                        <div class="field-group">
                            <label for="zengin_requester_code" class="field-label">依頼人コード（10桁）</label>
                            <input type="text" name="zengin_requester_code" id="zengin_requester_code" maxlength="10"
                                   value="{{ old('zengin_requester_code', $subdomain->zengin_requester_code ?? '') }}"
                                   class="field-base field-w-80 @error('zengin_requester_code') error @enderror" placeholder="銀行発行の委託者コード">
                            @error('zengin_requester_code')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="zengin_requester_name" class="field-label">依頼人名（40桁・半角カナ等）</label>
                            <input type="text" name="zengin_requester_name" id="zengin_requester_name" maxlength="40"
                                   value="{{ old('zengin_requester_name', $subdomain->zengin_requester_name ?? '') }}"
                                   class="field-base field-w-100 @error('zengin_requester_name') error @enderror">
                            @error('zengin_requester_name')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="zengin_bank_code" class="field-label">取引金融機関番号（4桁）</label>
                            <input type="text" name="zengin_bank_code" id="zengin_bank_code" maxlength="4"
                                   value="{{ old('zengin_bank_code', $subdomain->zengin_bank_code ?? '') }}"
                                   class="field-base field-w-80 @error('zengin_bank_code') error @enderror">
                            @error('zengin_bank_code')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="zengin_bank_name" class="field-label">取引金融機関名（15桁）</label>
                            <input type="text" name="zengin_bank_name" id="zengin_bank_name" maxlength="15"
                                   value="{{ old('zengin_bank_name', $subdomain->zengin_bank_name ?? '') }}"
                                   class="field-base field-w-100 @error('zengin_bank_name') error @enderror">
                            @error('zengin_bank_name')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="zengin_branch_code" class="field-label">取引支店番号（3桁）</label>
                            <input type="text" name="zengin_branch_code" id="zengin_branch_code" maxlength="3"
                                   value="{{ old('zengin_branch_code', $subdomain->zengin_branch_code ?? '') }}"
                                   class="field-base field-w-80 @error('zengin_branch_code') error @enderror">
                            @error('zengin_branch_code')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="zengin_branch_name" class="field-label">取引支店名（15桁）</label>
                            <input type="text" name="zengin_branch_name" id="zengin_branch_name" maxlength="15"
                                   value="{{ old('zengin_branch_name', $subdomain->zengin_branch_name ?? '') }}"
                                   class="field-base field-w-100 @error('zengin_branch_name') error @enderror">
                            @error('zengin_branch_name')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="zengin_account_type" class="field-label">預金種目</label>
                            <select name="zengin_account_type" id="zengin_account_type"
                                    class="field-base field-w-100 @error('zengin_account_type') error @enderror">
                                <option value="">未選択</option>
                                <option value="1" {{ old('zengin_account_type', $subdomain->zengin_account_type ?? '') === '1' ? 'selected' : '' }}>1: 普通</option>
                                <option value="2" {{ old('zengin_account_type', $subdomain->zengin_account_type ?? '') === '2' ? 'selected' : '' }}>2: 当座</option>
                                <option value="4" {{ old('zengin_account_type', $subdomain->zengin_account_type ?? '') === '4' ? 'selected' : '' }}>4: 貯蓄</option>
                            </select>
                            @error('zengin_account_type')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label for="zengin_account_number" class="field-label">口座番号（7桁）</label>
                            <input type="text" name="zengin_account_number" id="zengin_account_number" maxlength="7"
                                   value="{{ old('zengin_account_number', $subdomain->zengin_account_number ?? '') }}"
                                   class="field-base field-w-80 @error('zengin_account_number') error @enderror">
                            @error('zengin_account_number')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- 学年設定 -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-md font-medium text-gray-900 mb-4">
                            <i class="fas fa-graduation-cap text-gray-400 mr-2"></i>
                            学年設定
                        </h3>

                        <div class="field-group">
                            <label class="field-label">学年一覧</label>
                            <p class="text-xs text-gray-500 mb-3">コースで使用する学年を設定します。「+」ボタンで追加、「-」ボタンで削除できます。</p>
                            
                            <div id="grades-container" class="space-y-2">
                                @php
                                    $grades = old('grades', $subdomain->getGrades() ?? []);
                                    if (empty($grades)) {
                                        $grades = [''];
                                    }
                                @endphp
                                @foreach($grades as $index => $grade)
                                    <div class="grade-item flex items-center gap-2">
                                        <input type="text" 
                                               name="grades[]" 
                                               value="{{ $grade }}"
                                               placeholder="例: 小学1年"
                                               class="field-base flex-1 @error('grades.' . $index) error @enderror">
                                        <button type="button" 
                                                class="remove-grade-btn btn-base btn-back btn-s"
                                                {{ count($grades) <= 1 ? 'disabled' : '' }}>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            
                            <button type="button" id="add-grade-btn" class="mt-3 btn-base btn-update btn-s">
                                <i class="fas fa-plus mr-2"></i>学年を追加
                            </button>
                            
                            @error('grades')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                            @error('grades.*')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="btn-base btn-back btn-m">
                            <i class="fas fa-times mr-2"></i>キャンセル
                        </a>
                        <button type="submit" class="btn-base btn-update btn-m">
                            <i class="fas fa-save mr-2"></i>更新
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const expiryInput = document.getElementById('voucher_expiry');
    const expiryDisplay = document.getElementById('expiry-display');
    
    function updateExpiryDisplay() {
        const value = parseInt(expiryInput.value) || 0;
        if (value === 0) {
            expiryDisplay.textContent = '（年度末まで）';
        } else {
            expiryDisplay.textContent = '';
        }
    }
    
    // 初期表示
    updateExpiryDisplay();
    
    // 入力時の更新
    expiryInput.addEventListener('input', updateExpiryDisplay);

    // 学年追加・削除機能
    const gradesContainer = document.getElementById('grades-container');
    const addGradeBtn = document.getElementById('add-grade-btn');

    // 学年を追加
    addGradeBtn.addEventListener('click', function() {
        const gradeItem = document.createElement('div');
        gradeItem.className = 'grade-item flex items-center gap-2';
        gradeItem.innerHTML = `
            <input type="text" 
                   name="grades[]" 
                   value=""
                   placeholder="例: 小学1年"
                   class="field-base flex-1">
            <button type="button" 
                    class="remove-grade-btn btn-base btn-back btn-s">
                <i class="fas fa-minus"></i>
            </button>
        `;
        gradesContainer.appendChild(gradeItem);
        updateRemoveButtons();
    });

    // 学年を削除
    gradesContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-grade-btn')) {
            const gradeItem = e.target.closest('.grade-item');
            gradeItem.remove();
            updateRemoveButtons();
        }
    });

    // 削除ボタンの有効/無効を更新
    function updateRemoveButtons() {
        const gradeItems = gradesContainer.querySelectorAll('.grade-item');
        const removeButtons = gradesContainer.querySelectorAll('.remove-grade-btn');
        
        removeButtons.forEach(btn => {
            if (gradeItems.length <= 1) {
                btn.disabled = true;
            } else {
                btn.disabled = false;
            }
        });
    }

    // 初期状態で削除ボタンの状態を更新
    updateRemoveButtons();
});
</script>
@endpush
@endsection

