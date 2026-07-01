@extends('default.www.layout')

@section('title', $notice->title.' ｜ お知らせ ｜ 習い事クーポン管理システム')
@section('body_id', 'other')

@section('content')
<style>
	.uk-button-default {
		width: auto !important;
		padding:10px 40px !important;
	}
</style>
		<div class="bg_content_wrap">
			<div class="bg_content_inner">

				<!-- article -->
				<section id="sec_article">
				<!-- breadcrumb -->
				<ul class="uk-breadcrumb">
					<li><a href="{{ route('welcome') }}">TOP</a></li>
					<li><span>{{ $notice->title }}</span></li>
				</ul>

					<div class="uk-container">
						<h2 class="uk-h2 uk-text-center"><span class="ttl anime">お知らせ</span></h2>
						<div class="content_wrap uk-clearfix anime" uk-grid>
							<div class="content_inner uk-flex-wrap-stretch">
								<div class="illust_01 other_news"><img src="{{ asset('subdomain_assets/www/images/img_about_01.png') }}" width="165" alt=""></div>

								<div class="uk-margin-large-bottom">
									<!-- タイトル（見出し） -->
									<div class="uk-h3 uk-flex uk-flex-between uk-flex-stretch">
										<h3 class="uk-margin-remove-bottom uk-width-expand">{{ $notice->title }}</h3> 
									</div>
									<!-- / タイトル（見出し） -->
								
									<!-- お知らせ日付 -->
									<div class="uk-margin-small-top">
										<span class="date">{{ $notice->notice_date_display }}</span>
									</div>
									<!-- / お知らせ日付 -->
								
									<!-- 本文 -->
									<div class="uk-margin-medium-top">
										{!! nl2br(e($notice->content)) !!}
									</div>
									<!-- / 本文 -->
								
									<!-- 添付ファイル -->
									@if($notice->hasAttachment())
									<div class="uk-margin-medium-top">
										<a href="{{ route('notices.attachment.download', $notice->id) }}" download>
											<i class="uk-margin-small-right" uk-icon="icon: download"></i>{{ $notice->attachment_original_filename }}
										</a>
									</div>
									@endif
									<!-- / 添付ファイル -->

									<!-- リンクURL -->
									@if($notice->link_url)
									<div class="uk-margin-medium-top">
										<a href="{{ $notice->link_url }}" target="_blank" rel="noopener noreferrer" class="uk-button uk-button-primary">
											詳細はこちら
										</a>
									</div>
									@endif
									<!-- / リンクURL -->

                                    <!-- 地図表示（位置情報がある場合のみ） -->
                                    @if($notice->hasLocation())
                                    <div class="uk-margin-large-top">
                                        <div id="map" style="height: 450px; width: 100%; border: 1px solid #ccc;"></div>
                                    </div>
                                    <!-- / 地図表示 -->
                                    @endif
								
								</div>
								

                                <div class="uk-width-1-1 uk-width-2-3@m uk-text-center uk-margin-auto">
                                    <div class="uk-text-center" ><a href="{{ route('welcome') }}"  class="uk-button uk-button-primary">戻る</a></div>
                                </div>

							</div>
						</div>
					</div>
				</section>

			</div>
		</div>

@section('scripts')
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
@endsection
@endsection