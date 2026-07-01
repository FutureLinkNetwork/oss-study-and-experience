@php
    $isReadonly = $isReadonly ?? false;
    $classroom = $classroom ?? [];
    $useMap = filter_var(old('classrooms.' . $index . '.use_map', $classroom['use_map'] ?? true), FILTER_VALIDATE_BOOLEAN);
@endphp
<div>
    <!-- 教室名 -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">教室名</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_name]" 
                   value="{{ old('classrooms.' . $index . '.classroom_name', $classroom['classroom_name'] ?? '') }}" 
                   class="field-base field-w-100" maxlength="200" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
    </div>

    <!-- 教室名フリガナ -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">教室名（カナ）</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_name_kana]" 
                   value="{{ old('classrooms.' . $index . '.classroom_name_kana', $classroom['classroom_name_kana'] ?? '') }}" 
                   class="field-base field-w-100" maxlength="200" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
    </div>
    <!-- 「事業者情報と同じ内容を入力」ボタン（このボックス内の項目に事業者情報がコピーされる） -->
    @if(!$isReadonly)
    <div class="mt-6 mb-6 bg-gray-100 p-4 rounded-lg">
        <div class="mb-4">
            <button type="button" id="copy-business-info-btn-{{ $index }}" class="btn-base btn-copy btn-s" onclick="copyBusinessInfoToClassroom({{ $index }})">
                <i class="fas fa-user mr-2"></i>事業者情報と同じ内容を入力
            </button>
        </div>
    @endif

    <!-- 教室代表者氏名 -->
    <div class="form-item mb-4">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">教室代表者氏名</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_representative_name]" 
                   value="{{ old('classrooms.' . $index . '.classroom_representative_name', $classroom['classroom_representative_name'] ?? '') }}" 
                   id="classroom_representative_name-{{ $index }}"
                   class="field-base field-w-100" maxlength="100" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label></label>
            <div class="notice2">※姓と名の間を1文字（全角）空けてご記入ください。</div>
        </div>
    </div>

    <!-- 教室代表者氏名フリガナ -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">教室代表者氏名（カナ）</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_representative_name_kana]" 
                   value="{{ old('classrooms.' . $index . '.classroom_representative_name_kana', $classroom['classroom_representative_name_kana'] ?? '') }}" 
                   id="classroom_representative_name_kana-{{ $index }}"
                   class="field-base field-w-100" maxlength="100" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label></label>
            <div class="notice2">※姓と名の間を1文字（全角）空けてカタカナでご記入ください。</div>
        </div>
    </div>

    <!-- 郵便番号 -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">郵便番号</label>
            <div class="flex gap-2">
                <input type="text" name="classrooms[{{ $index }}][classroom_postal_code]" 
                       id="classroom_postal_code-{{ $index }}"
                       value="{{ old('classrooms.' . $index . '.classroom_postal_code', $classroom['classroom_postal_code'] ?? '') }}" 
                       class="field-base field-w-80" placeholder="123-4567" {{ $isReadonly ? 'readonly' : '' }} required>
                @if(!$isReadonly)
                <button type="button" class="btn-base btn-search btn-m w-100  search-classroom-postal-code" data-index="{{ $index }}">
                    <i class="fas fa-search mr-2"></i>検索
                </button>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label></label>
            <div class="notice2">※教室の郵便番号はxxx-xxxx形式で入力してください。</div>
        </div>
    </div>

    <!-- 都道府県 -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">都道府県</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_prefecture]" 
                   id="classroom_prefecture-{{ $index }}"
                   value="{{ old('classrooms.' . $index . '.classroom_prefecture', $classroom['classroom_prefecture'] ?? '') }}" 
                   class="field-base field-w-100" maxlength="10" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
    </div>

    <!-- 市区町村 -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">市区町村</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_city]" 
                   id="classroom_city-{{ $index }}"
                   value="{{ old('classrooms.' . $index . '.classroom_city', $classroom['classroom_city'] ?? '') }}" 
                   class="field-base field-w-100" maxlength="50" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
    </div>

    <!-- 住所１ -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">それ以降の住所</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_address1]" 
                   id="classroom_address1-{{ $index }}"
                   value="{{ old('classrooms.' . $index . '.classroom_address1', $classroom['classroom_address1'] ?? '') }}" 
                   class="field-base field-w-100" maxlength="100" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
    </div>

    <!-- 建物名・部屋番号 -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l mb-0">建物名・部屋番号</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_building_name]"
                   id="classroom_building_name-{{ $index }}"
                   value="{{ old('classrooms.' . $index . '.classroom_building_name', $classroom['classroom_building_name'] ?? '') }}"
                   class="field-base field-w-100" maxlength="100" {{ $isReadonly ? 'readonly' : '' }}>
        </div>
    </div>
    @if(!$isReadonly)
    </div>
    <!-- /「事業者情報と同じ内容を入力」ボックス（ここまで） -->
    @endif

    <!-- 地図を利用しない -->
    @if(!$isReadonly)
    <div class="form-item mb-6">
        <label class="flex items-center">
            <input type="checkbox" id="use-map-checkbox-{{ $index }}" class="mr-2 use-map-checkbox" data-index="{{ $index }}" {{ !$useMap ? 'checked' : '' }} value="0">
            <span class="text-sm text-gray-700">地図を利用しない</span>
        </label>
        <input type="hidden" id="use-map-{{ $index }}" name="classrooms[{{ $index }}][use_map]" value="{{ $useMap ? '1' : '0' }}">
    </div>
    @endif

    <!-- 地図表示エリア -->
    @if($isReadonly)
        @if($useMap)
	<div class="md:col-span-2">
		<div class="">
        	<label class="field-label label-l required">地図</label><span class="text-sm text-gray-500">※地図をクリックしてください。</span>
                <div id="map-{{ $index }}" class="classroom-map mt-2" style="height: 400px; width: 100%; border: 1px solid #ccc;" data-index="{{ $index }}" data-readonly="true"></div>
        </div>
    </div>
	<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
		<input type="hidden" name="classrooms[{{ $index }}][use_map]" value="1">
		<input type="hidden" id="latitude-{{ $index }}" name="classrooms[{{ $index }}][classroom_latitude]" value="{{ old('classrooms.' . $index . '.classroom_latitude', $classroom['classroom_latitude'] ?? '') }}">
		<input type="hidden" id="longitude-{{ $index }}" name="classrooms[{{ $index }}][classroom_longitude]" value="{{ old('classrooms.' . $index . '.classroom_longitude', $classroom['classroom_longitude'] ?? '') }}">
	</div>
        @else
	<input type="hidden" name="classrooms[{{ $index }}][use_map]" value="0">
	<input type="hidden" name="classrooms[{{ $index }}][classroom_latitude]" value="">
	<input type="hidden" name="classrooms[{{ $index }}][classroom_longitude]" value="">
        @endif
    @else
	<div class="md:col-span-2 mb-6 form-item classroom-map-section" data-index="{{ $index }}" style="{{ $useMap ? '' : 'display:none;' }}">
		<div class="mt-4">
        	<label class="field-label label-l required">地図</label><span class="text-sm text-gray-500">※地図をクリックしてください。</span>
                <div id="map-{{ $index }}" class="classroom-map mt-2" style="height: 400px; width: 100%; border: 1px solid #ccc;" data-index="{{ $index }}"></div>
        </div>
    </div>
	<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
		<input type="hidden" id="latitude-{{ $index }}" name="classrooms[{{ $index }}][classroom_latitude]" value="{{ $useMap ? old('classrooms.' . $index . '.classroom_latitude', $classroom['classroom_latitude'] ?? '') : '' }}">
		<input type="hidden" id="longitude-{{ $index }}" name="classrooms[{{ $index }}][classroom_longitude]" value="{{ $useMap ? old('classrooms.' . $index . '.classroom_longitude', $classroom['classroom_longitude'] ?? '') : '' }}">
	</div>
    @endif

    @if(!$isReadonly)
    <!-- 「事業者情報と同じ内容を入力」ボックス（続き）：以下の項目も事業者情報がコピーされる -->
    <div class="bg-gray-100 p-4 rounded-lg mt-6">
        <p class="text-xs text-gray-500 mb-2">「事業者情報と同じ内容を入力」ボタンでコピーされる項目です。</p>
    @endif

    <!-- 電話番号 -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">電話番号</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_phone]" 
                   id="classroom_phone-{{ $index }}"
                   value="{{ old('classrooms.' . $index . '.classroom_phone', $classroom['classroom_phone'] ?? '') }}" 
                   class="field-base field-w-100" placeholder="03-1234-5678" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label></label>
            <div class="notice2">※教室検索画面等で公開可能な電話番号を入力してください<br />※半角数字、ハイフン「-」ありで入力してください</div>
        </div>
    </div>

    <!-- FAX -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l mb-0">FAX</label>
            <input type="text" name="classrooms[{{ $index }}][classroom_fax]" 
                   id="classroom_fax-{{ $index }}"
                   value="{{ old('classrooms.' . $index . '.classroom_fax', $classroom['classroom_fax'] ?? '') }}" 
                   class="field-base field-w-100" placeholder="03-1234-5678" {{ $isReadonly ? 'readonly' : '' }}>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label></label>
            <div class="notice2">※半角数字、ハイフン「-」ありで入力してください</div>
        </div>
    </div>

    <!-- E-Mailアドレス -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l mb-0">E-Mailアドレス</label>
            <input type="email" name="classrooms[{{ $index }}][classroom_email]" 
                   id="classroom_email-{{ $index }}"
                   value="{{ old('classrooms.' . $index . '.classroom_email', $classroom['classroom_email'] ?? '') }}" 
                   class="field-base field-w-100" maxlength="255" {{ $isReadonly ? 'readonly' : '' }}>
        </div>
    </div>
    @if(!$isReadonly)
    </div>
    <!-- /「事業者情報と同じ内容を入力」ボックス（続き・ここまで） -->
    @endif

    <!-- 営業時間 -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">営業時間</label>
            <input type="text" name="classrooms[{{ $index }}][business_hours]" 
                   value="{{ old('classrooms.' . $index . '.business_hours', $classroom['business_hours'] ?? '') }}" 
                   class="field-base field-w-100" maxlength="200" placeholder="平日 9:00-18:00" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
    </div>

    <!-- 定休日 -->
    <div class="form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">定休日</label>
            <input type="text" name="classrooms[{{ $index }}][holiday]"
                   value="{{ old('classrooms.' . $index . '.holiday', $classroom['holiday'] ?? '') }}"
                   class="field-base field-w-100" maxlength="100" placeholder="土日祝日" {{ $isReadonly ? 'readonly' : '' }} required>
        </div>
    </div>

    <!-- contact_person_name, contact_department, document_address, document_addresseeは削除済み -->

    <!-- 教室紹介 -->
    <div class="md:col-span-2 form-item mb-4 mt-6">
        <label class="field-label label-l">教室紹介</label>
        <textarea name="classrooms[{{ $index }}][classroom_introduction]" 
                  class="field-base field-textarea field-w-100" rows="4" 
                  placeholder="教室の特徴や特色について" {{ $isReadonly ? 'readonly' : '' }}>{{ old('classrooms.' . $index . '.classroom_introduction', $classroom['classroom_introduction'] ?? '') }}</textarea>
    </div>

    <!-- サービス提供の類型 -->
    <div class="md:col-span-2 form-item mb-4 mt-6">
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label class="field-label label-l required mb-0">サービス提供の類型</label>
            <div>
                @if($isReadonly)
                    <p class="mt-1 text-gray-900">{{ old('classrooms.' . $index . '.service_type', $classroom['service_type'] ?? '') }}</p>
                    <input type="hidden" name="classrooms[{{ $index }}][service_type]" value="{{ old('classrooms.' . $index . '.service_type', $classroom['service_type'] ?? '') }}">
                @else
                    <input type="radio" name="classrooms[{{ $index }}][service_type]" class="mr-1" value="教室型" {{ (old('classrooms.' . $index . '.service_type', $classroom['service_type'] ?? '') == '教室型') ? 'checked' : '' }} required>教室型
                    <input type="radio" name="classrooms[{{ $index }}][service_type]" class="ml-4 mr-1" value="訪問型" {{ (old('classrooms.' . $index . '.service_type', $classroom['service_type'] ?? '') == '訪問型') ? 'checked' : '' }} required>訪問型
                    <input type="radio" name="classrooms[{{ $index }}][service_type]" class="ml-4 mr-1" value="通信型" {{ (old('classrooms.' . $index . '.service_type', $classroom['service_type'] ?? '') == '通信型') ? 'checked' : '' }} required>通信型
                @endif
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-4 items-center">
            <label></label>
            <div class="notice2">※複数ある場合は、主たる類型にチェックしてください</div>
        </div>
    </div>

    <!-- 習い事の種別 -->
    <div class="md:col-span-2 form-item mb-6">
        <label class="field-label label-l required">習い事の種別 </label>
        <div class="notice2">※１教室につき種別は１つになります。複数ある場合は、主たる種別にチェックしてください。その他の種別については、教室紹介に分かるようにご記入ください。</div><br>
        @php
            $selectedCategoryId = old('classrooms.' . $index . '.lesson_category', $classroom['lesson_category'] ?? '');
            $selectedCategoryName = '';
            if ($selectedCategoryId == '-1') {
                $selectedCategoryName = 'その他';
            } elseif ($selectedCategoryId && isset($categories)) {
                foreach ($categories as $parentId => $childCategories) {
                    foreach ($childCategories as $category) {
                        if ($category->id == $selectedCategoryId) {
                            $selectedCategoryName = $category->name;
                            break 2;
                        }
                    }
                }
            }
        @endphp
        @if($isReadonly)
            <p class="mt-1 text-gray-900">{{ $selectedCategoryName }}</p>
            <input type="hidden" name="classrooms[{{ $index }}][lesson_category]" value="{{ $selectedCategoryId }}">
        @else
            <table class="w-full text-m text-left rtl:text-right text-body border-collapse border border-gray-300">
                @if(isset($categories))
                    @foreach($categories as $parentId => $childCategories)
                        @if($childCategories->count() > 0)
                            <tr class="border-b border-gray-300">
                                <th class="border-r border-gray-300 px-4 py-2">{{ $childCategories->first()->parent->name }}</th>
                                <td class="border-r border-gray-300 px-4 py-2">
                                    @foreach($childCategories as $category)
									<span class="whitespace-nowrap mr-2"><input type="radio" name="classrooms[{{ $index }}][lesson_category]" class="mr-1" value="{{ $category->id }}" {{ (old('classrooms.' . $index . '.lesson_category', $classroom['lesson_category'] ?? '') == $category->id) ? 'checked' : '' }} required>{{ $category->name }}</span>
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endif
            </table>
        @endif
    </div>

    <!-- 教室画像 -->
    <div class="md:col-span-2 mt-6 form-item mb-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <label class="field-label label-l">教室紹介画像</label>
            @if($isReadonly)
                <div class="mt-3">
                    @if(!empty($classroom['classroom_image_temp_path']))
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-image text-green-600"></i>
                            <div>
                                <p class="text-green-600 font-medium">
                                    添付済み: {{ $classroom['classroom_image_filename'] ?? 'ファイル' }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    ファイルサイズ: {{ isset($classroom['classroom_image_size']) ? number_format($classroom['classroom_image_size'] / 1024, 1) : '0' }}KB
                                </p>
                            </div>
                        </div>
                        <input type="hidden" name="classrooms[{{ $index }}][classroom_image_uploaded]" value="1">
                        <input type="hidden" name="classrooms[{{ $index }}][classroom_image_filename]" value="{{ $classroom['classroom_image_filename'] ?? '' }}">
                    @else
                        <p class="text-gray-500">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                            画像がアップロードされていません
                        </p>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-600 mt-1 mb-3">
                    教室の外観・内観・設備等がわかる画像をアップロードしてください（任意）
                </p>
                <div class="space-y-3">
                    <input type="file" 
                           name="classrooms[{{ $index }}][classroom_image]" 
                           class="field-base field-w-100 @error('classrooms.' . $index . '.classroom_image') error @enderror"
                           accept="image/jpeg,image/jpg,image/png" 
                           onchange="previewClassroomImage(this, {{ $index }})">
                    <p class="text-xs text-gray-500">
                        対応形式: JPEG, PNG (最大10MB)
                    </p>
                    @error('classrooms.' . $index . '.classroom_image')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    
                    <!-- プレビュー表示 -->
                    <div id="classroom-image-preview-{{ $index }}" class="hidden mt-3">
                        <p class="text-sm font-medium text-gray-700 mb-2">プレビュー:</p>
                        <img id="classroom-image-preview-img-{{ $index }}" 
                             src="" 
                             alt="教室画像プレビュー" 
                             class="max-w-xs max-h-48 rounded-lg border border-gray-300 shadow-sm">
                        <button type="button" 
                                onclick="clearClassroomImagePreview({{ $index }})"
                                class="mt-2 text-sm text-red-600 hover:text-red-800">
                            画像を削除
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css" type="text/css">
<script src="https://unpkg.com/leaflet-gesture-handling"></script>

<script>
// 教室画像: 10MB制限（バイト）
const CLASSROOM_IMAGE_MAX_SIZE = 10 * 1024 * 1024;

// 教室画像プレビュー機能（添付時に10MBチェック）
function previewClassroomImage(input, index) {
    const previewContainer = document.getElementById(`classroom-image-preview-${index}`);
    const previewImg = document.getElementById(`classroom-image-preview-img-${index}`);
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > CLASSROOM_IMAGE_MAX_SIZE) {
            alert('教室紹介画像は10MB以下のファイルをアップロードしてください。');
            input.value = '';
            clearClassroomImagePreview(index);
            return;
        }
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.classList.remove('hidden');
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        clearClassroomImagePreview(index);
    }
}

// プレビュー削除機能
function clearClassroomImagePreview(index) {
    const input = document.querySelector(`input[name="classrooms[${index}][classroom_image]"]`);
    const previewContainer = document.getElementById(`classroom-image-preview-${index}`);
    const previewImg = document.getElementById(`classroom-image-preview-img-${index}`);
    
    input.value = '';
    previewImg.src = '';
    previewContainer.classList.add('hidden');
}


// 地図初期化関数（各教室フォームごとに呼び出される）
function initializeClassroomMapForContainer(mapContainer) {
    if (!mapContainer) {
        return;
    }

    const indexAttr = mapContainer.getAttribute('data-index');
    const index = indexAttr !== null ? parseInt(indexAttr, 10) : null;

    // 既に初期化されている場合はスキップ
    if (mapContainer.hasAttribute('data-initialized')) {
        return;
    }

    // デフォルトの緯度経度
    const defaultLat = {{ $latitude }};
    const defaultLng = {{ $longitude }};

    // 対象フォーム内の緯度経度フィールドを取得（ID 重複を避けるため name ベースで検索）
    const form = mapContainer.closest('form') || document;
    const latInput = form.querySelector(`input[name="classrooms[${index}][classroom_latitude]"]`);
    const lngInput = form.querySelector(`input[name="classrooms[${index}][classroom_longitude]"]`);
    const initialLat = latInput && latInput.value ? parseFloat(latInput.value) : defaultLat;
    const initialLng = lngInput && lngInput.value ? parseFloat(lngInput.value) : defaultLng;

    // 地図を初期化（要素を直接指定して ID 重複の影響を避ける）
    const map = L.map(mapContainer,{gestureHandling: true}).setView([initialLat, initialLng], 17);

    // OpenStreetMapレイヤーを追加
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // マーカー変数
    let marker = null;

    // 既存の緯度経度がある場合はマーカーを表示
    if (latInput && latInput.value && lngInput && lngInput.value) {
        marker = L.marker([initialLat, initialLng]).addTo(map);
    }

    // map / marker をコンテナに保持（後からの初期化解除用）
    mapContainer._leafletMap = map;
    mapContainer._leafletMarker = marker;

    // 地図クリックイベント（readonlyの場合は無効化）
    const isReadonly = mapContainer.hasAttribute('data-readonly');
    if (!isReadonly) {
        map.on('click', function(e) {
            // 既存のマーカーを削除
            if (marker) {
                map.removeLayer(marker);
            }

            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            // 緯度経度をフィールドにセット
            if (latInput) {
                latInput.value = lat.toFixed(8);
            }
            if (lngInput) {
                lngInput.value = lng.toFixed(8);
            }
            
            // マーカーを作成し、地図に追加
            marker = L.marker([lat, lng]).addTo(map);
            mapContainer._leafletMarker = marker;
        });
    }

    // 初期化済みフラグを設定
    mapContainer.setAttribute('data-initialized', 'true');
}

function initializeClassroomMap(index) {
    const mapId = `map-${index}`;
    const mapContainer = document.getElementById(mapId);
    if (!mapContainer) {
        console.error(`Map container not found: ${mapId}`);
        return;
    }

    initializeClassroomMapForContainer(mapContainer);
}

// 「地図を利用しない」チェックボックスのトグル
function setupUseMapCheckbox(index) {
    const checkbox = document.getElementById(`use-map-checkbox-${index}`);
    const hiddenInput = document.getElementById(`use-map-${index}`);
    const mapSection = document.querySelector(`.classroom-map-section[data-index="${index}"]`);
    if (!checkbox || !hiddenInput) {
        return;
    }
    checkbox.addEventListener('change', function() {
        const useMap = !this.checked;
        hiddenInput.value = useMap ? '1' : '0';
        const latInput = document.getElementById(`latitude-${index}`);
        const lngInput = document.getElementById(`longitude-${index}`);
        if (mapSection) {
            mapSection.style.display = useMap ? '' : 'none';
            const mapContainer = document.getElementById(`map-${index}`);
            if (useMap) {
                if (mapContainer && !mapContainer.hasAttribute('data-initialized')) {
                    initializeClassroomMap(index);
                }
            } else {
                // 緯度経度フィールドをクリア
                if (latInput) {
                    latInput.value = '';
                }
                if (lngInput) {
                    lngInput.value = '';
                }
                // 地図上のピン（マーカー）も削除して初期化
                if (mapContainer && mapContainer._leafletMap && mapContainer._leafletMarker) {
                    mapContainer._leafletMap.removeLayer(mapContainer._leafletMarker);
                    mapContainer._leafletMarker = null;
                }
            }
        }
    });
}

// ページ読み込み時に既存の地図を初期化
document.addEventListener('DOMContentLoaded', function() {
    // 既存の教室フォームの地図を初期化（表示されている地図のみ）
    document.querySelectorAll('.classroom-map').forEach(function(mapContainer) {
        const index = mapContainer.getAttribute('data-index');
        if (index !== null) {
            const section = mapContainer.closest('.classroom-map-section');
            if (section && section.style.display !== 'none') {
                initializeClassroomMap(parseInt(index));
            } else if (!section) {
                initializeClassroomMap(parseInt(index));
            }
        }
    });
    // 「地図を利用しない」チェックボックスの初期設定
    document.querySelectorAll('.use-map-checkbox').forEach(function(checkbox) {
        const index = checkbox.getAttribute('data-index');
        if (index !== null) {
            setupUseMapCheckbox(parseInt(index));
        }
    });
});

// 事業者情報を教室情報にコピーする関数（「事業者情報と同じ内容を入力」ボタンから呼ばれる）
function copyBusinessInfoToClassroom(index) {
    // 事業者情報のフィールドを取得
	const representativeFamilyName = document.querySelector('input[name="representative_family_name"]');
	const representativeGivenName = document.querySelector('input[name="representative_given_name"]');
	const representativeFamilyNameKana = document.querySelector('input[name="representative_family_name_kana"]');
	const representativeGivenNameKana = document.querySelector('input[name="representative_given_name_kana"]');
    const businessPostalCode = document.querySelector('input[name="postal_code"]');
    const businessPrefecture = document.querySelector('input[name="prefecture"]');
    const businessCity = document.querySelector('input[name="city"]');
    const businessAddress1 = document.querySelector('input[name="address1"]');
    const businessBuildingName = document.querySelector('input[name="building_name"]');
    const businessPhone = document.querySelector('input[name="phone"]');
    const businessFax = document.querySelector('input[name="fax"]');
    const businessEmail = document.querySelector('input[name="email"]');

    // 教室情報のフィールドを取得
    const classroomRepresentativeName = document.getElementById(`classroom_representative_name-${index}`);
	const classroomRepresentativeNameKana = document.getElementById(`classroom_representative_name_kana-${index}`);
    const classroomPostalCode = document.getElementById(`classroom_postal_code-${index}`);
    const classroomPrefecture = document.getElementById(`classroom_prefecture-${index}`);
    const classroomCity = document.getElementById(`classroom_city-${index}`);
    const classroomAddress1 = document.getElementById(`classroom_address1-${index}`);
    const classroomBuildingName = document.getElementById(`classroom_building_name-${index}`);
    const classroomPhone = document.getElementById(`classroom_phone-${index}`);
    const classroomFax = document.getElementById(`classroom_fax-${index}`);
    const classroomEmail = document.getElementById(`classroom_email-${index}`);

    // 値をコピー（氏名）
	if (classroomRepresentativeName) {
        const familyVal = representativeFamilyName ? representativeFamilyName.value.trim() : '';
        const givenVal = representativeGivenName ? representativeGivenName.value.trim() : '';
        let fullName = '';
        if (familyVal || givenVal) {
            fullName = familyVal && givenVal ? familyVal + '　' + givenVal : (familyVal || givenVal);
        }
		classroomRepresentativeName.value = fullName;
	}
    // 値をコピー（氏名カナ）
	if (classroomRepresentativeNameKana) {
        const familyKanaVal = representativeFamilyNameKana ? representativeFamilyNameKana.value.trim() : '';
        const givenKanaVal = representativeGivenNameKana ? representativeGivenNameKana.value.trim() : '';
        let fullKana = '';
        if (familyKanaVal || givenKanaVal) {
            fullKana = familyKanaVal && givenKanaVal ? familyKanaVal + '　' + givenKanaVal : (familyKanaVal || givenKanaVal);
        }
		classroomRepresentativeNameKana.value = fullKana;
	}
    if (businessPostalCode && classroomPostalCode) {
        classroomPostalCode.value = businessPostalCode.value || '';
    }
    if (businessPrefecture && classroomPrefecture) {
        classroomPrefecture.value = businessPrefecture.value || '';
    }
    if (businessCity && classroomCity) {
        classroomCity.value = businessCity.value || '';
    }
    if (businessAddress1 && classroomAddress1) {
        classroomAddress1.value = businessAddress1.value || '';
    }
    if (businessBuildingName && classroomBuildingName) {
        classroomBuildingName.value = businessBuildingName.value || '';
    }
    if (businessPhone && classroomPhone) {
        classroomPhone.value = businessPhone.value || '';
    }
    if (businessFax && classroomFax) {
        classroomFax.value = businessFax.value || '';
    }
    if (businessEmail && classroomEmail) {
        classroomEmail.value = businessEmail.value || '';
    }
}

// 教室情報の郵便番号検索機能
document.addEventListener('DOMContentLoaded', function() {
    // 動的に追加される教室フォームにも対応するため、イベント委譲を使用
    document.addEventListener('click', function(e) {
        if (e.target.closest('.search-classroom-postal-code')) {
            const button = e.target.closest('.search-classroom-postal-code');
            const index = button.getAttribute('data-index');
            
            const postalCodeInput = document.getElementById(`classroom_postal_code-${index}`);
            if (!postalCodeInput || postalCodeInput.value.trim() === '') {
                alert('郵便番号を入力してください。');
                return;
            }

            // 検索ボタンを無効化
            button.disabled = true;
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>検索中...';

            // フォームトークンを取得（セッションから）
            const formToken = '{{ session("form_access_token") }}';

            // APIを呼び出し
            fetch(`/api/postal-code/search?postcode=${encodeURIComponent(postalCodeInput.value)}`, {
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
                    const prefectureInput = document.getElementById(`classroom_prefecture-${index}`);
                    const cityInput = document.getElementById(`classroom_city-${index}`);
                    const address1Input = document.getElementById(`classroom_address1-${index}`);

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
                button.disabled = false;
                button.innerHTML = originalHtml;
            });
        }
    });
});
</script>