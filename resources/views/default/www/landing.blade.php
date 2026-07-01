@extends('default.www.layout')

@section('title', '習い事クーポン管理システム')
@section('body_id', 'home')

@section('content')
		<!-- keyvisual -->
		<section>
			<div id="sec_top" class="kv_container uk-cover-container">
				<!-- Cover / PC -->
			</div>
		</section>
	
		<div class="bg_content_wrap">

			<div class="bg_content_inner">
				
				<!-- news -->
				<section id="sec_news">
					<div class="uk-container uk-margin-large">
						<div class="content_wrap uk-clearfix anime" uk-grid>
							<h2 class="uk-h2 uk-text-center uk-margin-large-bottom"><span class="ttl anime">お知らせ</span></h2>
							<div class="content_inner uk-flex-wrap-stretch">
								<div class="">
									<div class="uk-width-1-1 uk-margin-auto-left uk-margin-auto-right">
										<ul id="notices-list" class="uk-list uk-list-divider">
											@foreach($notices as $notice)
												@include('default.www.partials.notice-item', [
													'url' => route('notices.show', $notice->id),
													'notice_date_display' => $notice->notice_date_display,
													'target_labels' => $notice->target_labels,
													'title' => $notice->title
												])
											@endforeach
										</ul>
									</div>
									<div id="load-more-container" class="uk-width-1-1 uk-width-2-3@m uk-text-center uk-margin-auto" @if($totalNoticesCount <= count($notices)) style="display: none;" @endif>
										<div class="uk-text-center">
											<button id="load-more-btn" type="button" class="uk-button uk-button-primary">続きを見る</button>
											<div id="load-more-loading" class="uk-button uk-button-primary uk-button-primary-loading" style="display: none;">読み込み中...</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>

				<!-- about -->
				<section id="sec_about">
					<div class="uk-container uk-margin-large">
						<div class="content_wrap uk-clearfix anime" style="" uk-grid>
							<h2 class="uk-h2 uk-text-center uk-margin-large-bottom uk-flex-last"><span class="ttl anime">事業について</span></h2>
							<div class="content_inner uk-flex-first">
										<div class="uk-text-center" ><a href="{{ route('about') }}">事業についてはこちら</a></div>
							</div>
						</div>
					</div>
				</section>

				<!-- faq -->
				<section id="sec_faq">
					<div class="uk-container uk-margin-large">
						<div class="content_wrap uk-clearfix anime" uk-grid>
							<h2 class="uk-h2 uk-text-center"><span class="ttl anime">よくある質問</span></h2>
							<div class="content_inner uk-flex uk-flex-column uk-flex-center">
								<a href="{{ route('faq_user') }}" ><span class="btn_icon icn_target_user">対象者<span class="subtext">（保護者）</span></span></a>
								<a href="{{ route('faq_business') }}"><span class="btn_icon icn_target_partner">事業者<span class="subtext">（習い事教室）</span></span></a>
							</div>
						</div>
					</div>
				</section>

				<!-- for_user -->
				<section id="sec_for_user">
					<div class="uk-container uk-margin-large">
						<div class="content_wrap uk-clearfix anime" uk-grid>
							<h2 class="uk-h2 uk-text-center uk-margin-bottom uk-flex-last"><span class="ttl anime">対象者の方へ</span></h2>
							<div class="content_inner uk-flex uk-flex-column uk-flex-first">
								<div class="illust_01"><img src="{{ asset('subdomain_assets/www/images/img_for_user_01.png') }}" width="208" alt=""></div>
								<div class="illust_02"><img src="{{ asset('subdomain_assets/www/images/img_for_user_02.png') }}" width="530" alt=""></div>
								<div class="btn_area_wrap uk-margin-bottom" uk-grid>
									<div class="uk-width-1-1 uk-width-1-2@s">
										<div class="btn_area">
											<div class="btn_area_item uk-width-1-1">
												<div class="uk-text-center" ><a href="{{ route('login') }}"><span class="btn_icon icn_login">ログイン</span></a></div>
											</div>
											<div class="btn_area_item uk-width-1-1">
												<div class="uk-text-center" ><a href="{{ route('manual_user') }}"><span class="btn_icon icn_manual">利用開始までの流れ</span></a></div>
											</div>
										</div>
									</div>
									<div class="uk-width-1-1 uk-width-1-2@s">
										<div class="btn_area">
											<div class="btn_area_item uk-width-1-1">
												<div class="uk-text-center" ><a href="{{ route('course.search') }}"><span class="btn_icon icn_search">習い事検索</span></a></div>
											</div>
											<div class="btn_area_item uk-width-1-1">
												<div class="uk-text-center" ><a href="{{ route('course.request') }}"><span class="btn_icon icn_request">習い事リクエスト</span></a></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>

				<!-- for_partner -->
				<section id="sec_for_partner">
					<div class="uk-container uk-margin-large">
						<div class="content_wrap uk-clearfix anime" uk-grid>
							<h2 class="uk-h2 uk-text-center uk-margin-large-bottom"><span class="ttl anime">対象者の方へ</span></h2>
							<div class="content_inner uk-flex uk-flex-column uk-flex-center">
								<div class="illust_01"><img src="{{ asset('subdomain_assets/www/images/img_dot.png') }}" width="73" alt=""></div>
								<div class="btn_area_wrap" uk-grid>
									<div class="uk-width-1-1 uk-width-1-2@s">
										<div class="btn_area">
											<div class="btn_area_item uk-width-1-1">
												<div class="uk-text-center" ><a href="{{ route('business.login') }}"><span class="btn_icon icn_login">ログイン</span></a></div>
											</div>
											<div class="btn_area_item uk-width-1-1">
												<div class="uk-text-center" ></div>
											</div>
										</div>
									</div>
									<div class="uk-width-1-1 uk-width-1-2@s">
										<div class="btn_area">
											<div class="btn_area_item uk-width-1-1">
												<div class="uk-text-center" ><a href="{{ route('business.registration') }}"><span class="btn_icon icn_partner_register">事業者登録について</span></a></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>

			</div>
		</div>
@endsection

@section('scripts')
@php
    // Bladeパーシャルをプレースホルダー付きでレンダリングしてJavaScriptテンプレートとして使用
    $jsTemplate = view('default.www.partials.notice-item', [
        'url' => '{{url}}',
        'notice_date_display' => '{{notice_date_display}}',
        'target_labels' => [['class' => '{{class}}', 'label' => '{{label}}']],
        'title' => '{{title}}'
    ])->render();
@endphp
<script>
$(document).ready(function() {
    var currentOffset = {{ count($notices) }};
    var isLoading = false;
    var loadMoreBtn = $('#load-more-btn');
    var loadMoreLoading = $('#load-more-loading');
    var loadMoreContainer = $('#load-more-container');
    var noticesList = $('#notices-list');
    var noticeItemTemplate = @json($jsTemplate);
    
    function renderNoticeItem(notice) {
        var labelHtml = '';
        if (notice.target_labels && notice.target_labels.length > 0) {
            notice.target_labels.forEach(function(target) {
                labelHtml += '<span class="' + target.class + '">' + target.label + '</span>';
            });
        }
        
        var html = noticeItemTemplate
            .replace(/\{\{url\}\}/g, notice.url)
            .replace(/\{\{notice_date_display\}\}/g, notice.notice_date_display)
            .replace(/\{\{title\}\}/g, notice.title);
        
        // target_labelsのプレースホルダー部分を置換
        html = html.replace(/<span class="\{\{class\}\}">\{\{label\}\}<\/span>/g, labelHtml);
        
        return html;
    }
    
    loadMoreBtn.on('click', function() {
        if (isLoading) return;
        
        isLoading = true;
        loadMoreBtn.hide();
        loadMoreLoading.show();
        
        $.ajax({
            url: '{{ route("notices.load-more") }}',
            method: 'POST',
            data: {
                offset: currentOffset,
                limit: 2,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function(response) {
                if (response.notices && response.notices.length > 0) {
                    response.notices.forEach(function(notice) {
                        noticesList.append(renderNoticeItem(notice));
                    });
                    
                    currentOffset += response.notices.length;
                }
                
                if (!response.has_more) {
                    loadMoreContainer.hide();
                } else {
                    loadMoreLoading.hide();
                    loadMoreBtn.show();
                }
                
                isLoading = false;
            },
            error: function() {
                loadMoreLoading.hide();
                loadMoreBtn.show();
                isLoading = false;
                alert('お知らせの読み込みに失敗しました。');
            }
        });
    });
});
</script>
@endsection