<!DOCTYPE html>
<html>
	<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# website: http://ogp.me/ns/website#">
		<title>@yield('title', '習い事クーポン管理システム')</title>
		<meta charset="utf-8">
		<link rel="icon" href="{{ asset('subdomain_assets/www/images/favicon.ico') }}">
		<meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
		<meta property="og:url" content="">
		<meta property="og:type" content="website">
		<meta property="og:image" content="">
		<meta property="og:title" content="@yield('title', '習い事クーポン管理システム')">
		<meta property="og:description" content="">
		<meta property="og:locale" content="ja_JP">
		<link rel="stylesheet" href="{{ asset('subdomain_assets/www/css/uikit.min.css') }}" />
		<link rel="stylesheet" href="{{ asset('subdomain_assets/www/css/style.css') }}" />
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&display=swap" rel="stylesheet">
	</head>
    
	<body id="@yield('body_id', 'other')">
		<header>
			<div class="inner">
				<nav class="uk-navbar-container uk-padding uk-padding-remove-vertical uk-padding-remove-horizontal uk-margin-remove-left@s" uk-navbar  uk-sticky>
					<div class="uk-navbar-left">
						<h1><a class="uk-navbar-item uk-logo" href="/">習い事クーポン管理システム</a></h1>
					</div>
					<div class="uk-navbar-right">
						<!-- menu / pc -->
						<div class="uk-visible@l">
							<ul class="uk-navbar-nav">
								<li>
									<a href="{{ route('welcome') }}#sec_news">
										<div class="uk-flex uk-flex-column uk-flex-middle">
											<span class="nav-icon nav-icon-news uk-margin-small-bottom"></span>
											<span class="uk-text-small uk-text-center">お知らせ</span>
										</div>
									</a>
								</li>
								<li>
									<a href="{{ route('welcome') }}#sec_about">
										<div class="uk-flex uk-flex-column uk-flex-middle">
											<span class="nav-icon nav-icon-about uk-margin-small-bottom"></span>
											<span class="uk-text-small uk-text-center">事業について</span>
										</div>
									</a>
								</li>
								<li>
									<a href="{{ route('welcome') }}#sec_faq">
										<div class="uk-flex uk-flex-column uk-flex-middle">
											<span class="nav-icon nav-icon-faq uk-margin-small-bottom"></span>
											<span class="uk-text-small uk-text-center">よくある質問</span>
										</div>
									</a>
								</li>
								<li>
									<a href="{{ route('welcome') }}#sec_for_user">
										<div class="uk-flex uk-flex-column uk-flex-middle">
											<span class="nav-icon nav-icon-for-user uk-margin-small-bottom"></span>
											<span class="uk-text-small uk-text-center">対象者の方へ</span>
										</div>
									</a>
								</li>
								<li>
									<a href="{{ route('course.search') }}">
										<div class="uk-flex uk-flex-column uk-flex-middle">
											<span class="nav-icon nav-icon-search uk-margin-small-bottom"></span>
											<span class="uk-text-small uk-text-center">習い事検索</span>
										</div>
									</a>
								</li>
								<li>
									<a href="{{ route('welcome') }}#sec_for_partner">
										<div class="uk-flex uk-flex-column uk-flex-middle">
											<span class="nav-icon nav-icon-for-partner uk-margin-small-bottom"></span>
											<span class="uk-text-small uk-text-center">事業者の方へ</span>
										</div>
									</a>
								</li>
								<li>
									<a href="{{ route('contact') }}">
										<div class="uk-flex uk-flex-column uk-flex-middle">
											<span class="nav-icon nav-icon-contact uk-margin-small-bottom"></span>
											<span class="uk-text-small uk-text-center">お問い合わせ</span>
										</div>
									</a>
								</li>
							</ul>
						</div>
						<!-- menu / mobile -->
						<div class="uk-hidden@l">
							<a class="uk-navbar-toggle uk-icon-button" uk-toggle uk-navbar-toggle-icon href="#menu-top-modal"></a>
							<div id="menu-top-modal" class="uk-modal-full uk-modal-container" uk-modal>
								<div class="uk-modal-dialog uk-margin-auto-bottom menu-top-bar">
									<button class="uk-modal-close-full uk-close-large" type="button" uk-close></button>
									<div class="uk-grid-collapse uk-child-width-1-1 uk-flex-middle" uk-grid>
										<div class="uk-padding-small uk-padding-remove-top">
											<ul class="uk-nav uk-nav-primary uk-nav-center" uk-nav>
												<li>
													<a href="{{ route('welcome') }}#sec_news" uk-toggle="!#menu-top-modal">
														<div class="uk-flex uk-flex-left uk-flex-middle">
															<span class="nav-icon nav-icon-news uk-margin-small-right"></span>
															<span class="uk-text-small uk-text-center">お知らせ</span>
														</div>
													</a>
												</li>
												<li>
													<a href="{{ route('welcome') }}#sec_about" uk-toggle="!#menu-top-modal">
														<div class="uk-flex uk-flex-left uk-flex-middle">
															<span class="nav-icon nav-icon-about uk-margin-small-right"></span>
															<span class="uk-text-small uk-text-center">事業について</span>
														</div>
													</a>
												</li>
												<li>
													<a href="{{ route('welcome') }}#sec_faq" uk-toggle="!#menu-top-modal">
														<div class="uk-flex uk-flex-left uk-flex-middle">
															<span class="nav-icon nav-icon-faq uk-margin-small-right"></span>
															<span class="uk-text-small uk-text-center">よくある質問</span>
														</div>
													</a>
												</li>
												<li>
													<a href="{{ route('welcome') }}#sec_for_user" uk-toggle="!#menu-top-modal">
														<div class="uk-flex uk-flex-left uk-flex-middle">
															<span class="nav-icon nav-icon-for-user uk-margin-small-right"></span>
															<span class="uk-text-small uk-text-center">対象者の方へ</span>
														</div>
													</a>
												</li>
												<li>
													<a href="{{ route('welcome') }}#sec_for_partner" uk-toggle="!#menu-top-modal">
														<div class="uk-flex uk-flex-left uk-flex-middle">
															<span class="nav-icon nav-icon-for-partner uk-margin-small-right"></span>
															<span class="uk-text-small uk-text-center">事業者の方へ</span>
														</div>
													</a>
												</li>
												<li>
													<a href="{{ route('contact') }}" uk-toggle="!#menu-top-modal">
														<div class="uk-flex uk-flex-left uk-flex-middle">
															<span class="nav-icon nav-icon-contact uk-margin-small-right"></span>
															<span class="uk-text-small uk-text-center">お問い合わせ</span>
														</div>
													</a>
												</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>

					</div>
				</nav>
			</div>
		</header>

        @yield('content')

		<footer>
			<section>
				<div class="sec_footer">
					<div class="content_wrap uk-container uk-clearfix">
						<div class="clearfix">
							<div class="uk-width-1-1 uk-text-center">
								<p class="ftr_logo uk-text-center uk-align-center">習い事クーポン管理システム</p>
								<p class="ftr_info">習い事クーポン管理システム<br>
								営業時間　平日x：00〜xx：00<br>（祝日、休日及び年末年始を除く）
								</p>
								<p class="ftr_text">
								
								事業主体　FLN市<br>
								システム提供　株式会社フューチャーリンクネットワーク
								</p>
							</div>
						</div>
						<div class="copyright">
							<div class="uk-container uk-width-1-1 uk-padding">
								<p class="ftr_copy uk-text-small uk-text-center">&copy; 習い事クーポン管理システム</p>
							</div>
						</div>
					</div>
				</div>
			</section>
		</footer>

        <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        
        <script src="{{ asset('subdomain_assets/www/js/uikit.min.js') }}"></script>
        <script src="{{ asset('subdomain_assets/www/js/uikit-icons.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.4.2/gsap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.4.2/ScrollTrigger.min.js"></script>
        <script src="{{ asset('subdomain_assets/www/js/base.js') }}"></script>
        
        @yield('scripts')
    </body>
</html>