{{-- 事業者情報フォーム（新規登録・編集共通） --}}
<form action="{{ $action }}" method="POST" class="p-6" enctype="multipart/form-data">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <!-- 申請者種別 -->
    <div class="form-row cols-2">
        <label class="field-label required label-l">申請者種別</label>
        <div class="flex space-x-6 mt-2 w-full">
			<div class="flex items-center">
                <input type="radio" 
                       id="applicant_type_corporation" 
                       name="applicant_type" 
                       value="corporation" 
                       {{ old('applicant_type', $business->applicant_type ?? '') == 'corporation' ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 focus:ring-2">
                <label for="applicant_type_corporation" class="ml-2 text-sm font-medium text-gray-900">法人</label>
            </div>
            <div class="flex items-center">
                <input type="radio" 
                       id="applicant_type_voluntary_group" 
                       name="applicant_type" 
                       value="voluntary_group" 
                       {{ old('applicant_type', $business->applicant_type ?? '') == 'voluntary_group' ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 focus:ring-2">
                <label for="applicant_type_voluntary_group" class="ml-2 text-sm font-medium text-gray-900">任意団体</label>
            </div>
            <div class="flex items-center ml10">
                <input type="radio" 
                       id="applicant_type_individual" 
                       name="applicant_type" 
                       value="individual" 
                       {{ old('applicant_type', $business->applicant_type ?? '') == 'individual' ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 focus:ring-2">
                <label for="applicant_type_individual" class="ml-2 text-sm font-medium text-gray-900">個人事業主</label>
            </div>
            <div class="flex items-center ml10">
                <input type="radio"
                       id="applicant_type_government_agency"
                       name="applicant_type"
                       value="government_agency"
                       {{ old('applicant_type', $business->applicant_type ?? '') == 'government_agency' ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 focus:ring-2">
                <label for="applicant_type_government_agency" class="ml-2 text-sm font-medium text-gray-900">行政機関</label>
            </div>
        </div>
        @error('applicant_type')
            <span class="field-error">{{ $message }}</span>
        @enderror
	</div>

    <!-- 反社チェック（暴力団排除誓約） -->
    <div class="form-row cols-2">
        <label class="field-label label-l">反社チェック</label>
        <div class="flex items-center mt-2 w-full">
            @if(old('antisocial_forces_pledged', optional($business)->antisocial_forces_pledged ?? false))
                <span class="text-green-700"><i class="fas fa-check-circle mr-1"></i>誓約済</span>
            @else
                <span class="text-gray-500"><i class="fas fa-minus-circle mr-1"></i>未誓約</span>
            @endif
        </div>
    </div>

    <!-- 登録教室募集要項及びプライバシーポリシー同意 -->
    <div class="form-row cols-2">
        <label class="field-label label-l">登録教室募集要項及びプライバシーポリシー同意</label>
        <div class="flex items-center mt-2 w-full">
            @if(old('privacy_policy_agreed', optional($business)->privacy_policy_agreed ?? false))
                <span class="text-green-700"><i class="fas fa-check-circle mr-1"></i>同意済</span>
            @else
                <span class="text-gray-500"><i class="fas fa-minus-circle mr-1"></i>未同意</span>
            @endif
        </div>
    </div>

	<!-- 申請日（作成日） -->
    <div class="form-row cols-2">
        <label class="field-label label-l">申請日</label>
        <div class="flex items-center mt-2 w-full">
            @if(old('created_at', optional($business)->created_at ?? false))
                <span>{{ $business->created_at->format('Y年m月d日') }}</span>
            @endif
        </div>
    </div>	

    <!-- 事業者名 -->
	<div>
	<div class="form-row cols-2">
		<label for="business_name" class="field-label required label-l">事業者名</label>
		<input type="text" 
				class="field-base field-w-100 @error('business_name') error @enderror" 
				id="business_name" name="business_name" 
				value="{{ old('business_name', $business->business_name ?? '') }}" 
				maxlength="100" required>
		@error('business_name')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
	<div class="form-row cols-2">
		<label for="business_name_kana"  class="field-label required label-l">事業者名（カナ）</label>
		<input type="text" 
				class="field-base field-w-100 @error('business_name_kana') error @enderror" 
				id="business_name_kana" name="business_name_kana" 
				value="{{ old('business_name_kana', $business->business_name_kana ?? '') }}" 
				maxlength="100" required>
		@error('business_name_kana')
			<span class="field-error">{{ $message }}</span>
		@enderror
	</div>
    <div>

    <!-- 代表者名 -->
    <div class="form-row cols-2">
        <label for="representative_title" class="field-label required label-l">代表者役職名</label>
		<input type="text"
				class="field-base field-w-100 @error('representative_title') error @enderror"
				id="representative_title" name="representative_title"
				value="{{ old('representative_title', $business->representative_title ?? '') }}"
				maxlength="50" required>
		@error('representative_title')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
		<label class="field-label required label-l">代表者役職名（カナ）</label>
        <input type="text"
				class="field-base field-w-100 @error('representative_title_kana') error @enderror"
				id="representative_title_kana" name="representative_title_kana"
				value="{{ old('representative_title_kana', $business->representative_title_kana ?? '') }}"
				maxlength="50" required>
		@error('representative_title_kana')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
        <label class="field-label required label-l">代表者名</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input type="text"
                    class="field-base field-w-100 @error('representative_family_name') error @enderror"
                    id="representative_family_name" name="representative_family_name"
                    value="{{ old('representative_family_name', $business->representative_family_name ?? '') }}"
                    maxlength="50" required
                    placeholder="姓">
            <input type="text"
                    class="field-base field-w-100 @error('representative_given_name') error @enderror"
                    id="representative_given_name" name="representative_given_name"
                    value="{{ old('representative_given_name', $business->representative_given_name ?? '') }}"
                    maxlength="50" required
                    placeholder="名">
        </div>
		@error('representative_family_name')
			<span class="field-error">{{ $message }}</span>
		@enderror
		@error('representative_given_name')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
		<label class="field-label label-l required">代表者名（カナ）</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input type="text"
                    class="field-base field-w-100 @error('representative_family_name_kana') error @enderror"
                    id="representative_family_name_kana" name="representative_family_name_kana"
                    value="{{ old('representative_family_name_kana', $business->representative_family_name_kana ?? '') }}"
                    maxlength="50"
                    placeholder="セイ" required>
            <input type="text"
                    class="field-base field-w-100 @error('representative_given_name_kana') error @enderror"
                    id="representative_given_name_kana" name="representative_given_name_kana"
                    value="{{ old('representative_given_name_kana', $business->representative_given_name_kana ?? '') }}"
                    maxlength="50"
                    placeholder="メイ" required>
        </div>
		@error('representative_family_name_kana')
			<span class="field-error">{{ $message }}</span>
		@enderror
		@error('representative_given_name_kana')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    </div>

    <div>
        <div class="form-row cols-2">
            <label for="postal_code" class="field-label required label-l">郵便番号</label>
            <div class="flex gap-2">
                <input type="text" 
                        class="field-base field-w-80 @error('postal_code') error @enderror" 
                        id="postal_code" name="postal_code" 
                        value="{{ old('postal_code', $business->postal_code ?? '') }}" 
                        placeholder="123-4567" maxlength="8" required>
                <button type="button" class="btn-base w-100 btn-search btn-m search-postal-code">
                    <i class="fas fa-search mr-2"></i>検索
                </button>
            </div>
			@error('postal_code')
				<span class="field-error">{{ $message }}</span>
			@enderror
    </div>
    <div class="form-row cols-2">
        <label for="prefecture" class="field-label required label-l">都道府県</label>
		<input type="text" 
				class="field-base field-w-100 @error('prefecture') error @enderror" 
				id="prefecture" name="prefecture" 
				value="{{ old('prefecture', $business->prefecture ?? '') }}" 
				maxlength="10" required>
		@error('prefecture')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
        <label for="city" class="field-label required label-l">市区町村</label>
		<input type="text" 
				class="field-base field-w-100 @error('city') error @enderror" 
				id="city" name="city" 
				value="{{ old('city', $business->city ?? '') }}" 
				maxlength="50" required>
		@error('city')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>

    <div class="form-row cols-2">
        <label for="address1" class="field-label required label-l">それ以降の住所</label>
		<input type="text" 
				class="field-base field-w-100 @error('address1') error @enderror" 
				id="address1" name="address1" 
				value="{{ old('address1', $business->address1 ?? '') }}" 
				maxlength="100" required>
		@error('address1')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
        <label for="building_name" class="field-label label-l">建物名・部屋番号</label>
		<input type="text" 
				class="field-base field-w-100 @error('building_name') error @enderror" 
				id="building_name" name="building_name" 
				value="{{ old('building_name', $business->building_name ?? '') }}" 
				maxlength="100">
		@error('building_name')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
	<div class="form-row cols-2">
		<label for="phone" class="field-label required label-l">電話番号</label>
		<input type="tel" 
				class="field-base field-w-100 @error('phone') error @enderror" 
				id="phone" name="phone" 
				value="{{ old('phone', $business->phone ?? '') }}" 
				maxlength="20" required>
		@error('phone')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
	<label for="fax" class="field-label label-l">FAX番号</label>
	<input type="tel" 
			class="field-base field-w-100 @error('fax') error @enderror" 
			id="fax" name="fax" 
			value="{{ old('fax', $business->fax ?? '') }}" 
			maxlength="20">
	@error('fax')
		<span class="field-error">{{ $message }}</span>
	@enderror
    </div>
    <div class="form-row cols-2">
        <label for="email" class="field-label required label-l">メールアドレス</label>
		<input type="email" 
				class="field-base field-w-100 @error('email') error @enderror" 
				id="email" name="email" 
				value="{{ old('email', $business->email ?? '') }}" 
				maxlength="255" required>
		@error('email')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
        <label for="website_url" class="field-label label-l">ウェブサイトURL</label>
		<input type="url" 
				class="field-base field-w-100 @error('website_url') error @enderror" 
				id="website_url" name="website_url" 
				value="{{ old('website_url', $business->website_url ?? '') }}" 
				maxlength="255" 
				placeholder="https://example.com">
		@error('website_url')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>	


    <!-- 連絡先情報 -->
	<h3 class="section-title">連絡先情報</h3>
	<div class="section-divider">
    <div class="form-row cols-2">
        <label for="contact_person" class="field-label required label-l">連絡先：担当者名</label>
		<input type="text" 
				class="field-base field-w-100 @error('contact_person') error @enderror" 
				id="contact_person" name="contact_person" 
				value="{{ old('contact_person', $business->contact_person ?? '') }}" 
				maxlength="50" required>
		@error('contact_person')
			<span class="field-error">{{ $message }}</span>
		@enderror
	</div>
	<div class="form-row cols-2">
        <label for="contact_phone" class="field-label required label-l">連絡先：電話番号</label>
		<input type="tel" 
				class="field-base field-w-100 @error('contact_phone') error @enderror" 
				id="contact_phone" name="contact_phone" 
				value="{{ old('contact_phone', $business->contact_phone ?? '') }}" 
				maxlength="20" required>
		@error('contact_phone')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
        <label for="document_person" class="field-label required label-l">文書等送付先：宛名</label>
		<input type="text" 
				class="field-base field-w-100 @error('document_person') error @enderror" 
				id="document_person" name="document_person" 
				value="{{ old('document_person', $business->document_person ?? '') }}" 
				maxlength="50" required>
		@error('document_person')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
        <label for="document_address" class="field-label required label-l">文書等送付先：住所</label>
		<input type="text" 
				class="field-base field-w-100 @error('document_address') error @enderror" 
				id="document_address" name="document_address" 
				value="{{ old('document_address', $business->document_address ?? '') }}" 
				maxlength="255" required>
		@error('document_address')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>
    <div class="form-row cols-2">
        <label for="business_hours" class="field-label required label-l">連絡先：営業時間</label>
            <textarea class="field-base field-w-100 @error('business_hours') error @enderror" 
                      id="business_hours" name="business_hours" rows="3" 
                      placeholder="例：平日 9:00-18:00、土日 10:00-17:00" required>{{ old('business_hours', $business->business_hours ?? '') }}</textarea>
            @error('business_hours')
                <span class="field-error">{{ $message }}</span>
            @enderror
    </div>
    <!-- 定休日 -->
    <div class="form-row cols-2">
        <label for="holiday" class="field-label required label-l">連絡先：定休日</label>
		<input type="text" class="field-base field-w-100 @error('holiday') error @enderror" 
			id="holiday" name="holiday" rows="3" 
			placeholder="例：日曜日・祝祭日" value="{{ old('holiday', $business->holiday ?? '') }}" required>
		@error('holiday')
			<span class="field-error">{{ $message }}</span>
		@enderror
    </div>

    <!-- 銀行情報 -->
    <div class="section-divider">
        <h3 class="section-title">振込先情報</h3>
		<x-form.bank-branch-select 
			:bank-name="'bank_code'"
			:branch-name="'branch_code'"
			:bank-value="old('bank_code', $business->bank_code ?? '')"
			:branch-value="old('branch_code', $business->branch_code ?? '')"
			:bank-required="true"
			:branch-required="true"
			:bank-label="'銀行名'"
			:branch-label="'支店名'"
			:mode="'input'"
			:data="[]"
			:form-token="$formToken ?? session('form_access_token')"
		/>
    <div class="form-row cols-2">
        <label for="account_type" class="field-label required label-l">口座種別</label>
		<div class="flex flex-wrap gap-4 mt-2">
			<input type="radio" name="account_type" value="普通" class="field-radio" {{ old('account_type', $business->account_type ?? '') == '普通' ? 'checked' : '' }} required><label class="field-label">普通</label>
			<input type="radio" name="account_type" value="当座" class="field-radio" {{ old('account_type', $business->account_type ?? '') == '当座' ? 'checked' : '' }}><label class="field-label">当座</label>
			@error('account_type')
				<span class="field-error">{{ $message }}</span>
			@enderror
		</div>
	</div>

    <div class="form-row cols-2">
        <label for="account_number" class="field-label required label-l">口座番号</label>
                <input type="text" 
                       class="field-base field-w-100 @error('account_number') error @enderror" 
                       id="account_number" name="account_number" 
                       value="{{ old('account_number', $business->account_number ?? '') }}" 
                       maxlength="20" required>
                @error('account_number')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
	<div class="form-row cols-2">
        <label for="account_holder_name" class="field-label required label-l">口座名義（カナ）</label>
                <input type="text" 
                       class="field-base field-w-100 @error('account_holder_name') error @enderror" 
                       id="account_holder_name" name="account_holder_name" 
                       value="{{ old('account_holder_name', $business->account_holder_name ?? '') }}" 
                       maxlength="50" required>
                @error('account_holder_name')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <!-- 申請書類（申請者種別に応じた書類一覧） -->
    @if(isset($business) && $business->exists)
	<div class="field-group">
        <div class="section-divider">
            <h3 class="section-title">申請書類</h3>
            @php
                $applicantType = $business->applicant_type ?? '';
                $documentKeys = $business::getDocumentTypes($applicantType);
            @endphp
            <div class="space-y-4">
                @foreach($documentKeys as $documentKey)
                    @php
                        $docInfo = $business->getDocumentInfo($documentKey);
                        $hasDocument = !empty($docInfo['s3_key']);
                    @endphp
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <i class="{{ $docInfo['icon'] }} text-{{ $docInfo['color'] }}-600"></i>
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $docInfo['title'] }}</h4>
                                <!-- @foreach($docInfo['notice'] ?? [] as $noticeLine)
                                    <p class="text-sm text-amber-700 mt-1">{{ $noticeLine }}</p>
                                @endforeach -->
                                @if($hasDocument && $docInfo['file_size'])
                                    <p class="text-xs text-gray-500 mt-1">
                                        ファイル名: {{ $docInfo['original_filename'] }}
                                        ({{ number_format($docInfo['file_size'] / 1024, 1) }}KB)
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div>
                            @if($hasDocument)
                                <div class="flex items-center space-x-2">
                                    <span class="label-base label-active label-xs">アップロード済み</span>
                                    <a href="{{ route('admin.business.document.download', ['business' => $business, 'type' => $documentKey]) }}"
                                       class="btn-base btn-info btn-xs"
                                       title="{{ $docInfo['original_filename'] }}">
                                        <i class="fas fa-download mr-1"></i>ダウンロード
                                    </a>
                                </div>
                            @else
                                <span class="label-base label-inactive label-xs">未アップロード</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
	</div>

	<!-- 事業者状態 -->
	<div class="field-group">
		<h3 class="section-title">
			事業者状態
			@if(isset($business) && $business->exists && $business->is_active == 1)
				<span class="label-base label-active label-s">有効</span>
			@else
				<span class="label-base label-inactive label-s">無効</span>
			@endif		
		</h3>
		<div class="form-row cols-2">
			<label for="status" class="field-label label-l">ステータス</label>
			@php
				$currentStatus = old('status', $business->status ?? '未着手');
			@endphp
			<select id="status" 
					name="status"
					class="field-base field-w-100 @error('status') error @enderror">
				@foreach(\App\Models\BusinessInfo::getAvailableStatuses() as $statusOption)
					<option value="{{ $statusOption }}" {{ $currentStatus == $statusOption ? 'selected' : '' }}>
						{{ $statusOption }}
					</option>
				@endforeach
			</select>
			@error('status')
				<span class="field-error">{{ $message }}</span>
			@enderror
		</div>
	</div>

    @if(isset($business) && $business->exists)
        <!-- 管理者用備考・添付（編集時のみ表示） -->
        <div class="section-divider">
            <h3 class="section-title">管理者用備考</h3>
            <div class="form-row cols-2">
                <span class="field-label label-l">公金振替</span>
                <div class="field-checkbox">
                    <input type="checkbox"
                           name="is_public_funds_transfer_target"
                           id="is_public_funds_transfer_target"
                           value="1"
                           {{ old('is_public_funds_transfer_target', $business->is_public_funds_transfer_target ?? false) ? 'checked' : '' }}
                           class="field-checkbox-input">
                    <label for="is_public_funds_transfer_target" class="field-checkbox-label">公金振替対象</label>
                </div>
            </div>
            <div class="form-row cols-2">
                <label for="admin_remarks" class="field-label label-l">備考</label>
                <textarea name="admin_remarks"
                          id="admin_remarks"
                          class="field-base field-w-100 @error('admin_remarks') error @enderror"
                          rows="4">{{ old('admin_remarks', $business->admin_remarks ?? '') }}</textarea>
                @error('admin_remarks')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
            @php
                $adminAttachments = $business->admin_attachments ?? [];
            @endphp
            @if(count($adminAttachments) > 0)
                <div class="space-y-2 mb-4">
                    @foreach($adminAttachments as $att)
                        @php
                            $s3Key = $att['s3_key'] ?? '';
                            $name = $att['original_filename'] ?? 'ファイル';
                            $size = $att['size'] ?? 0;
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-paperclip text-gray-500"></i>
                                <span class="text-sm">{{ $name }}</span>
                                <span class="text-xs text-gray-500">({{ number_format((int) $size / 1024, 1) }} KB)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.business.admin-attachment.download', $business) }}?key={{ urlencode($s3Key) }}"
                                   class="btn-base btn-info btn-xs"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="fas fa-download mr-1"></i>ダウンロード
                                </a>
                                <label class="inline-flex items-center text-sm text-red-600">
                                    <input type="checkbox" name="admin_attachment_remove[]" value="{{ $s3Key }}" class="rounded border-gray-300">
                                    <span class="ml-1">削除</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            @if(count($adminAttachments) < 5)
                <div class="form-row cols-2">
                    <label for="admin_attachments" class="field-label label-l">新規添付（1ファイル8MBまで）</label>
                    <input type="file"
                           id="admin_attachments"
                           name="admin_attachments[]"
                           class="field-base field-w-100"
                           multiple
                           accept="*">
                    <span class="field-help">1ファイル8MBまで。合計5件まで登録できます。</span>
                    @error('admin_attachments')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    @error('admin_attachments.*')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            @endif
        </div>
    @endif

	<div class="field-group">
    </div>
    @if(isset($business) && $business->exists)
        <!-- ログイン情報（編集時のみ表示） -->
        <div class="section-divider">
            <h3 class="section-title">ログイン情報</h3>
            <div class="info-panel">
                <div class="form-row cols-1">
                    <div class="field-group">
                        <label class="field-label">ログインID</label>
						{{ old('email', $business->email ?? '') }}
						@if(!empty($business->user_id))
							<label class="label-base label-active label-m" id="email-sent-status">メール送信済</label>
						@else
							<label class="label-base label-pending label-m" id="email-sent-status">メール未送信</label>
						@endif
                    </div>
				</div>
				<div class="form-row cols-1">
					<div class="field-group flex justify-end">
						@php
							$allowedStatuses = ['審査②通過', '審査通過メール送信済', '利用中'];
							$currentStatus = $business->status ?? '未着手';
							$canSendLoginInfo = in_array($currentStatus, $allowedStatuses, true);
						@endphp
						@if($canSendLoginInfo)
							<button type="button" class="btn-base btn-create btn-send-password btn-m" data-business-id="{{ $business->id }}">
								<i class="fas fa-envelope mr-2"></i>事業者にログイン情報を送信
							</button>
						@else
							<button type="button" class="btn-base btn-disable btn-m" disabled title="ステータスが「審査②通過」「審査通過メール送信済」「利用中」の場合のみ送信可能です">
								<i class="fas fa-envelope mr-2"></i>事業者にログイン情報を送信
							</button>
						@endif
					</div>
					<span class="field-help flex justify-end">※パスワードは自動生成され、事業者に送信されます。</span>
					<span class="field-help flex justify-end">※ステータスが「審査②通過」「審査通過メール送信済」「利用中」の場合のみ送信可能です。</span>
				</div>
            </div>
        </div>
    @else
        <!-- ログイン情報（新規登録時のみ表示） -->
        <div class="section-divider">
            <h3 class="section-title">ログイン情報</h3>
            <div class="form-row cols-1">
                <div class="field-group">
                    <label for="login_id" class="field-label required">ログインID</label>
                    <input type="text" 
                           class="field-base field-w-100 @error('login_id') error @enderror" 
                           id="login_id" name="login_id" 
                           value="{{ old('login_id') }}" 
                           maxlength="50" required>
                    <span class="field-help">半角英数字とハイフン、アンダースコアが使用できます</span>
                    @error('login_id')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="form-row cols-1">
                <div class="field-group">
                    <label for="password" class="field-label required">パスワード</label>
                    <input type="password" 
                           class="field-base field-w-100 @error('password') error @enderror" 
                           id="password" name="password" 
                           minlength="8" required>
                    <span class="field-help">8文字以上で入力してください</span>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="form-row cols-1">
                <div class="field-group">
                    <label for="password_confirmation" class="field-label required">パスワード確認</label>
                    <input type="password" 
                           class="field-base field-w-100 @error('password_confirmation') error @enderror" 
                           id="password_confirmation" name="password_confirmation" 
                           minlength="8" required>
                    <span class="field-help">確認のため同じパスワードを入力してください</span>
                    @error('password_confirmation')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    @endif


    <!-- ボタン -->
    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
        <a href="{{ route('admin.business.index') }}" 
           class="btn-base btn-back btn-m">
            <i class="fas fa-arrow-left mr-2"></i>戻る
        </a>
        <button type="submit" class="btn-base {{ isset($business) && $business->exists ? 'btn-update' : 'btn-create' }} btn-m">
            <i class="fas fa-{{ isset($business) && $business->exists ? 'save' : 'plus' }} mr-2"></i>
            {{ isset($business) && $business->exists ? '更新' : '登録' }}
        </button>
    </div>
</form>

<!-- 郵便番号検索用スクリプト -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.querySelector('.search-postal-code');
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const postalCode = document.getElementById('postal_code');
            if (!postalCode || postalCode.value.trim() === '') {
                alert('郵便番号を入力してください。');
                return;
            }

            // 検索ボタンを無効化
            searchBtn.disabled = true;
            const originalHtml = searchBtn.innerHTML;
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>検索中...';

            // フォームトークンを取得
            const formToken = '{{ $formToken ?? session("form_access_token") ?? "" }}';

            // APIを呼び出し
            fetch(`/api/postal-code/search?postcode=${encodeURIComponent(postalCode.value)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Form-Token': formToken,
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.addresses && data.data.addresses.length > 0) {
                    // 最初の住所情報を取得
                    const address = data.data.addresses[0];
                    
                    // 都道府県、市区町村、町名・番地を設定
                    const prefectureInput = document.getElementById('prefecture');
                    const cityInput = document.getElementById('city');
                    const address1Input = document.getElementById('address1');

                    if (prefectureInput && address.pref_name) {
                        prefectureInput.value = address.pref_name;
                    }
                    if (cityInput && address.city_name) {
                        cityInput.value = address.city_name;
                    }
                    if (address1Input && address.town_name) {
                        address1Input.value = address.town_name;
                    }
                } else {
                    alert(data.message || '郵便番号の検索に失敗しました。住所が見つかりませんでした。');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('郵便番号の検索中にエラーが発生しました。');
            })
            .finally(() => {
                // 検索ボタンを有効化
                searchBtn.disabled = false;
                searchBtn.innerHTML = originalHtml;
            });
        });
    }
});

// ログイン情報送信機能
document.addEventListener('DOMContentLoaded', function() {
    const sendPasswordBtn = document.querySelector('.btn-send-password');
    if (sendPasswordBtn) {
        sendPasswordBtn.addEventListener('click', function() {
            const businessId = this.getAttribute('data-business-id');
            if (!businessId) {
                alert('事業者IDが取得できませんでした。');
                return;
            }

            // 確認ダイアログ
            if (!confirm('事業者にログイン情報を送信しますか？')) {
                return;
            }

            // ボタンを無効化
            sendPasswordBtn.disabled = true;
            const originalHtml = sendPasswordBtn.innerHTML;
            sendPasswordBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>送信中...';

            // APIを呼び出し
            fetch(`/admin/business/${businessId}/send-login-info`, {
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
                    // 成功モーダルを表示
                    showSuccessModal('ログイン情報をメールで送信しました。');
					// メール未送信を送信済に変更
					const emailSentStatus = document.getElementById('email-sent-status');
					emailSentStatus.classList.remove('label-pending');
					emailSentStatus.classList.add('label-active');
					emailSentStatus.innerHTML = 'メール送信済';
                } else {
                    alert(data.message || 'ログイン情報の送信に失敗しました。');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('ログイン情報の送信中にエラーが発生しました。');
            })
            .finally(() => {
                // ボタンを有効化
                sendPasswordBtn.disabled = false;
                sendPasswordBtn.innerHTML = originalHtml;
            });
        });
    }

    // 成功モーダルを表示する関数
    function showSuccessModal(message) {
        // 既存のモーダルがあれば削除
        const existingModal = document.getElementById('success-modal');
        if (existingModal) {
            existingModal.remove();
        }

        // モーダルを作成
        const modal = document.createElement('div');
        modal.id = 'success-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        modal.innerHTML = `
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">送信完了</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
                        <p class="text-gray-700">${message}</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end">
                    <button type="button" class="btn-base btn-create btn-m" onclick="closeSuccessModal()">
                        閉じる
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // 背景クリックで閉じる
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeSuccessModal();
            }
        });
    }

    // モーダルを閉じる関数（グローバルスコープに配置）
    window.closeSuccessModal = function() {
        const modal = document.getElementById('success-modal');
        if (modal) {
            modal.remove();
        }
    };
});
</script>