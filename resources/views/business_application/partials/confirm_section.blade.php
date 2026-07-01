{{-- 確認画面セクション（同一ページ内表示用）。$data, $categories を渡す。 --}}
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

<div id="confirm-section-errors" class="hidden alert-base alert-error alert-m" aria-hidden="true"></div>

<form method="POST" action="{{ route('business_application.store') }}" id="applicationForm" class="space-y-8">
    @csrf
    <!-- 事業者情報 -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-6 border-b border-gray-200 pb-3">
            <i class="fas fa-building mr-2 text-blue-600"></i>事業者情報
        </h2>
        <div class="grid gap-6">
            <div class="form-row cols-2">
                <label class="field-label required label-l">申請者種別</label>
                <div class="flex space-x-6 mt-2 w-full">
                    <p class="mt-1 text-gray-900">
                        @if(($data['applicant_type'] ?? '') == 'corporation')
                            法人
                        @elseif(($data['applicant_type'] ?? '') == 'voluntary_group')
                            任意団体
                        @elseif(($data['applicant_type'] ?? '') == 'individual')
                            個人事業主
                        @endif
                    </p>
                    <input type="hidden" name="applicant_type" value="{{ $data['applicant_type'] ?? '' }}">
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l" style="float: left;">暴力団排除条項</label>
                <div class="mt-2 w-full">
                    <p class="mt-1 text-gray-900">
                        @if(!empty($data['antisocial_forces_pledged']))
                            <i class="fas fa-check-circle text-green-600 mr-1"></i>誓約済
                        @else
                            未誓約
                        @endif
                    </p>
                    <input type="hidden" name="antisocial_forces_pledged" value="{{ !empty($data['antisocial_forces_pledged']) ? '1' : '0' }}">
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">事業者名</label>
                <div class="flex space-x-6 mt-2 w-full">
                    <input type="text" name="business_name" value="{{ $data['business_name'] ?? '' }}" class="field-base field-w-100" readonly>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">事業者名（カナ）</label>
                <input type="text" name="business_name_kana" value="{{ $data['business_name_kana'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">代表者役職名</label>
                <input type="text" name="representative_title" value="{{ $data['representative_title'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">代表者役職名（カナ）</label>
                <input type="text" name="representative_title_kana" value="{{ $data['representative_title_kana'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
			<div class="md:col-span-2 mt-6 space-y-4">
				<div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-start">
					<label class="field-label required label-l">代表者名</label>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
						<input type="text" name="representative_family_name" value="{{ $data['representative_family_name'] ?? '' }}" class="field-base field-w-100" readonly>
						<input type="text" name="representative_given_name" value="{{ $data['representative_given_name'] ?? '' }}" class="field-base field-w-100" readonly>
					</div>
				</div>
            </div>
            <div class="md:col-span-2 mt-6 space-y-4">
				<div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
                <label class="field-label required label-l">代表者名（カナ）</label>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
						<input type="text" name="representative_family_name_kana" value="{{ $data['representative_family_name_kana'] ?? '' }}" class="field-base field-w-100" readonly>
						<input type="text" name="representative_given_name_kana" value="{{ $data['representative_given_name_kana'] ?? '' }}" class="field-base field-w-100" readonly>
					</div>
				</div>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">郵便番号</label>
                <input type="text" name="postal_code" value="{{ $data['postal_code'] ?? '' }}" class="field-base field-w-80" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">都道府県</label>
                <input type="text" name="prefecture" value="{{ $data['prefecture'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">市区町村</label>
                <input type="text" name="city" value="{{ $data['city'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">それ以降の住所</label>
                <input type="text" name="address1" value="{{ $data['address1'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label label-l">建物名・部屋番号</label>
                <input type="text" name="building_name" value="{{ $data['building_name'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">電話番号</label>
                <input type="text" name="phone" value="{{ $data['phone'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label label-l">FAX番号</label>
                <input type="text" name="fax" value="{{ $data['fax'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">メールアドレス</label>
                <input type="email" name="email" value="{{ $data['email'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label label-l">ウェブサイトURL</label>
                <input type="url" name="website_url" value="{{ $data['website_url'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <!-- 必須書類アップロード（確認表示） -->
            @php
                $confirmApplicantType = $data['applicant_type'] ?? '';
                $confirmDocumentLabels = \App\Models\BusinessInfo::getDocumentLabelsForApplicantType($confirmApplicantType);
                $confirmUploadedDocuments = $data['uploaded_documents'] ?? [];
            @endphp
            <div class="md:col-span-2 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                    <i class="fas fa-upload mr-2 text-blue-600"></i>必須書類アップロード
                </h3>
                <div class="space-y-6">
                    @foreach($confirmDocumentLabels as $docKey => $docInfo)
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="field-label required label-l">{{ $docInfo['label'] }}</label>
                            @foreach($docInfo['notice'] ?? [] as $noticeLine)
                                <p class="text-sm text-amber-700 mt-1">{{ $noticeLine }}</p>
                            @endforeach
                            @if(in_array($docKey, ['corporation_citizen_tax_certificate', 'representative_citizen_tax_certificate', 'corporate_tax_certificate_no2', 'citizen_tax_certificate']))
                                <p class="text-sm mt-1 mb-2">
                                    <a href="{{ asset('subdomain_assets/www/files/申立書.txt') }}" download class="text-blue-600 hover:underline" target="_blank" rel="noopener noreferrer">
                                        <i class="fas fa-file-download mr-1"></i>申立書（Word形式）をダウンロード
                                    </a>
                                </p>
                            @endif
                            @if(!empty($confirmUploadedDocuments[$docKey]))
                                <p class="mt-1 text-green-600">
                                    <i class="fas fa-check-circle mr-1"></i>添付済み: {{ $confirmUploadedDocuments[$docKey]['filename'] ?? 'ファイル' }}
                                </p>
                                <input type="hidden" name="documents_uploaded[{{ $docKey }}]" value="1">
                                <input type="hidden" name="documents_filename[{{ $docKey }}]" value="{{ $confirmUploadedDocuments[$docKey]['filename'] ?? '' }}">
                            @else
                                <p class="mt-1 text-red-500"><i class="fas fa-exclamation-triangle mr-1"></i>未添付</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">連絡先担当者名</label>
                <input type="text" name="contact_person" value="{{ $data['contact_person'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">連絡先担当者電話番号</label>
                <input type="text" name="contact_phone" value="{{ $data['contact_phone'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">文書等送付先：宛名</label>
                <input type="text" name="document_person" value="{{ $data['document_person'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">文書等送付先：住所</label>
                <input type="text" name="document_address" value="{{ $data['document_address'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">営業時間</label>
                <input type="text" name="business_hours" value="{{ $data['business_hours'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">定休日</label>
                <input type="text" name="holiday" value="{{ $data['holiday'] ?? '' }}" class="field-base field-w-100" readonly>
            </div>
        </div>
        <!-- 振込先情報 -->
        <h3 class="text-lg font-semibold text-gray-900 mt-8 mb-4 border-b border-gray-200 pb-2">
            <i class="fas fa-university mr-2 text-green-600"></i>振込先情報
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-form.bank-branch-select
                :bank-name="'bank_code'"
                :branch-name="'branch_code'"
                :bank-value="$data['bank_code'] ?? ''"
                :branch-value="$data['branch_code'] ?? ''"
                :bank-required="true"
                :branch-required="true"
                :bank-label="'銀行名'"
                :branch-label="'支店名'"
                :mode="'confirm'"
                :data="$data"
            />
            <div class="md:col-span-2">
                <label class="field-label required label-l">口座種別</label>
                <p class="mt-1 text-gray-900">{{ $data['account_type'] ?? '' }}</p>
                <input type="hidden" name="account_type" value="{{ $data['account_type'] ?? '' }}">
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">口座番号</label>
                <p class="mt-1 text-gray-900">{{ $data['account_number'] ?? '' }}</p>
                <input type="hidden" name="account_number" value="{{ $data['account_number'] ?? '' }}">
            </div>
            <div class="md:col-span-2">
                <label class="field-label required label-l">口座名義</label>
                <p class="mt-1 text-gray-900">{{ $data['account_holder'] ?? '' }}</p>
                <input type="hidden" name="account_holder" value="{{ $data['account_holder'] ?? '' }}">
            </div>
        </div>
    </div>
    <!-- 教室情報 -->
    @if(isset($data['classrooms']) && count($data['classrooms']) > 0)
        @foreach($data['classrooms'] as $index => $classroom)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 border-b border-gray-200 pb-3">
                    <i class="fas fa-school mr-2 text-purple-600"></i>教室情報 {{ $index + 1 }}
                </h2>
                @include('business_application.classroom_form', [
                    'classroom' => $classroom,
                    'index' => $index,
                    'isReadonly' => true,
                    'latitude' => $classroom['classroom_latitude'] ?? null,
                    'longitude' => $classroom['classroom_longitude'] ?? null
                ])
            </div>
        @endforeach
    @endif
    <!-- 登録教室募集要項及びプライバシーポリシー同意 -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="md:col-span-2">
            <label class="field-label required label-l">登録教室募集要項及びプライバシーポリシー</label>
            <p class="mt-1 text-gray-900">
                @if(!empty($data['privacy_policy_agreed']))
                    <i class="fas fa-check-circle text-green-600 mr-1"></i>同意済
                @else
                    未同意
                @endif
            </p>
            <input type="hidden" name="privacy_policy_agreed" value="{{ !empty($data['privacy_policy_agreed']) ? '1' : '0' }}">
        </div>
    </div>
    <!-- 送信ボタン -->
    <div class="flex flex-col items-stretch gap-2 mb-20 sm:mb-8">
        <div class="flex justify-between gap-4">
            <button type="button" id="btn-back-to-form" class="btn-base btn-back btn-m">
                <i class="fas fa-arrow-left mr-2"></i>戻る
            </button>
            <button type="submit" class="btn-base btn-create btn-m" id="submitBtn">
                <i class="fas fa-paper-plane mr-2"></i>申請する
            </button>
        </div>
    </div>
</form>
