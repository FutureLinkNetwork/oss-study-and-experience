@extends('layouts.app')

@section('title', $pageTitle)

@section('head')

@endsection

@section('content')

<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('admin.notices.index') }}" class="hover:text-gray-700">お知らせ管理</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>{{ $pageTitle }}</span></li>
            </ol>
        </nav>		
	
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="space-y-6" enctype="multipart/form-data">
		<input type="hidden" name="subdomain_id" value="{{ $subdomain_id }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">基本情報</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="field-label">タイトル 
                        <span class="text-red-500">*</span>
                    </label>
                        <input type="text" name="title" value="{{ old('title', $notice->title ?? '') }}" 
                               maxlength="255" 
                               class="field-base field-normal w-full" placeholder="お知らせのタイトルを入力">
                </div>

                <div class="md:col-span-2">
                    <label class="field-label">内容 
                        <span class="text-red-500">*</span>
                    </label>
                        <textarea name="content" rows="10" 
                                  class="field-base w-full" placeholder="お知らせの内容を入力">{{ old('content', $notice->content ?? '') }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="field-label">お知らせ日付 
                        <span class="text-red-500">*</span>
                    </label>
                        <input type="date" name="notice_date" 
                               value="{{ old('notice_date', $notice ? $notice->notice_date->format('Y-m-d') : now()->format('Y-m-d')) }}" 
                            	class="field-base field-w-30">
                </div>

                <div class="md:col-span-2">
                    <label class="field-label">リンクURL</label>
                        <input type="url" name="link_url" value="{{ old('link_url', $notice->link_url ?? '') }}" 
                               maxlength="1000" 
                               class="field-base field-w-30" placeholder="https://example.com">
                </div>

                <div class="md:col-span-2">
                    <label class="field-label">添付ファイル（任意）</label>
                    <p class="text-sm text-gray-500 mt-1 mb-2">PDF、Word（.doc / .docx）、Excel（.xls / .xlsx）、8MBまで</p>
                    @if($isEdit && $notice && $notice->hasAttachment())
                        <div class="mb-3 p-3 bg-gray-50 rounded border border-gray-200">
                            <p class="text-sm text-gray-700">
                                現在の添付: <a href="{{ route('admin.notices.attachment.download', $notice) }}" class="text-blue-600 hover:underline" target="_blank" rel="noopener">{{ $notice->attachment_original_filename }}</a>
                            </p>
                            <label class="inline-flex items-center mt-2">
                                <input type="checkbox" name="attachment_remove" value="1" class="check-normal">
                                <span class="ml-2 text-sm text-gray-600">添付を削除する</span>
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 mb-1">新しいファイルを選択すると、上記の添付を差し替えます。</p>
                    @endif
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="field-base w-full max-w-md">
                </div>
            </div>

			<h2 class="text-lg font-semibold text-gray-900 mt-6 mb-4">公開設定</h2>
			
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="field-label">公開開始日時</label>
                        <input type="datetime-local" name="publish_start_at" 
                               value="{{ old('publish_start_at', $notice && $notice->publish_start_at ? $notice->publish_start_at->format('Y-m-d\TH:i') : '') }}" 
                               class="field-base  field-w-30">
                        <p class="text-sm text-gray-500 mt-1">指定しない場合は即時公開</p>
                </div>

                <div>
                    <label class="field-label">公開終了日時</label>
                        <input type="datetime-local" name="publish_end_at" 
                               value="{{ old('publish_end_at', $notice && $notice->publish_end_at ? $notice->publish_end_at->format('Y-m-d\TH:i') : '') }}" 
                               class="field-base  field-w-30">
                        <p class="text-sm text-gray-500 mt-1">指定しない場合は無期限公開</p>
                </div>
            </div>

			<h2 class="text-lg font-semibold text-gray-900 mt-6 mb-4">表示設定</h2>
            
            <div class="check-group">
                <div class="check-item">
                    <input type="checkbox" id="show_on_public" name="show_on_public" value="1" 
                           {{ old('show_on_public', $notice->show_on_public ?? false) ? 'checked' : '' }} 
                           class="check-normal">
                    <label for="show_on_public" class="check-label">LPに表示</label>
                </div>
                
                <div class="check-item">
                    <input type="checkbox" id="show_on_user_dashboard" name="show_on_user_dashboard" value="1" 
                           {{ old('show_on_user_dashboard', $notice->show_on_user_dashboard ?? false) ? 'checked' : '' }} 
                           class="check-normal">
                    <label for="show_on_user_dashboard" class="check-label">利用者ダッシュボードに表示</label>
                </div>
                
                <div class="check-item">
                    <input type="checkbox" id="show_on_business_dashboard" name="show_on_business_dashboard" value="1" 
                           {{ old('show_on_business_dashboard', $notice->show_on_business_dashboard ?? false) ? 'checked' : '' }} 
                           class="check-normal">
                    <label for="show_on_business_dashboard" class="check-label">事業者ダッシュボードに表示</label>
                </div>
            </div>

			<h2 class="text-lg font-semibold text-gray-900 mt-6 mb-4">位置情報設定</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="field-label">住所</label>
                        <div class="flex gap-2">
                            <input type="text" id="address" name="address" 
                                   value="{{ old('address', $notice->address ?? '') }}" 
                                   maxlength="500" 
                                   class="field-base field-normal flex-1" 
                                   placeholder="住所を入力してください">
                        </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">緯度</label>
                            <input type="number" id="latitude" name="latitude" 
                                   value="{{ old('latitude', $notice->latitude ?? '') }}" 
                                   step="0.00000001" min="-90" max="90" 
                                   class="field-base field-normal" readonly>
                    </div>
                    
                    <div>
                        <label class="field-label">経度</label>
                            <input type="number" id="longitude" name="longitude" 
                                   value="{{ old('longitude', $notice->longitude ?? '') }}" 
                                   step="0.00000001" min="-180" max="180" 
                                   class="field-base field-normal" readonly>
                    </div>
                </div>

                <!-- 地図表示エリア（詳細画面用） -->
                <div class="mt-4">
                    <label class="field-label">地図</label>
                    <div id="map" class="mt-2" style="height: 400px; width: 100%; border: 1px solid #ccc;"></div>
                </div>
            </div>

        <!-- 送信ボタン -->
        <div class="flex justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
            <button type="submit" class="btn-base {{ $isEdit ? 'btn-update' : 'btn-create' }} btn-m">
                <i class="fas fa-save mr-2"></i>{{ $isEdit ? '更新' : '作成' }}
            </button>

			<a href="{{ route('admin.notices.index') }}" class="btn-base btn-cancel btn-m">
                キャンセル
            </a>
    </form>


			@if($isEdit)
			<!-- 削除ボタン（メインフォームとは別） -->
				<form method="POST" action="{{ route('admin.notices.destroy', $notice) }}" style="display: inline;" 
					onsubmit="return confirm('このお知らせを削除してもよろしいですか？');">
					@csrf
					@method('DELETE')
					<button type="submit" class="btn-base btn-disable btn-m">
						<i class="fas fa-trash mr-2"></i>削除
					</button>
				</form>
			@endif
        </div>
	</div>
</div>

<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="{{ asset('js/map.js') }}"></script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // 地図を初期化（編集モード）
    initMap({
        latitude: {{ $latitude }},
        longitude: {{ $longitude }},
        zoom: 17,
        showMarker: {{ $isEdit ? 'true' : 'false' }},
        enableClick: true, // クリックイベントを有効化
        onClick: function(lat, lng, marker, map) {
            // フォームフィールドに座標を設定
            setMapCoordinates(lat, lng);
        }
    });
});
</script>

@endsection