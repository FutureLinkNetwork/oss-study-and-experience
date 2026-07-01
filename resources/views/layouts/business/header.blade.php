<div class="min-h-screen bg-blue-100">
    <!-- ヘッダー -->
    <div class="bg-white shadow no-print" id="header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900"><a href="{{route('business.dashboard')}}">事業者マイページ｜{{ $subdomain->system_name ?? '' }}</a></h1>
                        @auth
                        <p class="text-sm text-gray-500">{{ auth()->user()->name ?? auth()->user()->login_id }} 様</p>
                        @endauth
                    </div>
                </div>
				<div class="flex items-center space-x-4">
				<form method="POST" action="{{ route('business.logout') }}" class="inline">
					@csrf
					<button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
						<i class="fas fa-sign-out-alt mr-2"></i>ログアウト
					</button>
				</form>
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