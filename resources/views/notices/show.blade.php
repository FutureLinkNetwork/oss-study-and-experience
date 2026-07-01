@extends('layouts.app')

@section('title', $notice->title . ' - お知らせ詳細 - 習い事クーポン管理システム')

@section('content')
<!-- controllerが事業者か利用者か判別がつくように背景色を変える -->
 @if($type == 'Business')
 <div class="min-h-screen bg-blue-100">
 @elseif($type == 'User')
 <div class="min-h-screen bg-green-100">
 @endif
    <!-- メインコンテンツ -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">{{ $notice->title }}</h2>
                </div>
                <div class="px-6 py-4 space-y-6">
                    <!-- お知らせ日付 -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">お知らせ日付</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $notice->notice_date_display }}</dd>
                    </div>

                    <!-- 内容 -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-2">内容</dt>
                        <dd class="mt-1 text-sm text-gray-900">{!! nl2br(e($notice->content)) !!}</dd>
                    </div>

                    <!-- リンクURL -->
                    @if($notice->link_url)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-2">関連リンク</dt>
                        <dd class="mt-1">
						@if($type == 'Business')
						<a href="{{ $notice->link_url }}" target="_blank" rel="noopener noreferrer" class="btn-base btn-create btn-m">
						@elseif($type == 'User')
						<a href="{{ $notice->link_url }}" target="_blank" rel="noopener noreferrer" class="btn-base btn-search btn-m">
						@endif

                                <i class="fas fa-external-link-alt mr-2"></i>詳細はこちら
                            </a>
                        </dd>
                    </div>
                    @endif

                    <!-- 添付ファイル -->
                    @if($notice->hasAttachment())
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-2">添付ファイル</dt>
                        <dd class="mt-1">
                            @if($type == 'Business')
                            <a href="{{ route('business.notices.attachment.download', $notice->id) }}" class="btn-base btn-create btn-m" download>
                            @elseif($type == 'User')
                            <a href="{{ route('user.notices.attachment.download', $notice->id) }}" class="btn-base btn-search btn-m" download>
                            @endif
                                <i class="fas fa-download mr-2"></i>{{ $notice->attachment_original_filename }}
                            </a>
                        </dd>
                    </div>
                    @endif

                    <!-- 住所 -->
                    @if($notice->address)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">住所</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $notice->address }}</dd>
                    </div>
                    @endif

                    <!-- 地図表示（位置情報がある場合のみ） -->
                    @if($notice->hasLocation())
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-2">地図</dt>
                        <dd class="mt-1">
                            <div id="map" style="height: 450px; width: 100%; border: 1px solid #ccc; border-radius: 0.375rem;"></div>
                        </dd>
                    </div>
                    @endif
                </div>
            </div>
            <!-- 戻るボタン -->
            <div class="mt-6 flex justify-center" id="pagination">
				@if($type == 'Business')
                <a href="{{ route('business.dashboard') }}" class="btn-base btn-back btn-m">
                    <i class="fas fa-arrow-left mr-2"></i>トップ画面に戻る
                </a>
				@elseif($type == 'User')
				<a href="{{ route('user.dashboard') }}" class="btn-base btn-back btn-m">
					<i class="fas fa-arrow-left mr-2"></i>トップ画面に戻る
				</a>
				@endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($notice->hasLocation())
<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="{{ asset('js/map.js') }}"></script>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // 地図を初期化（読み取り専用）
    initMap({
        latitude: {{ $notice->latitude }},
        longitude: {{ $notice->longitude }},
        zoom: 17,
        showMarker: true,
        popupContent: @if($notice->address){!! json_encode($notice->address, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}@else null @endif,
        enableClick: false // クリックイベントは無効
    });
});
</script>
@endif
@endpush

