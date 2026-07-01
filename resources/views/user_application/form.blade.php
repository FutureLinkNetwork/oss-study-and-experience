@extends('layouts.user')

@section('title', $mode === 'confirm' ? '利用者申請 - 確認' : '利用者申請')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">
            @if($mode === 'confirm')
                利用者申請フォーム - 確認
            @else
                利用者申請フォーム
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

        <form method="POST"
              action="{{ $mode === 'confirm' ? route('user_application.store') : route('user_application.confirm') }}"
              class="space-y-8"
              enctype="multipart/form-data"
              @if($mode === 'confirm') id="applicationForm" onsubmit="return handleSubmit(this)" @else id="user-application-form" @endif>
            @csrf
            @if($mode !== 'confirm' && config('recaptcha.enabled'))
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
            @endif
            <!-- PHP最大アップロードサイズを設定 -->
            <input type="hidden" name="MAX_FILE_SIZE" value="{{ 10 * 1024 * 1024 }}">
            
            <!-- 入力項目 -->
            <div class="bg-white rounded-lg shadow-md p-6">                
                <div class="grid gap-6">
                    <!-- 就学援助認定番号 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">就学援助認定番号（下の図の<span style="color: red;">赤枠部分</span>に記載の番号）</label>
                        <input type="text" name="certification_number" value="{{ old('certification_number', $data['certification_number'] ?? '') }}" 
                               class="field-base field-w-100" maxlength="100" required @if($mode === 'confirm') readonly @endif
							   placeholder="（例） 123-45678">
						<span class="notice2">※他市の就学援助を受けている等で本市の就学援助を受けていない場合は”０”で入力してください</span>
                    </div>

                    <!-- 就学援助認定者名（保護者名） -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">就学援助認定者名（保護者名）（上の図の<span style="color: blue;">青枠部分</span>に記載の方の氏名）</label>
                        <div class="flex gap-4">
                            <div class="flex-1">
								<span class="text-sm text-gray-500">姓</span>
								<input type="text" name="guardian_name_family" value="{{ old('guardian_name_family', $data['guardian_name_family'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif
                                       placeholder="（例）習事" aria-label="就学援助認定者名（姓）">
                            </div>
                            <div class="flex-1">
								<span class="text-sm text-gray-500">名</span>
								<input type="text" name="guardian_name_given" value="{{ old('guardian_name_given', $data['guardian_name_given'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif
                                       placeholder="（例）太郎" aria-label="就学援助認定者名（名）">
                            </div>
                        </div>
						<div class="notice2">※申請者は、就学援助受給している場合は就学援助受給者。就学援助を受給していない場合は世帯主です。</div>
                    </div>

                    <!-- 就学援助認定者名カナ（保護者名） -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">就学援助認定者名カナ（保護者名）</label>
                        <div class="flex gap-4">
                            <div class="flex-1">
								<span class="text-sm text-gray-500">姓</span>
								<input type="text" name="guardian_name_kana_family" value="{{ old('guardian_name_kana_family', $data['guardian_name_kana_family'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif
                                       placeholder="（例）ナライゴト" aria-label="就学援助認定者名カナ（姓）">
                            </div>
                            <div class="flex-1">
								<span class="text-sm text-gray-500">名</span>
                                <input type="text" name="guardian_name_kana_given" value="{{ old('guardian_name_kana_given', $data['guardian_name_kana_given'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif
                                       placeholder="（例）タロウ" aria-label="就学援助認定者名カナ（名）">
                            </div>
                        </div>
						<div class="notice2">※カタカナで入力してください</div>
                    </div>

                    <!-- 就学援助認定者生年月日 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">就学援助認定者生年月日（保護者生年月日）</label>
                        <input type="date" name="guardian_birth_date" value="{{ old('guardian_birth_date', $data['guardian_birth_date'] ?? '') }}" 
                               class="field-base field-w-100" required @if($mode === 'confirm') readonly @endif>
                    </div>

                    <!-- 住所 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">住所</label>
                        <textarea name="guardian_address" rows="3" 
                                  class="field-base field-w-100" maxlength="500" required @if($mode === 'confirm') readonly @endif placeholder="（例） 千葉県船橋市">{{ old('guardian_address', $data['guardian_address'] ?? '') }}</textarea>
						<div class="notice2">※住民登録をしている住所地を記載してください。</div>
						<div class="notice2">※アパートの方は号室まで記入してください</div>
                    </div>

                    <!-- 電話番号 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">電話番号</label>
                        <input type="text" name="guardian_phone" value="{{ old('guardian_phone', $data['guardian_phone'] ?? '') }}" 
                               class="field-base field-w-100" maxlength="20" required @if($mode === 'confirm') readonly @endif
							   placeholder="（例）072-784-8106">
						<div class="notice2">※日中連絡のつく電話番号</div>
						<div class="notice2">※半角数字、ハイフン「-」ありで入力してください</div>
                    </div>

                    <!-- メールアドレス -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">メールアドレス</label>
                        <input type="email" name="guardian_email" value="{{ old('guardian_email', $data['guardian_email'] ?? '') }}" 
                               class="field-base field-w-100" maxlength="255" required @if($mode === 'confirm') readonly @endif
							   placeholder="（例） example@example.com">
                        <div class="notice2">※登録するメールアドレスの種類に制限はありませんが、 通知メールを受信できるように、ドメイン(@study-and-experience.jp)を迷惑メールフィルターの対象外とするなどの対応をお願いいたします</div>
                    </div>

                    <!-- 対象児童名 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">対象児童名</label>
                        <div class="flex gap-4">
                            <div class="flex-1">
								<span class="text-sm text-gray-500">姓</span>
								<input type="text" name="child_name_family" value="{{ old('child_name_family', $data['child_name_family'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif
                                       placeholder="（例）習事" aria-label="対象児童名（姓）">
                            </div>
                            <div class="flex-1">
								<span class="text-sm text-gray-500">名</span>
								<input type="text" name="child_name_given" value="{{ old('child_name_given', $data['child_name_given'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif
                                       placeholder="（例）次郎" aria-label="対象児童名（名）">
                            </div>
                        </div>
                    </div>

                    <!-- 対象児童名カナ -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">対象児童名カナ</label>
                        <div class="flex gap-4">
                            <div class="flex-1">
								<span class="text-sm text-gray-500">姓</span>
								<input type="text" name="child_name_kana_family" value="{{ old('child_name_kana_family', $data['child_name_kana_family'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif
                                       placeholder="（例）ナライゴト" aria-label="対象児童名カナ（姓）">
                            </div>
                            <div class="flex-1">
								<span class="text-sm text-gray-500">名</span>
								<input type="text" name="child_name_kana_given" value="{{ old('child_name_kana_given', $data['child_name_kana_given'] ?? '') }}"
                                       class="field-base field-w-100" maxlength="50" required @if($mode === 'confirm') readonly @endif
                                       placeholder="（例）ジロウ" aria-label="対象児童名カナ（名）">
                            </div>
                        </div>
						<div class="notice2">※カタカナで入力してください</div>
                    </div>

                    <!-- 対象児童生年月日 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">対象児童生年月日</label>
                        <input type="date" name="child_birth_date" value="{{ old('child_birth_date', $data['child_birth_date'] ?? '') }}" 
                               class="field-base field-w-100" required @if($mode === 'confirm') readonly @endif>
                    </div>

                    <!-- 小学校名 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">学校名<span style="color: red;">（来年度入学予定の方は、就学される学校名をご記入ください。）</span></label>
                        <input type="text" name="elementary_school_name" value="{{ old('elementary_school_name', $data['elementary_school_name'] ?? '') }}" 
                               class="field-base field-w-100" maxlength="100" required @if($mode === 'confirm') readonly @endif
							   placeholder="（例） 習事市立習事中央小学校">
                    </div>

                    <!-- 学年 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">学年<span style="color: red;">（入学前のお子さんは「入学予定」を選択してください。）</span></label>
                        @if($mode === 'confirm')
                            <input type="text" name="grade" value="{{ old('grade', $data['grade'] ?? '') }}"
                                   class="field-base field-w-100" readonly>
                        @else
                            @php
                                $gradeOptions = $subdomain->getGrades();
                                $selectedGrade = old('grade', $data['grade'] ?? '');
                            @endphp
                            <select name="grade" class="field-base field-w-100" required>
                                <option value="">選択してください</option>
                                @foreach($gradeOptions as $option)
                                    <option value="{{ $option }}" {{ (string)$option === (string)$selectedGrade ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        @endif
						<div class="notice2">※６年生の方については、（「よくある質問|対象者（保護者）」「Q クーポンの利用申請に期限はありますか」をご覧ください。</div>
                    </div>

                    <!-- 対象児童の住所 -->
                    <div class="md:col-span-2">
                        <label class="field-label required label-l">対象児童の住所
                        @if($mode !== 'confirm')
                                    <input type="checkbox" 
                                           name="child_address_same_as_guardian" 
                                           value="1" 
                                           id="child_address_same_as_guardian"
                                           class="ml-6" 
                                           {{ old('child_address_same_as_guardian', $data['child_address_same_as_guardian'] ?? false) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700">申請者と同一の住所</span>
                        @else
                            @if(!empty($data['child_address_same_as_guardian']))
                                <i class="fas fa-check-circle text-green-600 mr-1 ml-6"></i>申請者と同一の住所
                                <input type="hidden" name="child_address_same_as_guardian" value="1">
                            @endif
                        @endif
						</label>
                        <textarea name="child_address" 
                                  id="child_address" 
                                  rows="3" 
                                  class="field-base field-w-100" 
                                  maxlength="500" 
                                  required 
                                  @if($mode === 'confirm') readonly @endif
								  placeholder="（例） 習事市">{{ old('child_address', $data['child_address'] ?? '') }}</textarea>
						<div class="notice2">※住民登録をしている住所地を記載してください。</div>
						<div class="notice2">※アパートの方は号室まで記入してください</div>
                    </div>

                    <!-- 調査同意チェック -->
                    <div class="md:col-span-2">
                        <label class="flex items-center">
						<span class="field-label required label-l">同意事項の確認
                            <div class="notice2" style="margin-top: 10px; margin-bottom: 10px;">※同意事項を必ずお読みいただき、すべてに同意の上、 チェックをしてください。</div>
                            <input type="checkbox" name="survey_consent" value="1" 
                                   class="mr-2" {{ old('survey_consent', $data['survey_consent'] ?? false) ? 'checked' : '' }} 
                                   required @if($mode === 'confirm') disabled @endif>私は、子どもの習い事応援事業における助成を申請するにあたり、次に掲げる全ての事項について、同意します</span>
                        </label>
                        @if($mode === 'confirm')
                            <input type="hidden" name="survey_consent" value="1">
                        @endif
						<style>
dt{
    margin:10px;
	font-size: 0.875rem;
}
dd{
    margin-left:30px;
	font-size: 0.875rem;
}
dd li{
    margin:10px;
}							
						</style>
						<dt>タイトル</dt>
<dd>本文テキスト</dd>
                    </div>
                </div>
            </div>

            <!-- 任意項目：現在習い事をしている教室等の情報 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 border-b border-gray-200 pb-3">
                    <i class="fas fa-school mr-2 text-green-600"></i>現在習い事をしている教室等の情報（任意）
                </h2>
                <div class="notice2">※記載いただいた教室等の情報は、 運営事務局より各教室への登録案内の事務に活用させていただきます。</div><br>
                <div class="space-y-6">
                    @for($i = 1; $i <= 3; $i++)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-700 mb-4">教室情報{{ $i }}</h3>
                            <div class="grid gap-4">
                                <!-- 教室名 -->
                                <div>
                                    <label class="field-label label-l">教室名</label>
                                    <input type="text" name="classroom_name_{{ $i }}" value="{{ old("classroom_name_{$i}", $data["classroom_name_{$i}"] ?? '') }}" 
                                           class="field-base field-w-100" maxlength="100" @if($mode === 'confirm') readonly @endif>
                                </div>

                                <!-- 所在地 -->
                                <div>
                                    <label class="field-label label-l">所在地</label>
                                    <input type="text" name="classroom_location_{{ $i }}" value="{{ old("classroom_location_{$i}", $data["classroom_location_{$i}"] ?? '') }}" 
                                           class="field-base field-w-100" maxlength="200" @if($mode === 'confirm') readonly @endif>
                                </div>

                                <!-- 電話番号 -->
                                <div>
                                    <label class="field-label label-l">電話番号</label>
                                    <input type="text" name="classroom_phone_{{ $i }}" value="{{ old("classroom_phone_{$i}", $data["classroom_phone_{$i}"] ?? '') }}" 
                                           class="field-base field-w-100" maxlength="20" @if($mode === 'confirm') readonly @endif>
                                </div>

                                <!-- 担当者 -->
                                <div>
                                    <label class="field-label label-l">担当者</label>
                                    <input type="text" name="classroom_contact_person_{{ $i }}" value="{{ old("classroom_contact_person_{$i}", $data["classroom_contact_person_{$i}"] ?? '') }}" 
                                           class="field-base field-w-100" maxlength="100" @if($mode === 'confirm') readonly @endif>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- 添付ファイル -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 border-b border-gray-200 pb-3">
                    <i class="fas fa-file-upload mr-2 text-purple-600"></i>添付ファイル
                </h2><span style="color: red;">※　本市就学援助受給者については、原則書類の提出は不要です。 </span>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <label class="field-label label-l">課税証明書等<br>
                    @if($mode !== 'confirm')
                                <input type="checkbox" 
                                       name="child_registered_in_municipality_and_receiving_scholarship" 
                                       value="1" 
                                       id="child_registered_in_municipality_and_receiving_scholarship"
                                       class="ml-6" 
                                       {{ old('child_registered_in_municipality_and_receiving_scholarship', $data['child_registered_in_municipality_and_receiving_scholarship'] ?? false) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">児童が習事市に住所登録があり、就学援助を受給している場合はチェックしてください。</span>

								@else
                        @if(!empty($data['child_registered_in_municipality_and_receiving_scholarship']))
                            <i class="fas fa-check-circle text-green-600 mr-1"></i>児童が自治体に住所登録があり、就学援助を受給している場合チェックしてください
                            <input type="hidden" name="child_registered_in_municipality_and_receiving_scholarship" value="1">
                        @endif
                    @endif
					</label>
                    <p class="text-sm text-gray-600 mt-1 mb-3">
                        課税証明書等の添付ファイルをアップロードしてください。（任意）
                    </p>
                    @if($mode === 'confirm')
                        @if(!empty($data['tax_document']))
                            <p class="mt-1 text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>
                                添付済み: {{ $data['tax_document_filename'] ?? 'ファイル' }}
                            </p>
                            <input type="hidden" name="document_uploaded" value="1">
                            <input type="hidden" name="tax_document_filename" value="{{ $data['tax_document_filename'] ?? '' }}">
                        @else
                            <p class="mt-1 text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                未添付（任意項目）
                            </p>
                        @endif
                    @else
                        <input type="file" 
                               name="tax_document" 
                               id="tax_document"
                               class="field-base field-w-100 @error('tax_document') error @enderror"
                               accept=".jpeg,.jpg,.png,.pdf">
                        <p class="text-xs text-gray-500 mt-1">
                            対応形式: JPEG, PNG, PDF (最大10MB)
                        </p>
                        @error('tax_document')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    @endif
                </div>
            </div>

            <!-- プライバシーポリシー同意 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="md:col-span-2">
                    <label class="field-label required label-l">プライバシーポリシー</label>
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
                            <span><a href="#" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">プライバシーポリシー</a>に同意する</span>
                        </label>
                        @error('privacy_policy_agreed')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    @endif
                </div>
            </div>

            <!-- ボタン -->
                @if($mode === 'confirm')
                <div class="flex justify-center items-center pt-6">
                    <a href="{{ route('user_application.create') }}" class="btn-base btn-back btn-l">
                        <i class="fas fa-arrow-left mr-2"></i>戻る
                    </a>
                    <button type="submit" id="submitBtn" class="btn-base btn-create btn-l">
                        <i class="fas fa-paper-plane mr-2"></i>申請する
                    </button>
               </div>
                @else
            <div class="flex justify-center items-center pt-6">
                <div class="flex">
                    <div>
                        <p class="mt-1">
                            入力が完了しましたら「確認画面に進む」ボタンを押してください。
                        </p>
                    </div>
                </div>
            </div>
               <div class="flex flex-col items-center pt-6 gap-2">
                    @error('g-recaptcha-response')
                        <div class="text-red-600 text-sm">{{ $message }}</div>
                    @enderror
                    <div class="flex justify-center items-center gap-4">
                        <a href="{{ route('welcome') }}" class="btn-base btn-cancel btn-m">
                            <i class="fas fa-arrow-left mr-2"></i>キャンセル
                        </a>
                        <button type="submit" class="btn-base btn-create btn-m" id="confirmStepBtn" disabled title="プライバシーポリシーに同意すると押せます">
                            <i class="fas fa-check mr-2"></i>確認画面へ
                        </button>
                    </div>
                </div>
                @endif
        </form>
    </div>
</div>

@if($mode !== 'confirm' && config('recaptcha.enabled'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}"></script>
    <script>
        (function() {
            var form = document.getElementById('user-application-form');
            var siteKey = '{{ config('recaptcha.site_key') }}';
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, { action: 'user_application' }).then(function(token) {
                        document.getElementById('g-recaptcha-response').value = token;
                        form.submit();
                    });
                });
            });
        })();
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
@else
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

    const checkbox = document.getElementById('child_address_same_as_guardian');
    const childAddressTextarea = document.getElementById('child_address');
    const guardianAddressTextarea = document.querySelector('textarea[name="guardian_address"]');
    
    if (checkbox && childAddressTextarea && guardianAddressTextarea) {
        function toggleChildAddress() {
            if (checkbox.checked) {
                childAddressTextarea.disabled = true;
                childAddressTextarea.required = false;
                childAddressTextarea.value = guardianAddressTextarea.value;
            } else {
                childAddressTextarea.disabled = false;
                childAddressTextarea.required = true;
            }
        }
        
        // 初期状態を設定
        toggleChildAddress();
        
        // チェックボックスの変更を監視
        checkbox.addEventListener('change', toggleChildAddress);
        
        // 申請者住所の変更を監視（チェック時のみ）
        guardianAddressTextarea.addEventListener('input', function() {
            if (checkbox.checked) {
                childAddressTextarea.value = guardianAddressTextarea.value;
            }
        });
    }

    // 児童が自治体に住所登録があり、就学援助を受給している場合のチェックボックス
    const scholarshipCheckbox = document.getElementById('child_registered_in_municipality_and_receiving_scholarship');
    const taxDocumentInput = document.getElementById('tax_document');
    
    if (scholarshipCheckbox && taxDocumentInput) {
        function toggleTaxDocument() {
            if (scholarshipCheckbox.checked) {
                taxDocumentInput.disabled = true;
                taxDocumentInput.required = false;
                taxDocumentInput.value = '';
            } else {
                taxDocumentInput.disabled = false;
                taxDocumentInput.required = false;
            }
        }
        
        // 初期状態を設定
        toggleTaxDocument();
        
        // チェックボックスの変更を監視
        scholarshipCheckbox.addEventListener('change', toggleTaxDocument);
    }

    // 課税証明書等：添付時に10MBチェック
    (function() {
        var input = document.getElementById('tax_document');
        if (!input) return;
        var MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
        input.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            var file = this.files[0];
            if (file.size > MAX_FILE_SIZE) {
                alert('添付ファイルは10MB以下のファイルをアップロードしてください。');
                this.value = '';
            }
        });
    })();
});
</script>
@endif

@endsection

