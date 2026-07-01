@extends('layouts.app')

@section('title', '利用者詳細 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('admin.beneficiaries.index') }}" class="hover:text-gray-700">利用者管理</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>利用者詳細</span></li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
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

            @if($errors->any())
                <div class="alert-base alert-error alert-m mb-6">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-message">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            <!-- 利用者詳細編集フォーム -->
            <form method="POST" action="{{ route('admin.beneficiaries.update', $beneficiary) }}">
                @csrf
                @method('PUT')
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-user text-gray-400 mr-2"></i>
                            利用者詳細
                        </h2>
                    </div>
                    <div class="p-6">
                        <!-- 残高表示 -->
                        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-blue-900 mb-1">今年度クーポン残高</h3>
                                    <p class="text-xs text-blue-700">
									有効期限：{{ $fiscalYearEnd->format('Y年n月j日') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-blue-900">
                                        ¥{{ number_format($balance) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 基本情報セクション -->
                        <div class="mb-8">
                            <h3 class="text-md font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">基本情報</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- こどもID -->
                                <div>
                                    <label for="child_id" class="block text-sm font-medium text-gray-700 mb-2">こどもID <span class="text-red-500">*</span></label>
                                    <input type="text" 
                                           id="child_id" 
                                           name="child_id" 
                                           value="{{ old('child_id', $beneficiary->child_id) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- ログイン情報を送信 -->
                                @if($beneficiary->status !== '資格喪失')
                                    <div class="flex items-end">
                                        <button type="button" id="send-login-info-btn" class="btn-base btn-update btn-m">
                                            <i class="fas fa-envelope mr-2"></i>ログイン情報を送信
                                        </button>
                                    </div>
                                @endif

                                <!-- 就学援助認定番号 -->
                                <div>
                                    <label for="certification_number" class="block text-sm font-medium text-gray-700 mb-2">就学援助認定番号 <span class="text-red-500">*</span></label>
                                    <input type="text" 
                                           id="certification_number" 
                                           name="certification_number" 
                                           value="{{ old('certification_number', $beneficiary->certification_number) }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 保護者名 -->
                                <div>
                                    <label for="guardian_name" class="block text-sm font-medium text-gray-700 mb-2">保護者名 <span class="text-red-500">*</span></label>
                                    <input type="text" 
                                           id="guardian_name" 
                                           name="guardian_name" 
                                           value="{{ old('guardian_name', $beneficiary->guardian_name) }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 保護者名カナ -->
                                <div>
                                    <label for="guardian_name_kana" class="block text-sm font-medium text-gray-700 mb-2">保護者名カナ</label>
                                    <input type="text" 
                                           id="guardian_name_kana" 
                                           name="guardian_name_kana" 
                                           value="{{ old('guardian_name_kana', $beneficiary->guardian_name_kana) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 保護者生年月日 -->
                                <div>
                                    <label for="guardian_birth_date" class="block text-sm font-medium text-gray-700 mb-2">保護者生年月日 <span class="text-red-500">*</span></label>
                                    <input type="date" 
                                           id="guardian_birth_date" 
                                           name="guardian_birth_date" 
                                           value="{{ old('guardian_birth_date', $beneficiary->guardian_birth_date ? $beneficiary->guardian_birth_date->format('Y-m-d') : '') }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 電話番号 -->
                                <div>
                                    <label for="guardian_phone" class="block text-sm font-medium text-gray-700 mb-2">電話番号 <span class="text-red-500">*</span></label>
                                    <input type="text" 
                                           id="guardian_phone" 
                                           name="guardian_phone" 
                                           value="{{ old('guardian_phone', $beneficiary->guardian_phone) }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- メールアドレス -->
                                <div>
                                    <label for="guardian_email" class="block text-sm font-medium text-gray-700 mb-2">メールアドレス <span class="text-red-500">*</span></label>
                                    <input type="email" 
                                           id="guardian_email" 
                                           name="guardian_email" 
                                           value="{{ old('guardian_email', $beneficiary->guardian_email) }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 申請日 -->
                                <div>
                                    <label for="application_date" class="block text-sm font-medium text-gray-700 mb-2">申請日 <span class="text-red-500">*</span></label>
                                    <input type="date" 
                                           id="application_date" 
                                           name="application_date" 
                                           value="{{ old('application_date', $beneficiary->application_date ? $beneficiary->application_date->format('Y-m-d') : '') }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 認定日 -->
                                <div>
                                    <label for="certification_date" class="block text-sm font-medium text-gray-700 mb-2">認定日 <span class="text-red-500">*</span></label>
                                    <input type="date" 
                                           id="certification_date" 
                                           name="certification_date" 
                                           value="{{ old('certification_date', $beneficiary->certification_date ? $beneficiary->certification_date->format('Y-m-d') : '') }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                            </div>

                            <!-- 住所 -->
                            <div class="mt-6">
                                <label for="guardian_address" class="block text-sm font-medium text-gray-700 mb-2">住所 <span class="text-red-500">*</span></label>
                                <textarea id="guardian_address" 
                                          name="guardian_address" 
                                          rows="3"
                                          required
                                          class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('guardian_address', $beneficiary->guardian_address) }}</textarea>
                            </div>
                        </div>

                        <!-- 対象児童情報セクション -->
                        <div class="mb-8">
                            <h3 class="text-md font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">対象児童情報</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- 対象児童名 -->
                                <div>
                                    <label for="child_name" class="block text-sm font-medium text-gray-700 mb-2">対象児童名 <span class="text-red-500">*</span></label>
                                    <input type="text" 
                                           id="child_name" 
                                           name="child_name" 
                                           value="{{ old('child_name', $beneficiary->child_name) }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 対象児童名カナ -->
                                <div>
                                    <label for="child_name_kana" class="block text-sm font-medium text-gray-700 mb-2">対象児童名カナ</label>
                                    <input type="text" 
                                           id="child_name_kana" 
                                           name="child_name_kana" 
                                           value="{{ old('child_name_kana', $beneficiary->child_name_kana) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 対象児童生年月日 -->
                                <div>
                                    <label for="child_birth_date" class="block text-sm font-medium text-gray-700 mb-2">対象児童生年月日 <span class="text-red-500">*</span></label>
                                    <input type="date" 
                                           id="child_birth_date" 
                                           name="child_birth_date" 
                                           value="{{ old('child_birth_date', $beneficiary->child_birth_date ? $beneficiary->child_birth_date->format('Y-m-d') : '') }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 小学校名 -->
                                <div>
                                    <label for="elementary_school_name" class="block text-sm font-medium text-gray-700 mb-2">学校名 <span class="text-red-500">*</span></label>
                                    <input type="text" 
                                           id="elementary_school_name" 
                                           name="elementary_school_name" 
                                           value="{{ old('elementary_school_name', $beneficiary->elementary_school_name) }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 学年 -->
                                <div>
                                    <label for="grade" class="block text-sm font-medium text-gray-700 mb-2">学年 <span class="text-red-500">*</span></label>
                                    <input type="text" 
                                           id="grade" 
                                           name="grade" 
                                           value="{{ old('grade', $beneficiary->grade) }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- 調査同意 -->
                                <div>
                                    <label for="survey_consent" class="block text-sm font-medium text-gray-700 mb-2">調査同意</label>
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               id="survey_consent" 
                                               name="survey_consent" 
                                               value="1"
                                               {{ old('survey_consent', $beneficiary->survey_consent) ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="survey_consent" class="ml-2 text-sm text-gray-700">同意する</label>
                                    </div>
                                </div>
                            </div>

                            <!-- 対象児童の住所 -->
                            <div class="mt-6">
                                <label for="child_address" class="block text-sm font-medium text-gray-700 mb-2">対象児童の住所 <span class="text-red-500">*</span></label>
                                <textarea id="child_address" 
                                          name="child_address" 
                                          rows="3"
                                          required
                                          class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('child_address', $beneficiary->child_address) }}</textarea>
                            </div>

                            <!-- 申請者と同一の住所 -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">申請者と同一の住所</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $beneficiary->child_address_same_as_guardian ? 'はい' : 'いいえ' }}
                                </div>
                            </div>
                        </div>

                        <!-- 教室情報セクション -->
                        <div class="mb-8">
                            <h3 class="text-md font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">教室情報</h3>
                            @for($i = 1; $i <= 3; $i++)
                                <div class="mb-6 p-4 bg-gray-50 rounded-md">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">教室{{ $i }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="classroom_name_{{$i}}" class="block text-xs font-medium text-gray-600 mb-1">教室名</label>
                                            <input type="text" 
                                                   id="classroom_name_{{$i}}" 
                                                   name="classroom_name_{{$i}}" 
                                                   value="{{ old("classroom_name_{$i}", $beneficiary->{"classroom_name_{$i}"}) }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="classroom_location_{{$i}}" class="block text-xs font-medium text-gray-600 mb-1">所在地</label>
                                            <input type="text" 
                                                   id="classroom_location_{{$i}}" 
                                                   name="classroom_location_{{$i}}" 
                                                   value="{{ old("classroom_location_{$i}", $beneficiary->{"classroom_location_{$i}"}) }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="classroom_phone_{{$i}}" class="block text-xs font-medium text-gray-600 mb-1">電話番号</label>
                                            <input type="text" 
                                                   id="classroom_phone_{{$i}}" 
                                                   name="classroom_phone_{{$i}}" 
                                                   value="{{ old("classroom_phone_{$i}", $beneficiary->{"classroom_phone_{$i}"}) }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="classroom_contact_person_{{$i}}" class="block text-xs font-medium text-gray-600 mb-1">担当者</label>
                                            <input type="text" 
                                                   id="classroom_contact_person_{{$i}}" 
                                                   name="classroom_contact_person_{{$i}}" 
                                                   value="{{ old("classroom_contact_person_{$i}", $beneficiary->{"classroom_contact_person_{$i}"}) }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <!-- その他情報セクション -->
                        <div class="mb-8">
                            <h3 class="text-md font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">その他情報</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- ステータス -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">ステータス <span class="text-red-500">*</span></label>
                                    <select id="status" 
                                            name="status" 
                                            required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        @php
                                            $availableStatuses = ['決定通知書未送信', '決定通知書送信待ち', '決定通知書送信失敗', '決定通知書送信済', 'ログイン認証済み', '資格喪失予定', '資格喪失'];
                                            $currentStatus = old('status', $beneficiary->status ?? '決定通知書未送信');
                                        @endphp
                                        @foreach($availableStatuses as $status)
                                            <option value="{{ $status }}" {{ $currentStatus === $status ? 'selected' : '' }}>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- 資格喪失日 -->
                                <div>
                                    <label for="disqualification_date" class="block text-sm font-medium text-gray-700 mb-2">資格喪失日</label>
                                    <input type="date" 
                                           id="disqualification_date" 
                                           name="disqualification_date" 
                                           value="{{ old('disqualification_date', $beneficiary->disqualification_date ? $beneficiary->disqualification_date->format('Y-m-d') : '') }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <!-- ラベル -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">利用者ラベル</label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                                        @php
                                            $availableLabels = ['DV避難等'];
                                            $currentLabels = old('labels', $beneficiary->labels ? explode(',', $beneficiary->labels) : []);
                                            if (!is_array($currentLabels)) {
                                                $currentLabels = [];
                                            }
                                            $currentLabels = array_map('trim', $currentLabels);
                                        @endphp
                                        @foreach($availableLabels as $label)
                                            <div class="flex items-center">
                                                <input type="checkbox" 
                                                       id="label_{{ $loop->index }}" 
                                                       name="labels[]" 
                                                       value="{{ $label }}"
                                                       {{ in_array($label, $currentLabels) ? 'checked' : '' }}
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <label for="label_{{ $loop->index }}" class="ml-2 text-sm text-gray-700">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 備考 -->
                        <div class="mb-6">
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">備考</label>
                            <textarea id="remarks"
                                      name="remarks"
                                      rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('remarks', $beneficiary->remarks) }}</textarea>
                            @error('remarks')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ボタン -->
                        <div class="flex justify-end space-x-4 mt-6">
                            <a href="{{ route('admin.beneficiaries.index') }}" 
                               class="btn-base btn-back btn-m">
                                <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
                            </a>
                            <button type="submit" class="btn-base btn-update btn-m">
                                <i class="fas fa-save mr-2"></i>更新
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- クーポン一覧 -->
            <div class="bg-white shadow rounded-lg mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-ticket-alt text-gray-400 mr-2"></i>
                            クーポン一覧
                        </h2>
                        @if($beneficiary->status !== '資格喪失')
                            <button type="button" 
                                    onclick="openIssueVoucherModal()" 
                                    class="btn-base btn-update btn-m">
                                <i class="fas fa-plus mr-2"></i>クーポン付与
                            </button>
                        @endif
                    </div>
                </div>
                <div class="p-6">
                    @if($beneficiary->vouchers->count() > 0)
                        <div class="overflow-x-auto">
                            <table id="vouchers-table" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">クーポン番号</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">発行日</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">有効期限</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">利用金額</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状態</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($beneficiary->vouchers as $voucher)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $voucher->voucher_number }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $voucher->issue_date->format('Y-m-d') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $voucher->expiry_date->format('Y-m-d') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ¥{{ number_format($voucher->amount) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($voucher->status === 'unused')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                        有効
                                                    </span>
                                                @elseif($voucher->status === 'expired')
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        無効
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($voucher->status === 'unused')
                                                    <button type="button" 
                                                            onclick="openExpireVoucherModal({{ $voucher->id }}, '{{ $voucher->voucher_number }}', '¥{{ number_format($voucher->amount) }}')"
                                                            class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                        <i class="fas fa-ban mr-1"></i>無効化
                                                    </button>
                                                @else
                                                    <span class="text-gray-400 text-xs">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-500">
                                <i class="fas fa-ticket-alt text-4xl text-gray-300 mb-4"></i>
                                <p>クーポンがありません。</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 申込履歴一覧 -->
            <div class="bg-white shadow rounded-lg mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-list-alt text-gray-400 mr-2"></i>
                        申込履歴
                    </h2>
                </div>
                <div class="p-6">
                    @if(count($applications) > 0)
                        <div class="overflow-x-auto">
                            <table id="applications-table" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">申込日時</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">教室名</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">コース名</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($applications as $application)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $application->used_at->format('Y-m-d H:i') }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $application->classroomInfo->classroom_name ?? '不明' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                @if($application->courseInfo)
                                                    {{ $application->courseInfo->course_name }}
                                                @else
                                                    金額指定利用
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ¥{{ number_format($application->amount) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($application->is_cancelled)
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        キャンセル済み
                                                    </span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                        申込済み
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-500">
                                <i class="fas fa-list-alt text-4xl text-gray-300 mb-4"></i>
                                <p>申込履歴がありません。</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- クーポン付与確認モーダル -->
<div id="issue-voucher-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500/50 transition-opacity" style="z-index: 0;" aria-hidden="true" onclick="closeIssueVoucherModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" style="z-index: 10;">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">クーポン付与確認</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeIssueVoucherModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-700 mb-4">
                        この利用者にクーポンを付与しますか？<br>
                        <span class="font-semibold">{{ $beneficiary->guardian_name }}様</span>（{{ $beneficiary->certification_number }}）
                    </p>
                    <p class="text-xs text-gray-500">
                        付与されるクーポンは、月次バウチャー発行バッチと同じ条件で作成されます。
                    </p>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form action="{{ route('admin.beneficiaries.issue-voucher', $beneficiary) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        OK
                    </button>
                    <button type="button" onclick="closeIssueVoucherModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        キャンセル
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- クーポン無効化確認モーダル -->
<div id="expire-voucher-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500/50 transition-opacity" style="z-index: 0;" aria-hidden="true" onclick="closeExpireVoucherModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" style="z-index: 10;">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">クーポン無効化確認</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeExpireVoucherModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-700 mb-4">
                        このクーポンを無効化しますか？<br>
                        <span class="text-red-600 font-semibold">この操作は取り消せません。</span>
                    </p>
                    <div class="bg-gray-50 p-3 rounded-md">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">クーポン番号:</span> <span id="expire-voucher-number"></span><br>
                            <span class="font-medium">金額:</span> <span id="expire-voucher-amount"></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="expire-voucher-form" action="" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        OK
                    </button>
                    <button type="button" onclick="closeExpireVoucherModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        キャンセル
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.5em 1em;
        margin-left: 2px;
        border: 1px solid transparent;
        border-radius: 0.375rem;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #3b82f6;
        color: white !important;
        border-color: #3b82f6;
    }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
    }
</style>
@endpush

@push('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
function openIssueVoucherModal() {
    const modal = document.getElementById('issue-voucher-modal');
    modal.classList.remove('hidden');
}

function closeIssueVoucherModal() {
    const modal = document.getElementById('issue-voucher-modal');
    modal.classList.add('hidden');
}

function openExpireVoucherModal(voucherId, voucherNumber, amount) {
    const modal = document.getElementById('expire-voucher-modal');
    const form = document.getElementById('expire-voucher-form');
    const numberSpan = document.getElementById('expire-voucher-number');
    const amountSpan = document.getElementById('expire-voucher-amount');
    
    // フォームのactionを設定
    form.action = '{{ url("admin/beneficiaries") }}/{{ $beneficiary->id }}/vouchers/' + voucherId + '/expire';
    
    // モーダル内の情報を更新
    numberSpan.textContent = voucherNumber;
    amountSpan.textContent = amount;
    
    modal.classList.remove('hidden');
}

function closeExpireVoucherModal() {
    const modal = document.getElementById('expire-voucher-modal');
    modal.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const sendLoginInfoBtn = document.getElementById('send-login-info-btn');
    if (sendLoginInfoBtn) {
        sendLoginInfoBtn.addEventListener('click', function() {
            if (!confirm('利用者にログイン情報を送信しますか？')) {
                return;
            }
            sendLoginInfoBtn.disabled = true;
            const originalHtml = sendLoginInfoBtn.innerHTML;
            sendLoginInfoBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>送信中...';
            fetch('{{ route('admin.beneficiaries.send-login-info', $beneficiary) }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'ログイン情報を送信しました。');
                } else {
                    alert(data.message || 'ログイン情報の送信に失敗しました。');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('ログイン情報の送信中にエラーが発生しました。');
            })
            .finally(() => {
                sendLoginInfoBtn.disabled = false;
                sendLoginInfoBtn.innerHTML = originalHtml;
            });
        });
    }

    // jQueryが読み込まれるまで待機
    function initDataTables() {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.dataTable !== 'undefined') {
            // クーポン一覧テーブル
            if (document.getElementById('vouchers-table')) {
                $('#vouchers-table').DataTable({
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ja.json'
                    },
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "すべて"]],
                    order: [[1, 'desc']], // 発行日で降順ソート
                    columnDefs: [
                        { orderable: false, targets: [4] } // 状態カラムはソート無効
                    ]
                });
            }

            // 申込履歴テーブル
            if (document.getElementById('applications-table')) {
                $('#applications-table').DataTable({
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ja.json'
                    },
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "すべて"]],
                    order: [[0, 'desc']] // 申込日時で降順ソート
                });
            }
        } else {
            // jQueryがまだ読み込まれていない場合、少し待ってから再試行
            setTimeout(initDataTables, 100);
        }
    }

    // jQueryの読み込みを待つ
    if (typeof jQuery !== 'undefined') {
        initDataTables();
    } else {
        // jQueryがまだ読み込まれていない場合、読み込み完了を待つ
        window.addEventListener('load', function() {
            setTimeout(initDataTables, 500);
        });
    }
});
</script>
@endpush
@endsection

