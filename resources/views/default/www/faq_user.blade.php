@extends('default.www.layout')

@section('title', 'よくある質問｜対象者（保護者）')
@section('body_id', 'other')

@section('content')

		
		<div class="bg_content_wrap">
			<div class="bg_content_inner">

				<!-- article -->
				<section id="sec_article">
					<!-- breadcrumb -->
					<ul class="uk-breadcrumb">
						<li><a href="./">TOP</a></li>
						<li><span>よくある質問｜対象者（保護者）</span></li>
					</ul>
				<!-- faq -->
					<div class="uk-container uk-margin-large">
						<h2 class="uk-h2 uk-text-center"><span class="ttl anime">よくある質問</span></h2>
						<div class="content_wrap uk-clearfix anime" uk-grid>
							<div class="content_inner uk-flex uk-flex-column uk-flex-center">
								<h3 class="sub_ttl"><span class="btn_icon icn_target_user">対象者<span class="subtext">（保護者）</span></span></h3>
								<!-- toc_list -->
								<!--div class="toc_list">
									<h4><img src="{{ asset('subdomain_assets/www/images/sttl_toc.png') }}" width="90" alt="目次"></h4>
									<div class="uk-child-width-1-2@m" uk-grid>
										<div>
											<ul class="uk-list">
												<li><a href="#sec_cat001" class="list-link">あああああああああ</a></li>
												<li><a href="#sec_cat002" class="list-link">いいいいいいいいい</a></li>
											</ul>
										</div>
										<div>
											<ul class="uk-list">
												<li><a href="#sec_cat003" class="list-link">ううううううううう</a></li>
												<li><a href="#sec_cat004" class="list-link">えええええええええ</a></li>
											</ul>
										</div>
									</div>
								</div-->
								<!-- / toc_list -->
								<!-- adn_list -->
								<div class="adn_list">
									<!--h4><span>あああああああああ</span></h4-->
									<ul uk-accordion="multiple: true" class="uk-accordion">
										<li ID="item01">
											<a class="uk-accordion-title" href="">【1】Question 1</a>
											<div class="uk-accordion-content" hidden="">
												<p class="uk-text-large">
													Answer 1
												</p>
											</div>
										</li>
										<li ID="item02">
											<a class="uk-accordion-title" href="">【2】Question 2</a>
											<div class="uk-accordion-content" hidden="">
												<p class="uk-text-large">
													Answer 2
												</p>
											</div>
										</li>
									</ul>
								</div>
								<!-- / adn_list -->

							</div>
						</div>
					</div>
				</section>
			</div>
		</div>
	<script>
			window.addEventListener('load', () => {
			// 1. URLのハッシュ（#item1 など）を取得
			const hash = window.location.hash;

			if (hash) {
			// 2. 指定されたIDの要素を探す
			const target = document.querySelector(hash);

			// 3. 要素が存在し、かつUIkitアコーディオン内にある場合
			if (target && target.closest('[uk-accordion]')) {
			const accordionElement = target.closest('[uk-accordion]');

			// 全ての項目(li)の中から、対象が何番目かを探す
			const items = Array.from(accordionElement.children);
			const index = items.indexOf(target);

			if (index !== -1) {
			// UIkitのAPIを使って強制的に開く（trueはアニメーションなしの指定）
			UIkit.accordion(accordionElement).toggle(index, false);
			}
			}
			}
			});
	</script>
	<style>
			/* ページ内リンクのずれ調整 */
			.uk-accordion li[id] {
			scroll-margin-top: 160px;
			}
			@media (max-width: 960px){
			.uk-accordion li[id] {
			scroll-margin-top: 100px;
			}
			}
</style>
@endsection

