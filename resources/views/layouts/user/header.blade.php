<!-- 追加（1）-->
<link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link href="https://cdn.jsdelivr.net/npm/daisyui@5/themes.css" rel="stylesheet" type="text/css" />
<script>
	window.addEventListener('DOMContentLoaded', (event) => {
		// チェックボックスを取得してチェックを外す
		const drawerCheckbox = document.getElementById('hdr-drawer');
		if (drawerCheckbox) {
		drawerCheckbox.checked = false;
		}
	});
</script>
<!-- /追加（1） -->
<div class="min-h-screen bg-green-100">
	<!-- ヘッダー -->
	<div class="bg-white shadow" id="header">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
			<div class="flex justify-between items-center pt-1 pb-3 md:py-6">
				<div class="flex items-center">
					<div class="min-w-[170px] mr-2">
						<h1 class="text-xl sm:text-2xl font-bold text-gray-900">利用者マイページ</h1>
						<p><span class="text-xs sm:text-sm text-gray-500">{{ $subdomain->system_name ?? '' }}</span></p>
					</div>
				</div>
				<div  class="flex items-center space-x-4">
					<div class="text-sm text-gray-600 mt-1">
						<div class="text-xs sm:text-sm rounded-lg badge-ghost py-2 px-2 md:px-3">
							@auth
							<div class="bg-white p-1 mb-1 p-1 rounded min-w-[120px] max-w-[200px] md:max-w-xs">
								<p class="text-center"><span class="text-xs sm:text-sm text-gray-500"><strong>{{ auth()->user()->name ?? auth()->user()->login_id }}</strong> 様</span></p>
							</div>
							@if(isset($voucherBalance))
							<p class="text-center mb-1 md:mb-0">
							<span class="text-gray-500 text-xs block md:inline-block align-middle mx-1">クーポン残高</span>
							<span class="font-bold text-green-600 text-lg block md:inline-block align-middle leading-none">¥{{ number_format($voucherBalance) }}</span>
							</p>
							@if(isset($voucherExpiryDate) && $voucherExpiryDate)
							<p class="text-center">
							<span class="text-gray-500 text-xs block md:inline-block align-middle mx-1">有効期限</span>
							<span class="font-bold text-green-600 block md:inline-block align-middle leading-none text-xs">{{ $voucherExpiryDate->format('Y年n月j日') }}</span>
							</p>
							@endif
							@endif
							@endauth							

						</div>
					</div>

					<div>
						<div class="drawer drawer-end">
							<input id="hdr-drawer" type="checkbox" class="drawer-toggle" />
							<div class="drawer-content">
								<label for="hdr-drawer" class="drawer-button btn btn-neutral px-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-5 w-5 stroke-current"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path> </svg></label>
							</div>
							<div class="drawer-side">
								<label for="hdr-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
								<div class="menu bg-base-200 min-h-full w-80 p-4 text-base-content">
									<div class="flex justify-end">
										<label for="hdr-drawer" class="btn btn-ghost btn-sm btn-circle">
										<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
										</svg>
										</label>
									</div>
									<ul class="mt-4">
										<li class="list-row block"><a class="py-3" href="{{ route('user.profile.edit') }}"><i class="fa-solid fa-gear mr-2"></i>設定</a></li>
										<li class="list-row block"><a class="py-3" href="/subdomain_assets/www/pdf/manual_for_user.pdf" target="_blank"><i class="fa-solid fa-gear mr-2"></i>マニュアルダウンロード</a></li>
										<li class="list-row block">
											<form method="POST" action="{{ route('user.logout') }}" class="inline">
												@csrf
												<button type="submit" class="py-3">
												<i class="fas fa-sign-out-alt mr-2"></i>ログアウト
												</button>
											</form>
										</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>


    <!-- Topへスクロールボタン -->
    <button 
        id="scrollToTopBtn" 
        class="fixed bottom-1 right-1 bg-gray-500/70 hover:bg-green-700 text-white w-12 h-12 rounded-lg shadow-lg transition-all duration-300 hidden z-[9999] flex items-center justify-center"
        style="position: fixed; bottom: 2rem; right: 2rem;"
        aria-label="ページトップへ戻る"
    >
        <i class="fas fa-arrow-up text-xl"></i>
    </button>

    <script>
        (function() {
            const scrollToTopBtn = document.getElementById('scrollToTopBtn');
            const scrollThreshold = 100;

            function toggleScrollButton() {
                if (window.pageYOffset > scrollThreshold) {
                    scrollToTopBtn.classList.remove('hidden');
                    scrollToTopBtn.classList.add('opacity-100');
                } else {
                    scrollToTopBtn.classList.add('hidden');
                    scrollToTopBtn.classList.remove('opacity-100');
                }
            }

            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // DOMContentLoadedで初期化
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    toggleScrollButton();
                });
            } else {
                toggleScrollButton();
            }

            // スクロールイベントリスナー
            window.addEventListener('scroll', toggleScrollButton);

            // ボタンクリックイベント
            scrollToTopBtn.addEventListener('click', scrollToTop);
        })();
    </script>
