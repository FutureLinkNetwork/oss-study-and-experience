<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
	<link rel="icon" href="{{ asset('common/images/favicon.ico') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '習い事クーポン管理システム')</title>
    
    <!-- Vite Assets -->
    @if(app()->environment('local') && file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @else
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $cssFile = $manifest['resources/css/app.css']['file'] ?? 'assets/app.css';
        @endphp
        <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
    @endif
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Select2 カスタムスタイル -->
    <style>
    .select2-container {
        width: 100% !important;
    }
    
    .select2-container--default .select2-selection--single {
        height: auto;
        min-height: 2.5rem;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        background-color: #ffffff;
        color: #111827;
        font-size: 0.875rem;
        line-height: 1.25rem;
        transition: all 0.15s ease-in-out;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #111827;
        line-height: normal;
        padding: 0;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6b7280;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        right: 0.5rem;
    }
    
    .select2-dropdown {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #3b82f6;
        color: white;
    }
    
    .select2-search--dropdown .select2-search__field {
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    </style>
	@stack('styles')
	</head>
<body class="bg-gray-50">
    @auth
		<!-- roleによってヘッダーを表示する -->
		@if(auth()->user()->role->name === 'subdomain_admin' || auth()->user()->role->name === 'super_admin' || auth()->user()->role->name === 'subdomain_operator' || auth()->user()->role->name === 'subdomain_viewer')
			@include('layouts.admin.header')
		@elseif(auth()->user()->role->name === 'subdomain_business')
			@include('layouts.business.header')
		@elseif(auth()->user()->role->name === 'subdomain_user')
			@include('layouts.user.header')
		@endif
	@endauth

    <!-- メインコンテンツ -->
    <main class="@auth py-6 @endauth">
		@yield('content')
    </main>

    <!-- アラートメッセージ -->
    @if(session('success'))
    <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded shadow-lg z-50">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded shadow-lg z-50">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <!-- JavaScript -->
    <script>
        // アラートを5秒後に自動で閉じる
        setTimeout(function() {
            document.querySelectorAll('.fixed.top-4.right-4').forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
    
    <!-- jQuery and Select2 Scripts -->
    <script>
        // jQueryを動的に読み込み
        (function() {
            const script = document.createElement('script');
            script.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
            script.integrity = 'sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=';
            script.crossOrigin = 'anonymous';
            
            script.onload = function() {
                if (typeof jQuery !== 'undefined' && jQuery && typeof jQuery.fn !== 'undefined') {
                    window.$ = jQuery;
                    window.jQuery = jQuery;
                    
                    // Select2を読み込み
                    loadSelect2();
                }
            };
            
            document.head.appendChild(script);
            
            function triggerSelect2ReadyEvent() {
                if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                    // DOMContentLoadedで初期化
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            $(document).trigger('select2Ready');
                        });
                    } else {
                        $(document).trigger('select2Ready');
                    }
                }
            }
            
            function loadSelect2() {
                const select2Script = document.createElement('script');
                select2Script.src = 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js';
                
                select2Script.onload = function() {
                    // 初期化イベントを発火
                    triggerSelect2ReadyEvent();
                };
                
                document.head.appendChild(select2Script);
            }
        })();
    </script>
    
    <!-- Vite JavaScript (after jQuery/Select2) -->
    @if(!app()->environment('local') || !file_exists(public_path('hot')))
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $jsFile = $manifest['resources/js/app.js']['file'] ?? 'assets/app.js';
        @endphp
        <script src="{{ asset('build/' . $jsFile) }}" defer></script>
    @endif

    @stack('scripts')
</body>
</html>