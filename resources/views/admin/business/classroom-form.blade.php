@extends('layouts.app')

@php
    // 変数の安全な初期化
    $classroom = $classroom ?? null;
    $isEdit = !is_null($classroom);
    $useMap = filter_var(old('use_map', $classroom ? ($classroom->use_map ?? true) : true), FILTER_VALIDATE_BOOLEAN);
@endphp

@section('title', ($isEdit ? '教室編集' : '教室新規登録') . ' - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
			<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
				<li><a href="{{ route('admin.business.index') }}" class="hover:text-gray-700">事業者管理</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('admin.business.edit', $business) }}" class="hover:text-gray-700">{{ $business->business_name }}</a></li>
                <li><span class="mx-2">/</span></li>
                <li class="text-gray-900 font-medium">
                    {{ $isEdit ? $classroom->classroom_name : '教室新規登録' }}
                </li>
            </ol>
        </nav>		
	
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">

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

    <!-- 教室情報フォーム -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-{{ $isEdit ? 'edit' : 'school' }} text-gray-400 mr-2"></i>
                {{ $isEdit ? '教室情報編集' : '教室情報入力' }}
            </h2>
        </div>
        
        <form action="{{ $isEdit ? route('admin.business.update-classroom', [$business, $classroom]) : route('admin.business.store-classroom', $business) }}" 
              method="POST" enctype="multipart/form-data" class="p-6" id="classroom-form">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <!-- 教室名 -->
            <div class="form-row cols-2">
				<label for="classroom_name" class="field-label required">教室名</label>
				<input type="text" 
						class="field-base field-w-100 @error('classroom_name') error @enderror" 
						id="classroom_name" name="classroom_name" 
						value="{{ old('classroom_name', $classroom->classroom_name ?? '') }}" 
						maxlength="100" required>
				@error('classroom_name')
					<span class="field-error">{{ $message }}</span>
				@enderror
			</div>
			<div class="form-row cols-2">
				<label for="classroom_name_kana" class="field-label required">
					教室名（カナ）
				</label>
				<input type="text" 
						class="field-base field-w-100 @error('classroom_name_kana') error @enderror" 
						id="classroom_name_kana" name="classroom_name_kana" 
						value="{{ old('classroom_name_kana', $classroom->classroom_name_kana ?? '') }}" 
						maxlength="100">
				@error('classroom_name_kana')
					<span class="field-error">{{ $message }}</span>
				@enderror
            </div>

            <!-- 教室代表者 -->
            <div class="form-row cols-2">
					<label for="classroom_representative_name" class="field-label required">
					代表者氏名
				</label>
				<input type="text" 
						class="field-base field-w-100 @error('classroom_representative_name') error @enderror" 
						id="classroom_representative_name" name="classroom_representative_name" 
						value="{{ old('classroom_representative_name', $classroom->classroom_representative_name ?? '') }}" 
						maxlength="50">
				@error('classroom_representative_name')
					<span class="field-error">{{ $message }}</span>
				@enderror
			</div>
			<div class="form-row cols-2">
				<label for="classroom_representative_name_kana" class="field-label required">
					代表者氏名（カナ）
				</label>
				<input type="text" 
						class="field-base field-w-100 @error('classroom_representative_name_kana') error @enderror" 
						id="classroom_representative_name_kana" name="classroom_representative_name_kana" 
						value="{{ old('classroom_representative_name_kana', $classroom->classroom_representative_name_kana ?? '') }}" 
						maxlength="50">
				@error('classroom_representative_name_kana')
					<span class="field-error">{{ $message }}</span>
				@enderror
            </div>

            <!-- 教室住所 -->
            <div class="section-divider">
                <h3 class="text-lg font-medium text-gray-900 mb-4">教室所在地</h3>
                <div class="form-row cols-2">
					<label for="classroom_postal_code" class="field-label required">
						郵便番号
					</label>
					<input type="text" 
							class="field-base field-w-100 @error('classroom_postal_code') error @enderror" 
							id="classroom_postal_code" name="classroom_postal_code" 
							value="{{ old('classroom_postal_code', $classroom->classroom_postal_code ?? '') }}" 
							placeholder="123-4567" maxlength="10">
					@error('classroom_postal_code')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>
				<div class="form-row cols-2">
					<label for="classroom_prefecture" class="field-label required">
						都道府県
					</label>
					<input type="text" 
							class="field-base field-w-100 @error('classroom_prefecture') error @enderror" 
							id="classroom_prefecture" name="classroom_prefecture" 
							value="{{ old('classroom_prefecture', $classroom->classroom_prefecture ?? '') }}" 
							maxlength="20">
					@error('classroom_prefecture')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>
				<div class="form-row cols-2">
					<label for="classroom_city" class="field-label required">
						市区町村
					</label>
					<input type="text" 
							class="field-base field-w-100 @error('classroom_city') error @enderror" 
							id="classroom_city" name="classroom_city" 
							value="{{ old('classroom_city', $classroom->classroom_city ?? '') }}" 
							maxlength="50">
					@error('classroom_city')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>

			<div class="form-row cols-2">
				<label for="classroom_address1" class="field-label required">
					それ以降の住所
				</label>
				<input type="text" 
						class="field-base field-w-100 @error('classroom_address1') error @enderror" 
						id="classroom_address1" name="classroom_address1" 
						value="{{ old('classroom_address1', $classroom->classroom_address1 ?? '') }}" 
						maxlength="100">
				@error('classroom_address1')
					<span class="field-error">{{ $message }}</span>
				@enderror
			</div>
			<div class="form-row cols-2">
				<label for="classroom_building_name" class="field-label">
					建物名
				</label>
				<input type="text" 
						class="field-base field-w-100 @error('classroom_building_name') error @enderror" 
						id="classroom_building_name" name="classroom_building_name" 
						value="{{ old('classroom_building_name', $classroom->classroom_building_name ?? '') }}" 
						maxlength="100">
				@error('classroom_building_name')
					<span class="field-error">{{ $message }}</span>
				@enderror
			</div>
			<div class="form-row cols-2">
				<label class="field-label">地図を利用しない</label>
				<div class="w-full flex items-center">
					<label class="flex items-center">
						<input type="checkbox" id="use-map-checkbox" class="mr-2" value="0" {{ !$useMap ? 'checked' : '' }}>
						<span class="text-sm text-gray-700">地図を利用しない</span>
					</label>
					<input type="hidden" id="use-map-hidden" name="use_map" value="{{ $useMap ? '1' : '0' }}">
				</div>
			</div>
			<div class="form-row cols-2 classroom-map-section" style="{{ $useMap ? '' : 'display:none;' }}">
				<label class="field-label required">地図</label>
				<div class="w-full">
					<div id="classroom-map" class="mt-2" style="height: 400px; width: 100%; border: 1px solid #ccc;"></div>
					<div class="grid grid-cols-2 gap-4 mt-4">
						{{-- 緯度経度 --}}
						<input type="hidden" 
								id="classroom_latitude" 
								name="classroom_latitude" 
								value="{{ $useMap ? old('classroom_latitude', $classroom?->classroom_latitude ?? '') : '' }}">
						<input type="hidden" 
								id="classroom_longitude" 
								name="classroom_longitude" 
								value="{{ $useMap ? old('classroom_longitude', $classroom?->classroom_longitude ?? '') : '' }}">
					</div>
				</div>
			</div>
			
            <!-- 連絡先情報 -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">教室連絡先</h3>
                <div class="form-row cols-2">
				<label for="classroom_phone" class="field-label required">
					電話番号
				</label>
				<input type="tel" 
						class="field-base field-w-100 @error('classroom_phone') error @enderror" 
						id="classroom_phone" name="classroom_phone" 
						value="{{ old('classroom_phone', $classroom->classroom_phone ?? '') }}" 
						maxlength="20" placeholder="03-1234-5678" required>
					@error('classroom_phone')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>
				<div class="form-row cols-2">
				<label for="classroom_fax" class="field-label">
					FAX
				</label>
				<input type="tel" 
						class="field-base field-w-100 @error('classroom_fax') error @enderror" 
						id="classroom_fax" name="classroom_fax" 
						value="{{ old('classroom_fax', $classroom->classroom_fax ?? '') }}" 
						maxlength="20">
					@error('classroom_fax')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>
				<div class="form-row cols-2">
					<label for="classroom_email" class="field-label">
						E-Mailアドレス
					</label>
					<input type="email" 
							class="field-base field-w-100 @error('classroom_email') error @enderror" 
							id="classroom_email" name="classroom_email" 
							value="{{ old('classroom_email', $classroom->classroom_email ?? '') }}" 
							maxlength="255">
					@error('classroom_email')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>
            </div>


            <!-- 営業時間・定休日 -->
            <div class="section-divider">
                <h3 class="text-lg font-medium text-gray-900 mb-4">営業時間・定休日</h3>
                <div class="form-row cols-2">
					<label for="business_hours" class="field-label required">
						営業時間
					</label>
					<textarea class="field-base field-w-100 @error('business_hours') error @enderror" 
							id="business_hours" name="business_hours" rows="2" 
							placeholder="例：平日 9:00-21:00、土日 10:00-18:00">{{ old('business_hours', $classroom->business_hours ?? '') }}</textarea>
					@error('business_hours')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>
				<div class="form-row cols-2">
					<label for="holiday" class="field-label required">
						定休日
					</label>
					<textarea class="field-base field-w-100 @error('holiday') error @enderror" 
							id="holiday" name="holiday" rows="2" 
							placeholder="例：毎週月曜日、年末年始">{{ old('holiday', $classroom->holiday ?? '') }}</textarea>
					@error('holiday')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>
            </div>

            <!-- 運営事務局からの文書等送付先 -->
            <!-- contact_person_name, contact_department, document_address, document_addresseeは削除済み -->

            <!-- 教室紹介 -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">教室紹介</h3>
                <div class="field-group">
                    <label for="classroom_introduction" class="field-label">
                        教室紹介
                    </label>
                    <textarea class="field-base field-w-100 @error('classroom_introduction') error @enderror" 
                              id="classroom_introduction" name="classroom_introduction" rows="4">{{ old('classroom_introduction', $classroom->classroom_introduction ?? '') }}</textarea>
                    @error('classroom_introduction')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- サービス提供の種類・習い事の種別 -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">サービス種別</h3>
                <div class="form-row cols-1">
                    <div class="field-group">
                        <label for="service_type" class="field-label required">
                            サービス提供の種類
                        </label>
						<input type="radio" name="service_type" value="教室型" class="ml-4 mr-1" {{ old('service_type', $classroom->service_type ?? '') == '教室型' ? 'checked' : '' }}>教室型
						<input type="radio" name="service_type" value="訪問型" class="ml-4 mr-1" {{ old('service_type', $classroom->service_type ?? '') == '訪問型' ? 'checked' : '' }}>訪問型
						<input type="radio" name="service_type" value="通信型" class="ml-4 mr-1" {{ old('service_type', $classroom->service_type ?? '') == '通信型' ? 'checked' : '' }}>通信型
					@error('service_type')
						<span class="field-error">{{ $message }}</span>
					@enderror
				</div>
			</div>
                
                <!-- 習い事の種別選択 -->
                <div class="mt-6">
                    <label class="field-label required">習い事の種別</label>
                    <div class="mt-3">
                        @if($parentCategories && $parentCategories->count() > 0)
                            <!-- 2列の表組みレイアウト -->
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($parentCategories as $parentCategory)
                                            @if($parentCategory->activeCategories->count() > 0)
                                                <tr class="hover:bg-gray-50">
                                                    <!-- 親分類名 -->
                                                    <td class="px-2 py-2 text-sm font-medium text-gray-900 align-top border-r border-gray-200">
                                                        {{ $parentCategory->name }}
                                                    </td>
                                                    <!-- 子分類選択 -->
                                                    <td class="px-2 py-2">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                            @foreach($parentCategory->activeCategories as $category)
                                                                <label class="flex items-center space-x-2 text-sm cursor-pointer hover:bg-blue-50 p-2 rounded">
                                                                    <input type="radio" 
                                                                           name="lesson_category" 
                                                                           value="{{ $category->id }}"
                                                                           class="form-radio text-blue-600 focus:ring-blue-500"
                                                                           {{ old('lesson_category', $classroom->lesson_category ?? '') == $category->id ? 'checked' : '' }}>
                                                                    <span class="text-gray-700">{{ $category->name }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                                <i class="fas fa-info-circle text-gray-400 text-2xl mb-2"></i>
                                <p class="text-gray-600">習い事種別が登録されていません。</p>
                                <p class="text-sm text-gray-500 mt-1">先に習い事種別管理で種別を登録してください。</p>
                            </div>
                        @endif
                        @error('lesson_category')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

			<!-- 教室画像 -->
            <div class="field-group border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">教室画像</h3>
                
                <!-- 既存画像の表示 -->
                @if($isEdit && $classroom->hasClassroomImage())
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <img src="{{ $classroom->getClassroomImageDownloadUrl('medium') }}?t={{$classroom->updated_at->timestamp}}" 
                                     alt="現在の教室画像" 
                                     class="w-64 h-64 object-cover rounded-lg border">
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">現在の画像</h4>
                                <p class="text-sm text-gray-600">
                                    ファイル名: {{ $classroom->classroom_image_original_filename }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    サイズ: {{ number_format($classroom->classroom_image_file_size / 1024, 1) }} KB
                                </p>
                                <div class="mt-2 flex space-x-2">
                                    <a href="{{ $classroom->getClassroomImageDownloadUrl('original') }}" 
                                       target="_blank"
                                       class="text-xs text-blue-600 hover:text-blue-800">
                                        元サイズで表示
                                    </a>
                                    <button type="button" 
                                            onclick="document.getElementById('delete_classroom_image').checked = !document.getElementById('delete_classroom_image').checked; toggleDeleteImageCheckbox();"
                                            class="text-xs text-red-600 hover:text-red-800">
                                        画像を削除
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- 削除用チェックボックス -->
                        <div class="mt-3">
                            <label class="flex items-center space-x-2 text-sm">
                                <input type="checkbox" 
                                       name="delete_classroom_image" 
                                       id="delete_classroom_image" 
                                       value="1"
                                       class="form-checkbox text-red-600">
                                <span class="text-red-700">この画像を削除する</span>
                            </label>
                        </div>
                    </div>
				@else
                <!-- 新しい画像のアップロード -->
                <div class="field-group">
                    <label for="classroom_image" class="field-label">
                        {{ $isEdit && $classroom->hasClassroomImage() ? '画像を差し替え' : '画像アップロード' }}
                        <span class="text-sm text-gray-500">(任意)</span>
                    </label>
                    <div class="mt-2">
                        <input type="file" 
                               name="classroom_image" 
                               id="classroom_image"
                               accept="image/jpeg,image/png"
                               class="field-base field-w-100 @error('classroom_image') error @enderror"
                               onchange="previewClassroomImage(event)">
                        @error('classroom_image')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                        <p class="field-help">
                            JPEG、PNG形式の画像ファイル（最大10MB）をアップロードできます。
                        </p>
                    </div>
                    
                    <!-- プレビュー表示 -->
                    <div id="classroom_image_preview" class="mt-3 hidden">
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">プレビュー</h4>
                            <img id="classroom_image_preview_img" src="" alt="プレビュー" class="max-w-xs h-32 object-cover rounded border">
                            <button type="button" onclick="clearClassroomImagePreview()" class="mt-2 text-xs text-gray-600 hover:text-gray-800">
                                選択を取り消し
                            </button>
                        </div>
                    </div>
                </div>
				@endif

            </div>

			<!-- 教室状態 -->
            <div class="field-group border-gray-200 pt-6">
				<h3 class="text-lg font-medium text-gray-900 mb-4 ">教室状態</h3>
                <div class="field-checkbox">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', $classroom->is_active ?? 1) ? 'checked' : '' }}
                           class="field-checkbox-input">
                    <label for="is_active" class="field-checkbox-label">有効</label>
                </div>
                <div class="field-checkbox mt-3">
                    <input type="checkbox" name="apply" id="apply" value="1" 
                           {{ old('apply', $classroom->apply ?? 0) == 1 ? 'checked' : '' }}
                           class="field-checkbox-input">
                    <label for="apply" class="field-checkbox-label">承認</label>
                </div>
                <div class="field-checkbox mt-3">
                    <input type="checkbox" name="disallow_amount_specified_usage" id="disallow_amount_specified_usage" value="1"
                           {{ old('disallow_amount_specified_usage', $isEdit && ($classroom->disallow_amount_specified_usage ?? false)) ? 'checked' : '' }}
                           class="field-checkbox-input">
                    <label for="disallow_amount_specified_usage" class="field-checkbox-label">金額指定利用しない</label>
                </div>
                <p class="text-sm text-gray-500 mt-1">チェックすると、利用者マイページで「金額指定利用」が表示されず、申し込みもできません。</p>
                <div class="field-checkbox mt-3">
                    <input type="checkbox" name="qr_only" id="qr_only" value="1"
                           {{ old('qr_only', $isEdit && ($classroom->qr_only ?? false)) ? 'checked' : '' }}
                           class="field-checkbox-input">
                    <label for="qr_only" class="field-checkbox-label">QR決済のみ</label>
                </div>
                <p class="text-sm text-gray-500 mt-1">チェックすると、利用者はQRコード経由でのみ申し込みできます。</p>
            </div>


            <!-- フォームアクション -->
            <div class="border-t border-gray-200 pt-6">
                <div class="flex justify-between">
                    <a href="{{ route('admin.business.edit', $business) }}" 
                       class="btn-base btn-back btn-m">
                        <i class="fas fa-arrow-left mr-2"></i> 戻る
                    </a>
                    <button type="submit" 
                            class="btn-base {{ $isEdit ? 'btn-update' : 'btn-create' }} btn-m">
                        <i class="fas fa-save mr-2"></i> {{ $isEdit ? '更新' : '登録' }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if($isEdit)
        <!-- コース一覧セクション -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-graduation-cap text-gray-400 mr-2"></i>
                    コース一覧
                </h2>
                <a href="{{ route('admin.business.create-course', [$business, $classroom]) }}" 
                   class="btn-base btn-create btn-s">
                    <i class="fas fa-plus mr-2"></i>新規コース登録
                </a>
            </div>
            <div class="p-6">
                @if($classroom->courses->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">コース名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">カテゴリ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">料金</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状態</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($classroom->courses as $course)
                                    <tr class="{{ !$course->is_active ? 'bg-gray-50' : 'hover:bg-gray-50' }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $course->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $course->course_name }}</div>
                                            @if($course->course_description)
                                                <div class="text-sm text-gray-500">{{ Str::limit($course->course_description, 50) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($course->category_other)
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ $course->category_other }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">¥{{ number_format($course->price) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($course->is_active)
                                                <span class="label-base label-active label-xs">
                                                    有効
                                                </span>
                                            @else
                                                <span class="label-base label-disable label-xs">
                                                    無効
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('admin.business.edit-course', [$business, $classroom, $course]) }}" 
                                                   class="btn-base btn-update btn-xs">
                                                    編集
                                                </a>
                                                
                                                @if($course->is_active)
                                                    <form action="{{ route('admin.business.deactivate-course', [$business, $classroom, $course]) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn-base btn-disable btn-xs" 
                                                                onclick="return confirm('このコースを無効化しますか？')">
                                                            無効化
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.business.activate-course', [$business, $classroom, $course]) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn-base btn-create btn-xs">
                                                            有効化
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-graduation-cap text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 mb-4">登録されているコースがありません。</p>
                        <a href="{{ route('admin.business.create-course', [$business, $classroom]) }}" 
                           class="btn-base btn-create btn-m">
                            <i class="fas fa-plus mr-2"></i>最初のコースを登録
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // フォーム送信をインターセプト
    const form = document.getElementById('classroom-form');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>送信中...';
            
            // FormDataを作成
            const formData = new FormData(form);
            
            // 既存のエラーメッセージをクリア
            clearValidationErrors();
            
            try {
                const response = await fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                
                if (response.ok) {
                    // 成功時はリダイレクト
                    const data = await response.json();
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else if (response.status === 422) {
                    // バリデーションエラー
                    const data = await response.json();
                    displayValidationErrors(data.errors);
                } else {
                    // その他のエラー
                    const data = await response.json().catch(() => ({ message: 'エラーが発生しました。' }));
                    alert(data.message || 'エラーが発生しました。');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('送信中にエラーが発生しました。');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
    
    // バリデーションエラーをクリア
    function clearValidationErrors() {
        // 既存のエラーメッセージを削除
        document.querySelectorAll('.field-error').forEach(el => el.remove());
        // エラークラスを削除
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    }
    
    // バリデーションエラーを表示
    function displayValidationErrors(errors) {
        for (const [field, messages] of Object.entries(errors)) {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                // エラークラスを追加
                input.classList.add('error');
                
                // エラーメッセージを表示
                const errorSpan = document.createElement('span');
                errorSpan.className = 'field-error';
                errorSpan.textContent = Array.isArray(messages) ? messages[0] : messages;
                
                // 入力フィールドの後にエラーメッセージを挿入
                const fieldRow = input.closest('.form-row, .field-group');
                if (fieldRow) {
                    fieldRow.appendChild(errorSpan);
                } else {
                    input.parentNode.insertAdjacentElement('afterend', errorSpan);
                }
            }
        }
        
        // エラーがある最初のフィールドにスクロール
        const firstError = document.querySelector('.field-error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    // ラジオボタンの変更を監視
    const lessonCategoryRadios = document.querySelectorAll('input[name="lesson_category"]');
    const otherTextarea = document.getElementById('lesson_category_other');
    
    lessonCategoryRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === '-1') {
                // その他が選択された場合
                otherTextarea.disabled = false;
                otherTextarea.focus();
            } else {
                // その他以外が選択された場合
                otherTextarea.disabled = true;
                otherTextarea.value = '';
            }
        });
    });
    
    // 初期状態の設定
    const checkedRadio = document.querySelector('input[name="lesson_category"]:checked');
    if (checkedRadio && checkedRadio.value !== '-1') {
        otherTextarea.disabled = true;
    }
});

// 教室画像プレビュー関数
function previewClassroomImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('classroom_image_preview');
    const previewImg = document.getElementById('classroom_image_preview_img');
    
    if (file) {
        // ファイルサイズチェック（10MB）
        if (file.size > 10 * 1024 * 1024) {
            alert('ファイルサイズが10MBを超えています。');
            event.target.value = '';
            preview.classList.add('hidden');
            return;
        }
        
        // ファイル形式チェック
        if (!file.type.match(/^image\/(jpeg|png)$/)) {
            alert('JPEG またはPNG形式の画像ファイルを選択してください。');
            event.target.value = '';
            preview.classList.add('hidden');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('hidden');
    }
}

// プレビューをクリア
function clearClassroomImagePreview() {
    document.getElementById('classroom_image').value = '';
    document.getElementById('classroom_image_preview').classList.add('hidden');
}

// 削除チェックボックスの表示/非表示
function toggleDeleteImageCheckbox() {
    const deleteCheckbox = document.getElementById('delete_classroom_image');
    const fileInput = document.getElementById('classroom_image');
    
    if (deleteCheckbox.checked) {
        fileInput.disabled = true;
        fileInput.value = '';
        clearClassroomImagePreview();
    } else {
        fileInput.disabled = false;
    }
}
</script>

<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- 地図表示共通ライブラリ -->
<script src="{{ asset('js/map.js') }}"></script>

<script>
// 教室フォーム用の座標設定関数
function setClassroomMapCoordinates(lat, lng) {
    const latInput = document.getElementById('classroom_latitude');
    const lngInput = document.getElementById('classroom_longitude');
    
    if (latInput) {
        latInput.value = lat.toFixed(8);
    }
    if (lngInput) {
        lngInput.value = lng.toFixed(8);
    }
}

// 「地図を利用しない」チェックボックスと地図初期化
document.addEventListener('DOMContentLoaded', function() {
    const useMapCheckbox = document.getElementById('use-map-checkbox');
    const useMapHidden = document.getElementById('use-map-hidden');
    const mapSection = document.querySelector('.classroom-map-section');
    if (useMapCheckbox && useMapHidden && mapSection) {
        useMapCheckbox.addEventListener('change', function() {
            const useMap = !this.checked;
            useMapHidden.value = useMap ? '1' : '0';
            mapSection.style.display = useMap ? '' : 'none';
            const latInput = document.getElementById('classroom_latitude');
            const lngInput = document.getElementById('classroom_longitude');
            if (!useMap && latInput && lngInput) {
                latInput.value = '';
                lngInput.value = '';
            }
        });
    }

    const mapContainer = document.getElementById('classroom-map');
    if (!mapContainer || (mapSection && mapSection.style.display === 'none')) {
        return;
    }

    // 既存の緯度経度値を取得
    const latInput = document.getElementById('classroom_latitude');
    const lngInput = document.getElementById('classroom_longitude');
    
    @php
        $isEdit = !is_null($classroom);
        if ($isEdit) {
            $classroomLat = $classroom->classroom_latitude ?? null;
            $classroomLng = $classroom->classroom_longitude ?? null;
        } else {
            $classroomLat = null;
            $classroomLng = null;
        }
        $defaultLat = $latitude ?? 35.6812;
        $defaultLng = $longitude ?? 139.7671;
    @endphp
    
    const classroomLat = @json($classroomLat);
    const classroomLng = @json($classroomLng);
    const defaultLat = {{ $defaultLat }};
    const defaultLng = {{ $defaultLng }};
    const isEdit = {{ $isEdit ? 'true' : 'false' }};
    
    // 初期座標を決定
    let initialLat, initialLng, showMarker;
    
    if (isEdit && classroomLat !== null && classroomLng !== null) {
        // 編集時：教室の座標がある場合はそれを使用してマーカーを表示
        initialLat = parseFloat(classroomLat);
        initialLng = parseFloat(classroomLng);
        showMarker = true;
    } else {
        // 新規登録時、または編集時でも座標がない場合：subdomainの座標を使用（マーカーなし）
        initialLat = latInput && latInput.value ? parseFloat(latInput.value) : defaultLat;
        initialLng = lngInput && lngInput.value ? parseFloat(lngInput.value) : defaultLng;
        showMarker = false;
    }

    // map.jsを使用して地図を初期化
    const map = initMap({
        containerId: 'classroom-map',
        latitude: initialLat,
        longitude: initialLng,
        zoom: 17,
        showMarker: showMarker,
        enableClick: true, // クリックイベントを有効化
		scrollWheelZoom: false,
		touchZoom: false,
        onClick: function(lat, lng, marker, map) {
            // 緯度経度をフィールドにセット
            setClassroomMapCoordinates(lat, lng);
        }
    });
});
</script>
@endpush