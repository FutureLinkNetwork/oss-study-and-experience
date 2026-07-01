@extends('layouts.user')

@section('title', $mode === 'confirm' ? '事業者登録申請 - 確認' : '事業者登録申請')

@section('content')
<style>
    .floating-section-title {
        position: sticky;
        top: 0.75rem;
        z-index: 1100;
        background-color: rgb(255 255 255);
    }
</style>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">
            @if($mode === 'confirm')
                事業者登録申請フォーム - 確認
            @else
                事業者登録申請フォーム
            @endif
        </h1>
        
        @if($mode === 'confirm')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                    <div>
                        <h3 class="text-yellow-800 font-medium">申請内容をご確認ください</h3>
                        <p class="text-yellow-700 text-sm mt-1">
                            内容に間違いがなければ「申請する」ボタンを押してください。修正する場合は「戻る」ボタンを押してください。
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if($mode !== 'confirm')
        <div id="form-errors" class="mb-6">
            @if ($errors->any())
            <div class="alert-base alert-error alert-m">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="alert-message">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
        <div id="input-form-section">
        @endif
        @if($mode === 'confirm')
            @if ($errors->any())
            <div class="alert-base alert-error alert-m mb-6">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="alert-message">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        @endif

        <form method="POST"
              action="{{ $mode === 'confirm' ? route('business_application.store') : route('business_application.confirm') }}"
              class="space-y-8"
              enctype="multipart/form-data"
              @if($mode === 'confirm') id="applicationForm" onsubmit="return handleSubmit(this)" @else id="business-application-form" data-has-old-input="{{ count(array_filter(array_keys(old() ?? []), fn($k) => $k !== '_token' && $k !== '_previous_url')) > 0 ? '1' : '0' }}" @endif>
            @csrf
            @if($mode !== 'confirm' && config('recaptcha.enabled'))
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
            @endif
            <!-- 書類は config の max_size（KB）と一致させる（10MB） -->
            <input type="hidden" name="MAX_FILE_SIZE" value="{{ config('app.uploads.business_documents.max_size', 10240) * 1024 }}">
            
            <!-- 事業者情報 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 border-b border-gray-200 pb-3 {{ $mode !== 'confirm' ? 'floating-section-title' : '' }}">
                    <i class="fas fa-building mr-2 text-blue-600"></i>事業者情報
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 申請者種別 -->
                    <div class="cols-2">
					<div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                        <label class="field-label required label-l">申請者種別</label>
						<div class="flex space-x-6 mt-2 w-full" style="width: 400px;">
							@if($mode === 'confirm')
								<p class="text-gray-900">
									@if($data['applicant_type'] == 'corporation')
										法人
									@elseif($data['applicant_type'] == 'voluntary_group')
										任意団体
									@elseif($data['applicant_type'] == 'individual')
										個人事業主
									@endif
								</p>
								<input type="hidden" name="applicant_type" value="{{ $data['applicant_type'] }}">
                     	   @else
								<label class="flex items-center">
									<input type="radio" name="applicant_type" value="corporation" class="mr-2" {{ old('applicant_type') == 'corporation' ? 'checked' : '' }} required>
									法人
								</label>
								<label class="flex items-center">
									<input type="radio" name="applicant_type" value="voluntary_group" class="mr-2" {{ old('applicant_type') == 'voluntary_group' ? 'checked' : '' }} required>
									任意団体
								</label>
								<label class="flex items-center">
									<input type="radio" name="applicant_type" value="individual" class="mr-2" {{ old('applicant_type') == 'individual' ? 'checked' : '' }} required>
									個人事業主
								</label>
                	        @endif
						</div>
						</div>
                    </div>

                    <!-- 反社チェック（暴力団排除誓約） -->
                    <div class="md:col-span-2 mt-6">
						<div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                        <label class="field-label required label-l" style="float: left;">暴力団排除条項</label>
                        <div class="flex space-x-6 w-full">
                            @if($mode === 'confirm')
                                <p class="mt-1 text-gray-900">
                                    @if(!empty($data['antisocial_forces_pledged']))
                                        <i class="fas fa-check-circle text-green-600 mr-1"></i>誓約済
                                    @else
                                        未誓約
                                    @endif
                                </p>
                                <input type="hidden" name="antisocial_forces_pledged" value="{{ !empty($data['antisocial_forces_pledged']) ? '1' : '0' }}">
                            @else
                                <div class="flex flex-wrap items-center gap-4">
                                    <button type="button" id="btn-open-antisocial-modal" class="btn-base btn-search btn-s">
                                        <i class="fas fa-file-alt mr-2"></i>誓約事項
                                    </button>
                                    <label class="flex items-center cursor-not-allowed text-gray-400" id="antisocial-pledge-label">
                                        <input type="checkbox" name="antisocial_forces_pledged" id="antisocial_forces_pledged" value="1" class="mr-2" disabled
                                            {{ old('antisocial_forces_pledged') ? 'checked' : '' }}>
                                        <span id="antisocial-pledge-text">誓約事項を読み、誓約する</span>
                                    </label>
                                </div>
                                @error('antisocial_forces_pledged')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>
					</div>
                    </div>

                    <!-- 事業者名 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">事業者名</label>
                            <input type="text" name="business_name" value="{{ old('business_name', $data['business_name'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="200" required @if($mode === 'confirm') readonly @endif>
                        </div>
						<div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
						<label></label>
                        <div class="notice2">※屋号がない場合は､開業者名を姓と名の間を1文字（全角）空けてご記入ください</div>
						</div>
					</div>

                    <!-- 事業者名（カナ） -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">事業者名（カナ）</label>
                            <input type="text" name="business_name_kana" value="{{ old('business_name_kana', $data['business_name_kana'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="200" required @if($mode === 'confirm') readonly @endif>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label></label>
                            <div class="notice2">※屋号がない場合は､開業者名を姓と名の間を1文字（全角）空けてカタカナでご記入ください</div>
                        </div>
                    </div>

                    <!-- 代表者役職名 -->
                    <div class="md:col-span-2 mt-6 space-y-4">
                        <!-- 代表者役職名 -->
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">代表者役職名</label>
                            <input type="text"
                                   name="representative_title"
                                   value="{{ old('representative_title', $data['representative_title'] ?? '') }}"
                                   class="field-base field-w-100"
                                   maxlength="50"
                                   required
                                   @if($mode === 'confirm') readonly @endif
                                   placeholder="（例） 代表取締役">
                        </div>
                        <!-- 代表者役職名（カナ） -->
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">代表者役職名（カナ）</label>
                            <input type="text"
                                   name="representative_title_kana"
                                   value="{{ old('representative_title_kana', $data['representative_title_kana'] ?? '') }}"
                                   class="field-base field-w-100"
                                   maxlength="50"
                                   required
                                   @if($mode === 'confirm') readonly @endif
                                   placeholder="（例） ダイヒョウトリシマリヤク">
                        </div>
                    </div>

                    <!-- 代表者名 -->
                    <div class="md:col-span-2 mt-6 space-y-4">
                        <!-- 代表者名（姓・名） -->
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-start">
                            <label class="field-label required label-l mb-0">代表者名</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="text"
                                       name="representative_family_name"
                                       id="representative_family_name"
                                       value="{{ old('representative_family_name', $data['representative_family_name'] ?? '') }}"
                                       class="field-base field-w-100"
                                       maxlength="50"
                                       required
                                       @if($mode === 'confirm') readonly @endif
                                       placeholder="姓（例） 習事">
                                <input type="text"
                                       name="representative_given_name"
                                       id="representative_given_name"
                                       value="{{ old('representative_given_name', $data['representative_given_name'] ?? '') }}"
                                       class="field-base field-w-100"
                                       maxlength="50"
                                       required
                                       @if($mode === 'confirm') readonly @endif
                                       placeholder="名（例） 太郎">
                            </div>
                        </div>

                        <!-- 代表者名（カナ）（姓・名） -->
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-start">
                            <label class="field-label required label-l mb-0">代表者名（カナ）</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="text"
                                       name="representative_family_name_kana"
                                       value="{{ old('representative_family_name_kana', $data['representative_family_name_kana'] ?? '') }}"
                                       class="field-base field-w-100"
                                       maxlength="50"
                                       required
                                       @if($mode === 'confirm') readonly @endif
                                       placeholder="セイ（例） ナライゴト">
                                <input type="text"
                                       name="representative_given_name_kana"
                                       value="{{ old('representative_given_name_kana', $data['representative_given_name_kana'] ?? '') }}"
                                       class="field-base field-w-100"
                                       maxlength="50"
                                       required
                                       @if($mode === 'confirm') readonly @endif
                                       placeholder="メイ（例） タロウ">
                            </div>
                        </div>
                    </div>

                    <!-- 郵便番号 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">郵便番号</label>
                            <div class="flex gap-2">
                                <input type="text" name="postal_code" value="{{ old('postal_code', $data['postal_code'] ?? '') }}" 
                                       class="field-base field-w-80" placeholder="123-4567" required @if($mode === 'confirm') readonly @endif>
                                @if($mode != 'confirm')
                                    <a class="btn-base btn-search btn-m w-100 search-postal-code"><i class="fas fa-search mr-2"></i>検索</a>
                                @endif
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label></label>
                            <div class="notice2">※教室の郵便番号はxxx-xxxx形式で入力してください。<br />※事業所の所在地をご記入ください（教室情報は下部で入力いただきます）</div>
                        </div>
                    </div>

                    <!-- 都道府県 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">都道府県</label>
                            <input type="text" name="prefecture" id="prefecture" value="{{ old('prefecture', $data['prefecture'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="10" required @if($mode === 'confirm') readonly @endif>
                        </div>
                    </div>

                    <!-- 市区町村 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">市区町村</label>
                            <input type="text" name="city" id="city" value="{{ old('city', $data['city'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif>
                        </div>
                    </div>

                    <!-- 町名・番地 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">それ以降の住所</label>
                            <input type="text" name="address1" id="address1" value="{{ old('address1', $data['address1'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="100" required @if($mode === 'confirm') readonly @endif>
                        </div>
                    </div>

                    <!-- 建物名・部屋番号 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label label-l mb-0">建物名・部屋番号</label>
                            <input type="text" name="building_name" id="building_name" value="{{ old('building_name', $data['building_name'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="100" @if($mode === 'confirm') readonly @endif>
                        </div>
                    </div>

                    <!-- 電話番号 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">電話番号</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $data['phone'] ?? '') }}" 
                                   class="field-base field-w-100" placeholder="03-1234-5678" required @if($mode === 'confirm') readonly @endif>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label></label>
                            <div class="notice2">※半角数字、ハイフン「-」ありで入力してください</div>
                        </div>
                    </div>

                    <!-- FAX番号 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label label-l mb-0">FAX番号</label>
                            <input type="text" name="fax" value="{{ old('fax', $data['fax'] ?? '') }}" 
                                   class="field-base field-w-100" placeholder="03-1234-5678" @if($mode === 'confirm') readonly @endif>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label></label>
                            <div class="notice2">※半角数字、ハイフン「-」ありで入力してください</div>
                        </div>                        
                    </div>

                    <!-- メールアドレス -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">メールアドレス</label>
                            <input type="email" name="email" value="{{ old('email', $data['email'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="255" required @if($mode === 'confirm') readonly @endif placeholder="（例） example@example.com">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label></label>
                            <div class="notice2">※登録するメールアドレスの種類に制限はありませんが、 通知メールを受信できるように、ドメイン(@study-and-experience.jp)を迷惑メールフィルターの対象外とするなどの対応をお願いいたします</div>
                        </div>
                    </div>

                    <!-- ウェブサイトURL -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                            <label class="field-label label-l mb-0">ウェブサイトURL</label>
                            <input type="url" name="website_url" value="{{ old('website_url', $data['website_url'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="500" placeholder="https://example.com" @if($mode === 'confirm') readonly @endif>
                        </div>
                    </div>

                    <!-- 必須書類アップロード -->
                    <div class="md:col-span-2 mt-6" id="required-documents-section">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2 {{ $mode !== 'confirm' ? 'floating-section-title' : '' }}">
                            <i class="fas fa-upload mr-2 text-blue-600"></i>必須書類アップロード
                        </h3>
                        @if($mode === 'confirm')
                            @php
                                $applicantType = $data['applicant_type'] ?? '';
                                $documentLabels = \App\Models\BusinessInfo::getDocumentLabelsForApplicantType($applicantType);
                                $uploadedDocuments = $data['uploaded_documents'] ?? [];
                            @endphp
                            <div class="space-y-6">
                                @foreach($documentLabels as $docKey => $docInfo)
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <label class="field-label required label-l">{{ $docInfo['label'] }}</label>
                                        @foreach($docInfo['notice'] ?? [] as $noticeLine)
                                            <p class="text-sm text-amber-700 mt-1">{{ $noticeLine }}</p>
                                        @endforeach
                                        @if(in_array($docKey, ['corporation_citizen_tax_certificate', 'representative_citizen_tax_certificate', 'citizen_tax_certificate']))
                                            <p class="text-sm mt-1 mb-2">
                                                <a href="{{ asset('subdomain_assets/www/files/申立書.docx') }}" download class="text-blue-600 hover:underline" target="_blank" rel="noopener noreferrer">
                                                    <i class="fas fa-file-download mr-1"></i>申立書（Word形式）をダウンロード
                                                </a>
                                            </p>
                                        @endif
                                        @if(!empty($uploadedDocuments[$docKey]))
                                            <p class="mt-1 text-green-600">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                添付済み: {{ $uploadedDocuments[$docKey]['filename'] ?? 'ファイル' }}
                                            </p>
                                            <input type="hidden" name="documents_uploaded[{{ $docKey }}]" value="1">
                                            <input type="hidden" name="documents_filename[{{ $docKey }}]" value="{{ $uploadedDocuments[$docKey]['filename'] ?? '' }}">
                                        @else
                                            <p class="mt-1 text-red-500">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                未添付
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="space-y-6">
                                @foreach(['corporation' => '法人', 'voluntary_group' => '任意団体', 'individual' => '個人事業主'] as $type => $typeLabel)
                                    @php
                                        $labels = \App\Models\BusinessInfo::getDocumentLabelsForApplicantType($type);
                                    @endphp
                                    <div id="documents-{{ $type }}" class="document-type-block js-document-block" data-applicant-type="{{ $type }}" style="display: {{ old('applicant_type', $data['applicant_type'] ?? 'corporation') === $type ? 'block' : 'none' }};">
                                        @foreach($labels as $docKey => $docInfo)
                                            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                                <label class="field-label required label-l">{{ $docInfo['label'] }}</label>
                                                @foreach($docInfo['notice'] ?? [] as $noticeLine)
                                                    <p class="text-sm text-amber-700 mt-1 mb-2">{{ $noticeLine }}</p>
                                                @endforeach
                                                @if(in_array($docKey, ['corporation_citizen_tax_certificate', 'representative_citizen_tax_certificate', 'corporate_tax_certificate_no2', 'citizen_tax_certificate']))
                                                    <p class="text-sm mt-1 mb-2">
                                                        <a href="{{ asset('subdomain_assets/www/files/申立書.txt') }}" download class="text-blue-600 hover:underline" target="_blank" rel="noopener noreferrer">
                                                            <i class="fas fa-file-download mr-1"></i>申立書をダウンロード
                                                        </a>
                                                    </p>
                                                @endif
                                                <p class="text-sm text-gray-600 mt-1 mb-3">
                                                    対応形式のファイルをアップロードしてください。
                                                </p>
                                                <input type="file"
                                                       name="documents[{{ $docKey }}]"
                                                       class="field-base field-w-100 js-document-file @error('documents.'.$docKey) error @enderror"
                                                       accept=".jpeg,.jpg,.png,.pdf,.xls,.xlsx,.doc,.docx"
                                                       data-doc-key="{{ $docKey }}"
                                                       {{ old('applicant_type', $data['applicant_type'] ?? 'corporation') === $type ? 'required' : '' }}>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    対応形式: JPEG, PNG, PDF, Excel, Word (最大10MB)
                                                </p>
                                                @error('documents.'.$docKey)
                                                    <span class="field-error">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- 連絡先情報 -->
                    <div class="md:col-span-2 mt-6">
						<h3 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2 {{ $mode !== 'confirm' ? 'floating-section-title' : '' }}">
                    		<i class="fas fa-user mr-2 text-green-600"></i>連絡先情報
                		</h3>

                    </div>

                    @if($mode !== 'confirm')
                    <!-- 事業者情報と同じ内容を入力ボタン（ボックス内の項目に事業者情報がコピーされる） -->
                    <div class="md:col-span-2 bg-gray-100 p-4 rounded-lg">
                        <button type="button" id="copy-business-info-btn" class="btn-base btn-copy btn-s">
                            <i class="fas fa-user mr-2"></i>事業者情報と同じ内容を入力
                        </button>
                    @endif

                        <!-- 連絡先担当者名 -->
                        <div class="md:col-span-2 mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                                <label class="field-label required label-l mb-0">連絡先：担当者名</label>
                                <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person', $data['contact_person'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="100" required @if($mode === 'confirm') readonly @endif>
                            </div>
                        </div>

                        <!-- 連絡先担当者電話番号 -->
                        <div class="md:col-span-2 mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                                <label class="field-label required label-l mb-0">連絡先：電話番号</label>
                                <input type="text" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $data['contact_phone'] ?? '') }}"
                                       class="field-base field-w-100" placeholder="03-1234-5678" required @if($mode === 'confirm') readonly @endif>
                            </div>
                        </div>

                        <!-- 文書等送付先：宛名 -->
                        <div class="md:col-span-2 mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                                <label class="field-label required label-l mb-0">文書等送付先：宛名</label>
                                <input type="text" name="document_person" id="document_person" value="{{ old('document_person', $data['document_person'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="100" required @if($mode === 'confirm') readonly @endif>
                            </div>
                        </div>

                        <!-- 文書等送付先：住所 -->
                        <div class="md:col-span-2 mt-6">
                            <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                                <label class="field-label required label-l mb-0">文書等送付先：住所</label>
                                <input type="text" name="document_address" id="document_address" value="{{ old('document_address', $data['document_address'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="255" required @if($mode === 'confirm') readonly @endif>
                            </div>
                        </div>
                    @if($mode !== 'confirm')
                    </div>
                    @endif

                    <!-- 営業時間 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">連絡先：営業時間</label>
                            <input type="text" name="business_hours" value="{{ old('business_hours', $data['business_hours'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="200" placeholder="平日 9:00-18:00" required @if($mode === 'confirm') readonly @endif>
                        </div>
                    </div>

                    <!-- 定休日 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">連絡先：定休日</label>
                            <input type="text" name="holiday" value="{{ old('holiday', $data['holiday'] ?? '') }}" 
                                   class="field-base field-w-100" maxlength="100" placeholder="土日祝日" required @if($mode === 'confirm') readonly @endif>
                        </div>
                    </div>
                </div>

                <!-- 振込先情報 -->
                <h3 class="text-lg font-semibold text-gray-900 mt-8 mb-4 border-b border-gray-200 pb-2 {{ $mode !== 'confirm' ? 'floating-section-title' : '' }}">
                    <i class="fas fa-university mr-2 text-green-600"></i>振込先情報
                </h3>
                <div class="notice2">※１事業者につき登録できる口座は１つになります。</div>
                <div class="notice2">※ゆうちょ銀行は、他金融機関からの振込用口座に限ります。</div>
                <br>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- 銀行・支店選択 -->
                    <div class="md:col-span-2">
                        <x-form.bank-branch-select 
                            :bank-name="'bank_code'"
                            :branch-name="'branch_code'"
                            :bank-value="old('bank_code', $data['bank_code'] ?? '')"
                            :branch-value="old('branch_code', $data['branch_code'] ?? '')"
                            :bank-required="true"
                            :branch-required="true"
                            :bank-label="'銀行名'"
                            :branch-label="'支店名'"
                            :mode="$mode"
                            :data="$data"
                        />
                    </div>

                    <!-- 口座種別 -->
                    <div class="md:col-span-2 mt-6">
						<div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                        <label class="field-label required label-l mb-0">口座種別</label>
						<div class="flex space-x-6 mt-2 w-full">
                        @if($mode === 'confirm')
                            <p class="mt-1 text-gray-900">{{ $data['account_type'] }}</p>
                            <input type="hidden" name="account_type" value="{{ $data['account_type'] }}">
                        @else
							<input type="radio" name="account_type" value="普通" class="mr-2 ml-6" {{ old('account_type', $data['account_type'] ?? '') == '普通' ? 'checked' : '' }} required> 普通
							<input type="radio" name="account_type" value="当座" class="mr-2 ml-6" {{ old('account_type', $data['account_type'] ?? '') == '当座' ? 'checked' : '' }} required> 当座
                        @endif
						</div>
						</div>
                    </div>

                    <!-- 口座番号 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">口座番号</label>
                            @if($mode === 'confirm')
                                <p class="mt-1 text-gray-900">{{ $data['account_number'] }}</p>
                                <input type="hidden" name="account_number" value="{{ $data['account_number'] }}">
                            @else
                                <input type="text" name="account_number" value="{{ old('account_number', $data['account_number'] ?? '') }}" 
                                       class="field-base field-w-100" maxlength="7" inputmode="numeric" pattern="[0-9]*" title="半角数字のみで入力してください" oninput="value = value.replace(/[^0-9]/g, '')" required>
                            @endif
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                            <label></label>
                            <div class="notice2">※半角数字　７ケタでご記入ください。</div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                            <label></label>
                            <div class="notice2">※7桁未満の場合は、前に0をつけて入力してください。</div>
                        </div>
                    </div>

                    <!-- 口座名義 -->
                    <div class="md:col-span-2 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-x-4 items-center">
                            <label class="field-label required label-l mb-0">口座名義（カナ）</label>
                            @if($mode === 'confirm')
                                <p class="mt-1 text-gray-900">{{ $data['account_holder'] }}</p>
                                <input type="hidden" name="account_holder" value="{{ $data['account_holder'] }}">
                            @else
                                <input type="text" name="account_holder" value="{{ old('account_holder', $data['account_holder'] ?? '') }}" 
                                       class="field-base field-w-100" maxlength="100" required>
                            @endif
                            <label></label>
                            <div class="notice2">※半角カナ・半角英大文字・数字・( ) . ｰ / , ・半角スペースのみ使用できます。</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 教室情報 -->
            @if($mode === 'confirm')
                @if(isset($data['classrooms']) && count($data['classrooms']) > 0)
                    @foreach($data['classrooms'] as $index => $classroom)
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6 border-b border-gray-200 pb-3">
                                <i class="fas fa-school mr-2 text-purple-600"></i>教室情報 {{ $index + 1 }}
                            </h2>
                            
                            @include('business_application.classroom_form', ['classroom' => $classroom, 'index' => $index, 'isReadonly' => true, 'latitude' => $classroom['classroom_latitude'] , 'longitude' => $classroom['classroom_longitude'] ])
                        </div>
                    @endforeach
                @endif
            @else
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-3 {{ $mode !== 'confirm' ? 'floating-section-title' : '' }}">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <i class="fas fa-school mr-2 text-purple-600"></i>教室情報
                        </h2>
                        <button type="button" id="add-classroom" class="btn-base btn-create btn-s">
                            <i class="fas fa-plus mr-1"></i>教室を追加
                        </button>
                    </div>

                    <div id="classrooms-container">
                        <!-- 初期の教室フォーム -->
                        <div class="classroom-form border border-gray-200 rounded-lg p-6 mb-6" data-index="0">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">教室 1</h3>
                                <button type="button" class="remove-classroom btn-base btn-disable btn-xs" style="display: none;">
                                    <i class="fas fa-trash mr-1"></i>削除
                                </button>
                            </div>
                            
                            @include('business_application.classroom_form', ['index' => 0, 'latitude' => $latitude, 'longitude' => $longitude])
                        </div>
                    </div>
                </div>
            @endif

            <!-- 登録教室募集要項及びプライバシーポリシー同意 -->
            <div class="bg-white rounded-lg shadow-md p-6">
				<div class="md:col-span-2">
                        <label class="field-label required label-l">登録教室募集要項及びプライバシーポリシー</label>
						@if($mode === 'confirm')
                        <p class="mt-1 text-gray-900">
                            @if(!empty($data['privacy_policy_agreed']))
                                <i class="fas fa-check-circle text-green-600 mr-1"></i>同意済
                            @else
                                未同意
                            @endif
                        </p>
                        <input type="hidden" name="privacy_policy_agreed" value="{{ !empty($data['privacy_policy_agreed']) ? '1' : '0' }}">
                    @else
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="privacy_policy_agreed" id="privacy_policy_agreed" value="1" class="mr-2"
                                {{ old('privacy_policy_agreed') ? 'checked' : '' }}>
                            <span><a href="#" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">登録教室募集要項</a>及び<a href="#" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">プライバシーポリシー</a>に同意する</span>
                        </label>
                        @error('privacy_policy_agreed')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    @endif
				</div>
            </div>

            <!-- 送信ボタン -->
            <div class="flex flex-col items-{{ $mode === 'confirm' ? 'stretch' : 'end' }} gap-2 mb-20 sm:mb-8">
                @if($mode !== 'confirm')
                    @error('g-recaptcha-response')
                        <div class="text-red-600 text-sm">{{ $message }}</div>
                    @enderror
                @endif
                <div class="flex {{ $mode === 'confirm' ? 'justify-between' : 'justify-end' }} gap-4">
                    @if($mode === 'confirm')
                        <a href="{{ route('business_application.create') }}" class="btn-base btn-back btn-m">
                            <i class="fas fa-arrow-left mr-2"></i>戻る
                        </a>
                        <button type="submit" class="btn-base btn-create btn-m" id="submitBtn">
                            <i class="fas fa-paper-plane mr-2"></i>申請する
                        </button>
                    @else
                        <a href="{{ route('welcome') }}" class="btn-base btn-cancel btn-m">
                            キャンセル
                        </a>
                        <button type="submit" class="btn-base btn-create btn-m" id="confirmStepBtn" disabled title="プライバシーポリシーに同意すると押せます">
                            <i class="fas fa-check mr-2"></i>確認画面へ
                        </button>
                    @endif
                </div>
            </div>
        </form>
        @if($mode !== 'confirm')
        </div>
        <div id="confirm-section" class="hidden" aria-hidden="true"></div>
        @endif

        @if($mode !== 'confirm')
        <!-- 反社誓約事項モーダル -->
        <div id="antisocial-modal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog" style="z-index: 1200;" aria-labelledby="antisocial-modal-title">
            <div class="fixed inset-0 bg-black/50" id="antisocial-modal-backdrop"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full flex flex-col min-h-0" style="height: 85%;">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 shrink-0">
                        <h2 id="antisocial-modal-title" class="text-lg font-semibold text-gray-900">誓約事項</h2>
                        <button type="button" id="btn-close-antisocial-modal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="閉じる">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto overflow-x-hidden whitespace-pre-wrap text-sm text-gray-700 max-h-[60vh]">{{ isset($antisocialForcesText) ? e($antisocialForcesText) : '' }}</div>
                    <div class="p-4 border-t border-gray-200 flex justify-end shrink-0">
                        <button type="button" id="btn-close-antisocial-modal-footer" class="btn-base btn-create btn-m">閉じる</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @if($mode !== 'confirm')
    <!-- 一時保存ボタン（スクロール追従・ビューポート基準で固定） -->
    <div id="draft-save-wrapper" class="fixed z-40" style="bottom: 1.5rem; left: 1.5rem; right: auto;">
        <button type="button" id="btn-draft-save" class="btn-base btn-create btn-m shadow-lg">
            <i class="fas fa-save mr-2"></i>一時保存
        </button>
    </div>
    <div id="draft-privacy-notice" class="hidden fixed z-40 max-w-sm bg-amber-50 border border-amber-200 rounded-lg p-4 shadow-lg text-sm text-amber-800" style="bottom: 10rem; left: 1.5rem; right: auto;">
        <p class="font-medium mb-1">一時保存について</p>
        <p>入力内容はこの端末のみに保存されます。共有のパソコンでは利用を控えてください。</p>
    </div>
    <div id="draft-saved-toast" class="hidden fixed z-40 max-w-sm bg-green-50 border border-green-200 rounded-lg p-4 shadow-lg text-sm text-green-800" style="bottom: 5rem; left: 1.5rem; right: auto;">
        一時保存しました。
    </div>
    @endif
</div>

@if($mode !== 'confirm' && config('recaptcha.enabled'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}"></script>
@endif
@if($mode !== 'confirm')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    (function() {
        var form = document.getElementById('business-application-form');
        var inputSection = document.getElementById('input-form-section');
        var confirmSection = document.getElementById('confirm-section');
        var formErrors = document.getElementById('form-errors');
        var confirmUrl = '{{ route('business_application.confirm') }}';
        var storeUrl = '{{ route('business_application.store') }}';
        var completeUrl = '{{ route('business_application.complete') }}';
        var siteKey = '{{ config('recaptcha.site_key') }}';
        var recaptchaEnabled = {{ config('recaptcha.enabled') ? 'true' : 'false' }};

        function showFormErrors(errors) {
            if (!formErrors) return;
            var list = [];
            if (errors && typeof errors === 'object') {
                Object.keys(errors).forEach(function(key) {
                    var messages = errors[key];
                    if (Array.isArray(messages)) {
                        messages.forEach(function(m) { list.push(m); });
                    } else if (typeof messages === 'string') {
                        list.push(messages);
                    }
                });
            }
            formErrors.innerHTML = list.length
                ? '<div class="alert-base alert-error alert-m"><div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div><div class="alert-message"><ul class="list-disc list-inside">' +
                  list.map(function(m) { return '<li>' + (m.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')) + '</li>'; }).join('') + '</ul></div></div>'
                : '';
        }

        function setConfirmSectionErrors(errors) {
            var el = document.getElementById('confirm-section-errors');
            if (!el) return;
            var list = [];
            if (errors && typeof errors === 'object') {
                Object.keys(errors).forEach(function(key) {
                    var messages = errors[key];
                    if (Array.isArray(messages)) {
                        messages.forEach(function(m) { list.push(m); });
                    } else if (typeof messages === 'string') {
                        list.push(messages);
                    }
                });
            }
            if (list.length) {
                el.innerHTML = '<div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div><div class="alert-message"><ul class="list-disc list-inside">' +
                    list.map(function(m) { return '<li>' + (m.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')) + '</li>'; }).join('') + '</ul></div>';
                el.classList.remove('hidden');
                el.classList.add('mb-6');
                el.setAttribute('aria-hidden', 'false');
            } else {
                el.innerHTML = '';
                el.classList.add('hidden');
                el.classList.remove('mb-6');
                el.setAttribute('aria-hidden', 'true');
            }
        }

        function doConfirmSubmit(formData) {
            var btn = document.getElementById('confirmStepBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>確認中...';
            }
            fetch(confirmUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(function(res) {
                return res.text().then(function(text) {
                    var data = {};
                    if (text) {
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            data = {};
                        }
                    }
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .then(function(result) {
                if (result.status === 419) {
                    alert('セッションの有効期限が切れました。お手数ですが、ページを再読み込みしてから再度お試しください。');
                } else if (result.status === 422 && result.data.errors) {
                    showFormErrors(result.data.errors);
                    if (formErrors) {
                        formErrors.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else if (result.ok && result.data.confirm_html) {
                    confirmSection.innerHTML = result.data.confirm_html;
                    confirmSection.classList.remove('hidden');
                    confirmSection.setAttribute('aria-hidden', 'false');
                    if (inputSection) {
                        inputSection.classList.add('hidden');
                        inputSection.setAttribute('aria-hidden', 'true');
                    }

                    // 確認画面内の教室地図を初期化
                    if (typeof initializeClassroomMapForContainer === 'function') {
                        confirmSection.querySelectorAll('.classroom-map').forEach(function(mapContainer) {
                            var idx = mapContainer.getAttribute('data-index');

                            if (idx !== null && !mapContainer.hasAttribute('data-initialized')) {
                                initializeClassroomMapForContainer(mapContainer);
                            }
                        });
                    }

                    confirmSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    var backBtn = confirmSection.querySelector('#btn-back-to-form');
                    if (backBtn) {
                        backBtn.addEventListener('click', function() {
                            confirmSection.innerHTML = '';
                            confirmSection.classList.add('hidden');
                            confirmSection.setAttribute('aria-hidden', 'true');
                            if (inputSection) {
                                inputSection.classList.remove('hidden');
                                inputSection.setAttribute('aria-hidden', 'false');
                            }
                        });
                    }
                    var applicationForm = confirmSection.querySelector('#applicationForm');
                    if (applicationForm) {
                        applicationForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            var submitBtn = confirmSection.querySelector('#submitBtn');
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>申請中...';
                            }
                            var fd = new FormData(applicationForm);
                            fetch(storeUrl, {
                                method: 'POST',
                                body: fd,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin'
                            })
                            .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, status: res.status, data: data }; }); })
                            .then(function(result) {
                                if (result.status === 422 && result.data.errors) {
                                    setConfirmSectionErrors(result.data.errors);
                                    if (submitBtn) {
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>申請する';
                                    }
                                } else if (result.ok && result.data.redirect_url) {
                                    window.location.href = result.data.redirect_url;
                                } else {
                                    setConfirmSectionErrors({ general: ['申請の処理中にエラーが発生しました。'] });
                                    if (submitBtn) {
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>申請する';
                                    }
                                }
                            })
                            .catch(function() {
                                setConfirmSectionErrors({ general: ['通信エラーが発生しました。'] });
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>申請する';
                                }
                            });
                        });
                    }
                }
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check mr-2"></i>確認画面へ';
                }
            })
            .catch(function() {
                showFormErrors({ general: ['通信エラーが発生しました。'] });
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check mr-2"></i>確認画面へ';
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                showFormErrors();
                if (recaptchaEnabled && typeof grecaptcha !== 'undefined') {
                    grecaptcha.ready(function() {
                        grecaptcha.execute(siteKey, { action: 'business_application' }).then(function(token) {
                            var gInput = document.getElementById('g-recaptcha-response');
                            if (gInput) gInput.value = token;
                            var formData = new FormData(form);
                            doConfirmSubmit(formData);
                        });
                    });
                } else {
                    doConfirmSubmit(new FormData(form));
                }
            });
        }
    })();
    });
    </script>
@endif
@if($mode !== 'confirm')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // プライバシーポリシー未チェック時は「確認画面へ」ボタンを無効化
    (function() {
        var privacyCheckbox = document.getElementById('privacy_policy_agreed');
        var confirmStepBtn = document.getElementById('confirmStepBtn');
        if (privacyCheckbox && confirmStepBtn) {
            function updateConfirmButton() {
                confirmStepBtn.disabled = !privacyCheckbox.checked;
            }
            updateConfirmButton();
            privacyCheckbox.addEventListener('change', updateConfirmButton);
        }
    })();

    // 申請者種別変更時：該当する必須書類ブロックを表示し、他を非表示。既存のファイル選択はクリアする。
    (function() {
        var section = document.getElementById('required-documents-section');
        if (!section) return;
        var radios = document.querySelectorAll('input[name="applicant_type"]');
        var blocks = section.querySelectorAll('.js-document-block');
        function switchDocumentBlock() {
            var selected = document.querySelector('input[name="applicant_type"]:checked');
            var type = selected ? selected.value : '';
            blocks.forEach(function(block) {
                var show = block.getAttribute('data-applicant-type') === type;
                block.style.display = show ? 'block' : 'none';
                var inputs = block.querySelectorAll('input.js-document-file');
                inputs.forEach(function(input) {
                    input.value = '';
                    input.required = show;
                    input.disabled = !show;
                });
            });
        }
        switchDocumentBlock();
        radios.forEach(function(radio) {
            radio.addEventListener('change', switchDocumentBlock);
        });
    })();

    // 必須書類アップロード：添付時に10MBチェック
    (function() {
        var section = document.getElementById('required-documents-section');
        if (!section) return;
        var MAX_DOCUMENT_SIZE = 10 * 1024 * 1024; // 10MB
        section.addEventListener('change', function(e) {
            var input = e.target;
            if (!input.matches || !input.matches('input.js-document-file')) return;
            if (!input.files || !input.files[0]) return;
            var file = input.files[0];
            if (file.size > MAX_DOCUMENT_SIZE) {
                alert('必須書類は10MB以下のファイルをアップロードしてください。');
                input.value = '';
            }
        });
    })();

    // 反社誓約モーダル：開く・閉じる、閉じたらチェック可能に
    (function() {
        var modal = document.getElementById('antisocial-modal');
        var btnOpen = document.getElementById('btn-open-antisocial-modal');
        var btnClose = document.getElementById('btn-close-antisocial-modal');
        var btnCloseFooter = document.getElementById('btn-close-antisocial-modal-footer');
        var backdrop = document.getElementById('antisocial-modal-backdrop');
        var checkbox = document.getElementById('antisocial_forces_pledged');
        var label = document.getElementById('antisocial-pledge-label');
        var labelText = document.getElementById('antisocial-pledge-text');
        if (!modal || !btnOpen) return;
        function openModal() {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            if (checkbox && label && labelText) {
                checkbox.disabled = false;
                label.classList.remove('cursor-not-allowed', 'text-gray-400');
                label.classList.add('cursor-pointer', 'text-gray-900');
            }
        }
        btnOpen.addEventListener('click', openModal);
        if (btnClose) btnClose.addEventListener('click', closeModal);
        if (btnCloseFooter) btnCloseFooter.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);
    })();

    let classroomCount = 1;
    const maxClassrooms = 5;
    
    const addClassroomBtn = document.getElementById('add-classroom');
    const classroomsContainer = document.getElementById('classrooms-container');
    
    // 「事業者情報と同じ内容を入力」ボタンの処理
    const copyBusinessInfoBtn = document.getElementById('copy-business-info-btn');

    if (copyBusinessInfoBtn) {
        copyBusinessInfoBtn.addEventListener('click', function() {
            copyBusinessInfo();
        });
    }

    function buildRepresentativeFullName() {
        const family = document.getElementById('representative_family_name');
        const given = document.getElementById('representative_given_name');
        const familyVal = family ? family.value.trim() : '';
        const givenVal = given ? given.value.trim() : '';
        if (!familyVal && !givenVal) {
            return '';
        }
        return familyVal && givenVal ? familyVal + '　' + givenVal : (familyVal || givenVal);
    }

    function copyBusinessInfo() {
        // 代表者名（姓＋名） → 連絡先担当者名
        const representativeFullName = buildRepresentativeFullName();
        const contactPerson = document.getElementById('contact_person');
        if (contactPerson) {
            contactPerson.value = representativeFullName;
        }
        
        // 電話番号 → 連絡先担当者電話番号
        const phoneInput = document.getElementById('phone');
        const contactPhone = document.getElementById('contact_phone');
        if (phoneInput && contactPhone) {
            contactPhone.value = phoneInput.value || '';
        }
        
        // 代表者名（姓＋名） → 文書等送付先：宛名
        const documentPerson = document.getElementById('document_person');
        if (documentPerson) {
            documentPerson.value = representativeFullName;
        }
        
        // 都道府県 + 市区町村 + 町名・番地 + 建物名 → 文書等送付先：住所
        const prefecture = document.getElementById('prefecture').value || '';
        const city = document.getElementById('city').value || '';
        const address1 = document.getElementById('address1').value || '';
        const buildingName = document.getElementById('building_name').value || '';
        
        let fullAddress = prefecture + city + address1;
        if (buildingName) {
            fullAddress += ' ' + buildingName;
        }
        document.getElementById('document_address').value = fullAddress;
    }

    // 教室追加
    addClassroomBtn.addEventListener('click', function() {
        if (classroomCount >= maxClassrooms) {
            alert('教室は最大5件まで登録できます。');
            return;
        }
        
        const newClassroom = createClassroomForm(classroomCount);
        classroomsContainer.appendChild(newClassroom);
        classroomCount++;
        
        const newIndex = classroomCount - 1;
        if (typeof setupUseMapCheckbox === 'function') {
            setupUseMapCheckbox(newIndex);
        }
        if (typeof initializeClassroomMap === 'function') {
            initializeClassroomMap(newIndex);
        }
        
        updateRemoveButtons();
        
        if (classroomCount >= maxClassrooms) {
            addClassroomBtn.style.display = 'none';
        }
    });
    
    // 教室削除
    classroomsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-classroom')) {
            const classroomForm = e.target.closest('.classroom-form');
            classroomForm.remove();
            classroomCount--;
            
            updateClassroomNumbers();
            updateRemoveButtons();
            
            if (classroomCount < maxClassrooms) {
                addClassroomBtn.style.display = 'inline-flex';
            }
        }
    });
    
    function createClassroomForm(index) {
        const template = document.querySelector('.classroom-form').cloneNode(true);
        template.setAttribute('data-index', index);
        
        // フィールド名を更新（ラジオ・チェックボックスは value を消さず checked のみ解除）
        const inputs = template.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name && name.startsWith('classrooms[0]')) {
                input.setAttribute('name', name.replace('classrooms[0]', `classrooms[${index}]`));
                if (input.type === 'radio' || input.type === 'checkbox') {
                    input.checked = false;
                } else if (input.type === 'hidden' && name.includes('[use_map]')) {
                    input.value = '1';
                } else {
                    input.value = '';
                }
            }
        });
        // textarea は cloneNode でテキストノードもコピーされるため明示的にクリア
        template.querySelectorAll('textarea').forEach(ta => { ta.value = ''; ta.textContent = ''; });
        
        // IDを更新（地図、緯度、経度、use_mapなど）
        const mapContainer = template.querySelector('.classroom-map');
        if (mapContainer) {
            mapContainer.id = `map-${index}`;
            mapContainer.setAttribute('data-index', index);
            mapContainer.removeAttribute('data-initialized'); // 初期化フラグをリセット
        }
        
        const mapSection = template.querySelector('.classroom-map-section');
        if (mapSection) {
            mapSection.setAttribute('data-index', index);
            mapSection.style.display = '';
        }
        
        const useMapCheckbox = template.querySelector('#use-map-checkbox-0');
        if (useMapCheckbox) {
            useMapCheckbox.id = `use-map-checkbox-${index}`;
            useMapCheckbox.checked = false;
            useMapCheckbox.setAttribute('data-index', index);
        }
        
        const useMapHidden = template.querySelector('#use-map-0');
        if (useMapHidden) {
            useMapHidden.id = `use-map-${index}`;
            useMapHidden.value = '1';
        }
        
        const latInput = template.querySelector('#latitude-0');
        if (latInput) {
            latInput.id = `latitude-${index}`;
        }
        
        const lngInput = template.querySelector('#longitude-0');
        if (lngInput) {
            lngInput.id = `longitude-${index}`;
        }
        
        // 「事業者情報と同じ内容を入力」ボタンのIDとonclickイベントを更新
        const copyBusinessInfoBtn = template.querySelector('#copy-business-info-btn-0');
        if (copyBusinessInfoBtn) {
            copyBusinessInfoBtn.id = `copy-business-info-btn-${index}`;
            copyBusinessInfoBtn.setAttribute('onclick', `copyBusinessInfoToClassroom(${index})`);
        }
        const repNameEl = template.querySelector('#classroom_representative_name-0');
        const repNameKanaEl = template.querySelector('#classroom_representative_name_kana-0');
        if (repNameEl) { repNameEl.id = `classroom_representative_name-${index}`; }
        if (repNameKanaEl) { repNameKanaEl.id = `classroom_representative_name_kana-${index}`; }
        // 教室情報の各フィールドのIDを更新
        const classroomPostalCode = template.querySelector('#classroom_postal_code-0');
        if (classroomPostalCode) {
            classroomPostalCode.id = `classroom_postal_code-${index}`;
        }
        const classroomPrefecture = template.querySelector('#classroom_prefecture-0');
        if (classroomPrefecture) {
            classroomPrefecture.id = `classroom_prefecture-${index}`;
        }
        const classroomCity = template.querySelector('#classroom_city-0');
        if (classroomCity) {
            classroomCity.id = `classroom_city-${index}`;
        }
        const classroomAddress1 = template.querySelector('#classroom_address1-0');
        if (classroomAddress1) {
            classroomAddress1.id = `classroom_address1-${index}`;
        }
        const classroomBuildingName = template.querySelector('#classroom_building_name-0');
        if (classroomBuildingName) {
            classroomBuildingName.id = `classroom_building_name-${index}`;
        }
        const classroomPhone = template.querySelector('#classroom_phone-0');
        if (classroomPhone) {
            classroomPhone.id = `classroom_phone-${index}`;
        }
        const classroomFax = template.querySelector('#classroom_fax-0');
        if (classroomFax) {
            classroomFax.id = `classroom_fax-${index}`;
        }
        const classroomEmail = template.querySelector('#classroom_email-0');
        if (classroomEmail) {
            classroomEmail.id = `classroom_email-${index}`;
        }
        
        // プレビュー関連のIDも更新し、表示状態・画像をリセット
        const previewContainer = template.querySelector('#classroom-image-preview-0');
        if (previewContainer) {
            previewContainer.id = `classroom-image-preview-${index}`;
            previewContainer.classList.add('hidden');
        }
        
        const previewImg = template.querySelector('#classroom-image-preview-img-0');
        if (previewImg) {
            previewImg.id = `classroom-image-preview-img-${index}`;
            previewImg.src = '';
        }
        
        // 画像アップロードのonchangeイベントを更新
        const imageInput = template.querySelector('input[type="file"][name*="classroom_image"]');
        if (imageInput) {
            imageInput.setAttribute('onchange', `previewClassroomImage(this, ${index})`);
        }
        const clearPreviewButton = template.querySelector('button[onclick^="clearClassroomImagePreview("]');
        if (clearPreviewButton) {
            clearPreviewButton.setAttribute('onclick', `clearClassroomImagePreview(${index})`);
        }

        const classroomPostalSearchButton = template.querySelector('.search-classroom-postal-code');
        if (classroomPostalSearchButton) {
            classroomPostalSearchButton.setAttribute('data-index', index);
        }
        
        // ラベルとタイトルを更新
        template.querySelector('h3').textContent = `教室 ${index + 1}`;
        
        return template;
    }
    
    function updateClassroomNumbers() {
        const classrooms = document.querySelectorAll('.classroom-form');
        classrooms.forEach((classroom, index) => {
            classroom.setAttribute('data-index', index);
            classroom.querySelector('h3').textContent = `教室 ${index + 1}`;
            
            // フィールド名を更新
            const inputs = classroom.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.startsWith('classrooms[')) {
                    const newName = name.replace(/classrooms\[\d+\]/, `classrooms[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
            
            // IDを更新（地図、緯度、経度、use_mapなど）
            const mapContainer = classroom.querySelector('.classroom-map');
            if (mapContainer) {
                mapContainer.id = `map-${index}`;
                mapContainer.setAttribute('data-index', index);
                mapContainer.removeAttribute('data-initialized'); // 初期化フラグをリセット
            }
            
            const mapSection = classroom.querySelector('.classroom-map-section');
            if (mapSection) {
                mapSection.setAttribute('data-index', index);
            }
            
            const useMapCheckbox = classroom.querySelector('[id^="use-map-checkbox-"]');
            if (useMapCheckbox) {
                useMapCheckbox.id = `use-map-checkbox-${index}`;
                useMapCheckbox.setAttribute('data-index', index);
            }
            
            const useMapHidden = classroom.querySelector('input[type="hidden"][name*="[use_map]"]');
            if (useMapHidden) {
                useMapHidden.id = `use-map-${index}`;
            }
            
            const latInput = classroom.querySelector('input[id^="latitude-"]');
            if (latInput) {
                latInput.id = `latitude-${index}`;
            }
            
            const lngInput = classroom.querySelector('input[id^="longitude-"]');
            if (lngInput) {
                lngInput.id = `longitude-${index}`;
            }
            
            // 「事業者情報と同じ内容を入力」ボタンのIDとonclickイベントを更新
            const copyBusinessInfoBtn = classroom.querySelector('[id^="copy-business-info-btn-"]');
            if (copyBusinessInfoBtn) {
                copyBusinessInfoBtn.id = `copy-business-info-btn-${index}`;
                copyBusinessInfoBtn.setAttribute('onclick', `copyBusinessInfoToClassroom(${index})`);
            }
            
            // 教室情報の各フィールドのIDを更新
            const classroomPostalCode = classroom.querySelector('[id^="classroom_postal_code-"]');
            if (classroomPostalCode) {
                classroomPostalCode.id = `classroom_postal_code-${index}`;
            }
            const classroomPrefecture = classroom.querySelector('[id^="classroom_prefecture-"]');
            if (classroomPrefecture) {
                classroomPrefecture.id = `classroom_prefecture-${index}`;
            }
            const classroomCity = classroom.querySelector('[id^="classroom_city-"]');
            if (classroomCity) {
                classroomCity.id = `classroom_city-${index}`;
            }
            const classroomAddress1 = classroom.querySelector('[id^="classroom_address1-"]');
            if (classroomAddress1) {
                classroomAddress1.id = `classroom_address1-${index}`;
            }
            const classroomBuildingName = classroom.querySelector('[id^="classroom_building_name-"]');
            if (classroomBuildingName) {
                classroomBuildingName.id = `classroom_building_name-${index}`;
            }
            const classroomPhone = classroom.querySelector('[id^="classroom_phone-"]');
            if (classroomPhone) {
                classroomPhone.id = `classroom_phone-${index}`;
            }
            const classroomFax = classroom.querySelector('[id^="classroom_fax-"]');
            if (classroomFax) {
                classroomFax.id = `classroom_fax-${index}`;
            }
            const classroomEmail = classroom.querySelector('[id^="classroom_email-"]');
            if (classroomEmail) {
                classroomEmail.id = `classroom_email-${index}`;
            }

            const classroomPostalSearchButton = classroom.querySelector('.search-classroom-postal-code');
            if (classroomPostalSearchButton) {
                classroomPostalSearchButton.setAttribute('data-index', index);
            }
            
            // プレビュー関連のIDも更新
            const previewContainer = classroom.querySelector('[id^="classroom-image-preview-"]:not([id*="img-"])');
            if (previewContainer) {
                previewContainer.id = `classroom-image-preview-${index}`;
            }
            
            const previewImg = classroom.querySelector('[id^="classroom-image-preview-img-"]');
            if (previewImg) {
                previewImg.id = `classroom-image-preview-img-${index}`;
            }
            
            // 画像アップロードのonchangeイベントを更新
            const imageInput = classroom.querySelector('input[type="file"][name*="classroom_image"]');
            if (imageInput) {
                imageInput.setAttribute('onchange', `previewClassroomImage(this, ${index})`);
            }
            const clearPreviewButton = classroom.querySelector('button[onclick^="clearClassroomImagePreview("]');
            if (clearPreviewButton) {
                clearPreviewButton.setAttribute('onclick', `clearClassroomImagePreview(${index})`);
            }
            
            // 地図を再初期化
            if (typeof initializeClassroomMap === 'function') {
                initializeClassroomMap(index);
            }
        });
    }
    
    function updateRemoveButtons() {
        const classrooms = document.querySelectorAll('.classroom-form');
        classrooms.forEach((classroom, idx) => {
            const btn = classroom.querySelector('.remove-classroom');
            if (btn) {
                btn.style.display = (idx === 0) ? 'none' : 'inline-flex';
            }
        });
    }

    // --- 一時保存（Cookie）---
    var DRAFT_COOKIE_PREFIX = 'business_application_draft';
    var DRAFT_COOKIE_MAX_AGE = 2592000; // 30日（秒）
    var DRAFT_COOKIE_CHUNK_SIZE = 500; // 4KB制限（encodeURIComponentで約3倍になるため十分小さく）
    var DRAFT_EXPIRY_MS = 30 * 24 * 60 * 60 * 1000; // 30日（ミリ秒）
    var NOTICE_COOKIE_NAME = 'business_application_draft_notice_shown';

    function setCookie(name, value, maxAge) {
        var s = name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + (maxAge || DRAFT_COOKIE_MAX_AGE) + '; SameSite=Lax';
        document.cookie = s;
    }
    function getCookie(name) {
        var parts = document.cookie.split(/;\s*/);
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i];
            var eq = part.indexOf('=');
            if (eq === -1) continue;
            var key = part.slice(0, eq).trim();
            var val = part.slice(eq + 1).trim();
            if (key === name) {
                try {
                    return decodeURIComponent(val);
                } catch (e) {
                    return null;
                }
            }
        }
        return null;
    }
    function deleteCookie(name) {
        document.cookie = name + '=; path=/; max-age=0';
    }
    function collectDraftData() {
        var form = document.getElementById('business-application-form');
        if (!form) return {};
        var exclude = ['_token', 'g-recaptcha-response', 'MAX_FILE_SIZE'];
        var data = {};
        var els = form.querySelectorAll('input, select, textarea');
        for (var i = 0; i < els.length; i++) {
            var el = els[i];
            var name = el.getAttribute('name');
            if (!name) continue;
            if (name.indexOf('documents[') === 0 || name.indexOf('documents_') === 0) continue;
            if (exclude.indexOf(name) !== -1) continue;
            if (el.type === 'file') continue;
            if (el.type === 'radio') {
                if (el.checked) data[name] = el.value;
                continue;
            }
            if (el.type === 'checkbox') {
                data[name] = el.checked ? '1' : '0';
                continue;
            }
            data[name] = el.value || '';
        }
        data.savedAt = Date.now();
        return data;
    }
    function setDraftToCookie(data) {
        var json = JSON.stringify(data);
        clearDraftCookies();
        for (var offset = 0, idx = 1; offset < json.length; offset += DRAFT_COOKIE_CHUNK_SIZE, idx++) {
            var chunk = json.slice(offset, offset + DRAFT_COOKIE_CHUNK_SIZE);
            var name = idx === 1 ? DRAFT_COOKIE_PREFIX : DRAFT_COOKIE_PREFIX + '_' + idx;
            setCookie(name, chunk);
        }
    }
    function getDraftFromCookie() {
        var json = getCookie(DRAFT_COOKIE_PREFIX);
        if (json == null || json === '') return null;
        var idx = 2;
        while (true) {
            var next = getCookie(DRAFT_COOKIE_PREFIX + '_' + idx);
            if (next == null || next === '') break;
            json += next;
            idx++;
        }
        try {
            return JSON.parse(json);
        } catch (e) {
            var toTry = json;
            for (var close = 0; close <= 5; close++) {
                try {
                    return JSON.parse(toTry);
                } catch (e2) {}
                toTry += '}';
            }
            return null;
        }
    }
    function clearDraftCookies() {
        deleteCookie(DRAFT_COOKIE_PREFIX);
        for (var i = 1; i < 20; i++) {
            deleteCookie(DRAFT_COOKIE_PREFIX + '_' + i);
        }
    }
    function applyDraftToForm(data) {
        var form = document.getElementById('business-application-form');
        if (!form || !data) return;
        var classroomKeys = {};
        Object.keys(data).forEach(function(k) {
            var m = k.match(/^classrooms\[(\d+)\]/);
            if (m) classroomKeys[m[1]] = true;
        });
        var draftClassroomCount = Object.keys(classroomKeys).length;
        if (draftClassroomCount > 1 && addClassroomBtn && classroomsContainer) {
            for (var i = 1; i < draftClassroomCount; i++) {
                var newEl = createClassroomForm(classroomCount);
                classroomsContainer.appendChild(newEl);
                classroomCount++;
                var newIndex = classroomCount - 1;
                if (typeof setupUseMapCheckbox === 'function') setupUseMapCheckbox(newIndex);
                if (typeof initializeClassroomMap === 'function') initializeClassroomMap(newIndex);
            }
            updateRemoveButtons();
            if (classroomCount >= maxClassrooms) addClassroomBtn.style.display = 'none';
        }
        var excludeNames = ['documents[', 'documents_', '_token', 'g-recaptcha-response', 'MAX_FILE_SIZE'];
        var els = form.querySelectorAll('input:not([type="file"]), select, textarea');
        for (var i = 0; i < els.length; i++) {
            var e = els[i];
            var name = e.getAttribute('name');
            if (!name || name === 'savedAt') continue;
            var skip = false;
            for (var x = 0; x < excludeNames.length; x++) {
                if (name.indexOf(excludeNames[x]) === 0) { skip = true; break; }
            }
            if (skip) continue;
            var value = data[name];
            if (value === undefined) continue;
            value = (value == null) ? '' : String(value);
            if (e.type === 'radio') {
                e.checked = (e.value === value);
            } else if (e.type === 'checkbox') {
                e.checked = (value === '1' || value === 'true' || value === 1);
            } else {
                e.value = value;
            }
        }
        if (data.bank_code || data.branch_code) {
            setTimeout(function() {
                if (typeof window.$ === 'undefined' || !window.$.fn || !window.$.fn.select2) return;
                var bankSelect = document.getElementById('bank_select_bank_code');
                var branchSelect = document.getElementById('branch_select_branch_code');
                if (!bankSelect) return;
                if (data.bank_code) {
                    try { window.$(bankSelect).val(data.bank_code).trigger('change'); } catch (err) {}
                }
                if (branchSelect && data.branch_code) {
                    var branchCode = data.branch_code;
                    var attempts = 0;
                    var maxAttempts = 25;
                    function trySetBranch() {
                        var opt = branchSelect.querySelector('option[value="' + branchCode + '"]');
                        if (opt) {
                            try { window.$(branchSelect).val(branchCode).trigger('change'); } catch (err) {}
                            return;
                        }
                        attempts++;
                        if (attempts < maxAttempts) {
                            setTimeout(trySetBranch, 200);
                        }
                    }
                    setTimeout(trySetBranch, 400);
                }
            }, 600);
        }
    }
    function restoreDraft() {
        var formEl = document.getElementById('business-application-form');
        if (!formEl) return;
        var hasOldInput = formEl.getAttribute('data-has-old-input') === '1';
        if (hasOldInput) return;
        var data = getDraftFromCookie();
        if (!data || typeof data !== 'object') return;
        if (data.savedAt && (Date.now() - data.savedAt > DRAFT_EXPIRY_MS)) {
            clearDraftCookies();
            return;
        }
        applyDraftToForm(data);
        var applicantRadio = formEl.querySelector('input[name="applicant_type"]:checked');
        if (applicantRadio) {
            applicantRadio.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (data.antisocial_forces_pledged === '1' || data.antisocial_forces_pledged === 1) {
            var antisocialCheckbox = document.getElementById('antisocial_forces_pledged');
            var antisocialLabel = document.getElementById('antisocial-pledge-label');
            var antisocialLabelText = document.getElementById('antisocial-pledge-text');
            if (antisocialCheckbox && antisocialLabel && antisocialLabelText) {
                antisocialCheckbox.disabled = false;
                antisocialLabel.classList.remove('cursor-not-allowed', 'text-gray-400');
                antisocialLabel.classList.add('cursor-pointer', 'text-gray-900');
            }
        }
        if (data.privacy_policy_agreed === '1' || data.privacy_policy_agreed === 1) {
            var privacyCheckbox = document.getElementById('privacy_policy_agreed');
            if (privacyCheckbox) {
                privacyCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        var toast = document.getElementById('draft-saved-toast');
        if (toast) {
            toast.textContent = '一時保存した内容を復元しました。';
            toast.classList.remove('hidden');
            setTimeout(function() { toast.classList.add('hidden'); }, 4000);
        }
    }
    function saveDraft() {
        var data = collectDraftData();
        if (!data.savedAt) return;
        setDraftToCookie(data);
        var toast = document.getElementById('draft-saved-toast');
        if (toast) {
            toast.textContent = '一時保存しました。';
            toast.classList.remove('hidden');
            setTimeout(function() { toast.classList.add('hidden'); }, 3000);
        }
        var noticeShown = getCookie(NOTICE_COOKIE_NAME);
        if (!noticeShown) {
            setCookie(NOTICE_COOKIE_NAME, '1', DRAFT_COOKIE_MAX_AGE);
            var notice = document.getElementById('draft-privacy-notice');
            if (notice) {
                notice.classList.remove('hidden');
                setTimeout(function() { notice.classList.add('hidden'); }, 8000);
            }
        }
    }
    var btnDraftSave = document.getElementById('btn-draft-save');
    if (btnDraftSave) btnDraftSave.addEventListener('click', saveDraft);
    setTimeout(restoreDraft, 0);
});
</script>
@endif

@if($mode === 'confirm')
<script>
function handleSubmit(form) {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn.disabled) {
        return false;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>申請中...';
    
    return true;
}
</script>
@endif

<!-- 郵便番号用 -->
<script>
	// .search-postal-codeをクリックしたときの処理
	document.addEventListener('DOMContentLoaded', function() {
		const searchBtn = document.querySelector('.search-postal-code');
		if (searchBtn) {
			searchBtn.addEventListener('click', function() {
				// name属性がpostal_codeのinput要素を取得
				const postalCode = document.querySelector('input[name="postal_code"]');
				if (postalCode.value.trim() === '') {
					alert('郵便番号を入力してください。');
					return;
				}

				// 検索ボタンを無効化
				searchBtn.disabled = true;
				searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>検索中...';

				// APIを呼び出し
				const formToken = '{{ $formToken ?? session("form_access_token") }}';
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
					searchBtn.innerHTML = '<i class="fas fa-search mr-2"></i>検索';
				});
			});
		}
	});
</script>

{{-- セッション延命（15分ごと）と CSRF（meta / hidden）の同期 --}}
<script>
(function () {
	var keepAliveUrl = @json(route('session.keep_alive'));
	var intervalMs = 15 * 60 * 1000;

	function syncCsrfToken(token) {
		if (!token) {
			return;
		}
		var meta = document.querySelector('meta[name="csrf-token"]');
		if (meta) {
			meta.setAttribute('content', token);
		}
		document.querySelectorAll('input[name="_token"]').forEach(function (el) {
			el.value = token;
		});
	}

	function ping() {
		fetch(keepAliveUrl, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				return response.ok ? response.json() : Promise.reject(new Error('keep-alive failed'));
			})
			.then(function (data) {
				if (data && data.token) {
					syncCsrfToken(data.token);
				}
			})
			.catch(function () {
				/* セッション切れ等は送信時に 419 等で検知 */
			});
	}

	var timerId = setInterval(ping, intervalMs);
	window.addEventListener('beforeunload', function () {
		clearInterval(timerId);
	});
})();
</script>

@endsection