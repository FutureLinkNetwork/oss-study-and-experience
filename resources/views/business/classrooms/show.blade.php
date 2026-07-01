@extends('layouts.app')

@section('title', '教室詳細・編集 - 習い事クーポン管理システム')

@push('styles')
<style>
    /* 印刷用スタイル */
	.print-qr-section {
		display: none;
	}
	@media print {
        .no-print {
            display: none;
        }
		.print-qr-section {
			display: block;
		}
		.print-service-name {
			font-size: 36px;
			font-weight: bold;
			text-align: center;
			margin-bottom: 20px;
		}
		.print-classroom-name {
			font-size: 36px;
			font-weight: bold;
			text-align: center;
			margin-bottom: 20px;
		}
		.print-qr-container {
			margin: 100px auto;
		}
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-blue-100 no-print">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('business.classrooms.index') }}" class="hover:text-gray-700">教室管理</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>教室詳細・編集</span></li>
            </ol>
        </nav>
	<!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
            <!-- 成功メッセージ -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
                
                <!-- 教室基本情報（表示のみ） -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">基本情報</h3>
                        <p class="text-sm text-gray-500">※ 基本情報の変更は管理者にお問い合わせください</p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">教室名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->classroom_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">教室名（カナ）</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->classroom_name_kana }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">代表者名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->classroom_representative_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">代表者名（カナ）</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->classroom_representative_name_kana }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">郵便番号</dt>
                            <dd class="mt-1 text-sm text-gray-900">〒{{ $classroom->classroom_postal_code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">住所</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $classroom->classroom_prefecture }}{{ $classroom->classroom_city }}{{ $classroom->classroom_address1 }}
                                @if($classroom->classroom_building_name)
                                @endif
                            </dd>
                        </div>
						@if($classroom->use_map)
						<div>
							<dt class="text-sm font-medium text-gray-500">地図</dt>
							<dd class="mt-1 text-sm text-gray-900">
								<div id="classroom-map" class="mt-2" style="height: 400px; width: 100%; border: 1px solid #ccc;"></div>
							</dd>
						</div>
						@endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">電話番号</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->classroom_phone ?? '未設定' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">FAX番号</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->classroom_fax ?? '未設定' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">メールアドレス</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->classroom_email ?? '未設定' }}</dd>
                        </div>
						<div>
							<dt class="text-sm font-medium text-gray-500">教室画像</dt>
							<dd class="mt-1 text-sm text-gray-900">
								@if($classroom->hasClassroomImage())
									<div class="flex items-start space-x-4">
										<div class="flex-shrink-0">
											<img src="{{ $classroom->getBusinessClassroomImageDownloadUrl('medium') }}" 
												 alt="教室画像" 
												 class="w-64 h-64 object-cover rounded-lg border border-gray-200">
										</div>
										<div class="flex-1">
											<p class="text-sm text-gray-600 mb-2">
												ファイル名: {{ $classroom->classroom_image_original_filename }}
											</p>
											<p class="text-sm text-gray-600 mb-2">
												サイズ: {{ number_format($classroom->classroom_image_file_size / 1024, 1) }} KB
											</p>
											<a href="{{ $classroom->getBusinessClassroomImageDownloadUrl('original') }}" 
											   target="_blank"
											   class="text-xs text-blue-600 hover:text-blue-800">
												<i class="fas fa-external-link-alt mr-1"></i>元サイズで表示
											</a>
										</div>
									</div>
								@else
									<p class="text-sm text-gray-500">教室画像が登録されていません</p>
								@endif
							</dd>
						</div>
                    </div>
                </div>

                <!-- 編集可能な情報 -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">編集可能な情報</h3>
                        <p class="text-sm text-gray-500">以下の情報は事業者側で編集可能です</p>
                    </div>
                    
                    <form method="POST" action="{{ route('business.classrooms.update', $classroom) }}" class="px-6 py-4">
                        @csrf
                        @method('PUT')
                        
                        <!-- 教室紹介 -->
                        <div class="field-group">
                            <label for="classroom_introduction" class="field-label">
                                教室紹介
                            </label>
                            <textarea id="classroom_introduction" 
                                      name="classroom_introduction" 
                                      rows="6" 
                                      class="field-base field-textarea field-w-100 @error('classroom_introduction') error @enderror"
                                      placeholder="教室の特徴や雰囲気などを紹介してください">{{ old('classroom_introduction', $classroom->classroom_introduction) }}</textarea>
                            @error('classroom_introduction')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 有効/無効 -->
                        <div class="field-group">
                            <label class="field-label">
                                教室の状態
                            </label>
                            <div class="space-y-2">
                                <label class="field-checkbox">
                                    <input type="radio" 
                                           name="is_active" 
                                           value="1" 
                                           class="field-checkbox-input"
                                           {{ old('is_active', $classroom->is_active) == 1 ? 'checked' : '' }}>
                                    <span class="field-checkbox-label">
                                        <i class="fas fa-check-circle text-green-600 mr-1"></i>有効
                                    </span>
                                </label>
                                <label class="field-checkbox">
                                    <input type="radio" 
                                           name="is_active" 
                                           value="0" 
                                           class="field-checkbox-input"
                                           {{ old('is_active', $classroom->is_active) == 0 ? 'checked' : '' }}>
                                    <span class="field-checkbox-label">
                                        <i class="fas fa-times-circle text-red-600 mr-1"></i>無効
                                    </span>
                                </label>
                            </div>
                            @error('is_active')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label">
                                金額指定利用
                            </label>
                            <label class="field-checkbox">
                                <input type="checkbox"
                                       name="disallow_amount_specified_usage"
                                       value="1"
                                       class="field-checkbox-input"
                                       {{ old('disallow_amount_specified_usage', $classroom->disallow_amount_specified_usage) ? 'checked' : '' }}>
                                <span class="field-checkbox-label">金額指定利用しない</span>
                            </label>
                            <p class="text-sm text-gray-500 mt-1">チェックすると、利用者マイページで「金額指定利用」が表示されません。</p>
                            @error('disallow_amount_specified_usage')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label">
                                QR決済のみ
                            </label>
                            <input type="hidden" name="qr_only" value="0">
                            <label class="field-checkbox">
                                <input type="checkbox"
                                       name="qr_only"
                                       value="1"
                                       class="field-checkbox-input"
                                       {{ old('qr_only', $classroom->qr_only) ? 'checked' : '' }}>
                                <span class="field-checkbox-label">QR決済の利用のみ許可する</span>
                            </label>
                            <p class="text-sm text-gray-500 mt-1">チェックすると、利用者はQRコード経由でのみ申し込みとなります。</p>
                            @error('qr_only')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 更新ボタン -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="btn-base btn-update btn-m">
                                <i class="fas fa-save mr-2"></i>更新する
                            </button>
                        </div>
                    </form>
                </div>

                <!-- QRコード表示 -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">利用者向けページへのアクセス</h3>
                        <p class="text-sm text-gray-500">利用者向け教室詳細ページへのQRコード</p>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex flex-col items-center space-y-4">
                            <div class="bg-white p-4 rounded-lg border-2 border-gray-200">
                                {!! $qrCodeSvg !!}
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600 mb-2">利用者向け教室詳細ページ</p>
                                <a href="{{ $userCourseUrl }}" 
                                   target="_blank" 
                                   class="text-sm text-blue-600 hover:text-blue-800 break-all">
                                    {{ $userCourseUrl }}
                                </a>
                            </div>
                        </div>
						<span class="flex justify-end">
							<span class="btn-base btn-update btn-m" onclick="window.print()">
								<i class="fas fa-print mr-2"></i>印刷
							</span>
						</span>
                    </div>
                </div>
            </div>

            <!-- 運営情報（表示のみ） -->
            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">運営情報</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">営業時間</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->business_hours ?? '未設定' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">定休日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->holiday ?? '未設定' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">サービス提供種類</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $classroom->service_type ?? '未設定' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">習い事の種別</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($classroom->lesson_category && $classroom->lesson_category > 0)
                                    @if($classroom->lessonCategoryInfo)
                                        {{ $classroom->lessonCategoryInfo->name }}
                                    @else
                                        ID: {{ $classroom->lesson_category }}（分類データが見つかりません）
                                    @endif
                                @elseif($classroom->lesson_category == -1)
                                    その他
                                    @if($classroom->lesson_category_other)
                                        （{{ $classroom->lesson_category_other }}）
                                    @endif
                                @else
                                    未設定
                                @endif
                            </dd>
                        </div>
                        <!-- contact_person_name, contact_departmentは削除済み -->
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
<div class="print-qr-section">
	<div class="print-service-name">
		{{ $subdomain->system_name ?? '習い事クーポン管理システム' }}
	</div>
	<div class="print-classroom-name">
		{{ $classroom->classroom_name }}
	</div>
	<div class="print-qr-container" style="width: 400px; height: 400px;">
		{!! $qrCodeSvgForPrint !!}
	</div>
</div>


<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- 地図表示共通ライブラリ -->
<script src="{{ asset('js/map.js') }}"></script>
<script>
// 地図初期化（use_map が true の場合のみ）
document.addEventListener('DOMContentLoaded', function() {
    const mapContainer = document.getElementById('classroom-map');
    if (!mapContainer) {
        return;
    }
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
        // 座標がない場合：サブドメインの座標を使用（マーカーなし）
        initialLat = defaultLat;
        initialLng = defaultLng;
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
        onClick: function(lat, lng, marker, map) {
            // 緯度経度をフィールドにセット
            setClassroomMapCoordinates(lat, lng);
        }
    });
});
</script>	
@endsection