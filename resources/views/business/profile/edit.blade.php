@extends('layouts.app')

@section('title', '事業者情報管理 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>設定</span></li>
            </ol>
        </nav>		
	
    <!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
        <!-- 成功メッセージ（一定時間でフェードアウト） -->
        @if(session('success'))
            <div id="profile-success-alert" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert" style="opacity: 1; transition: opacity 0.5s ease-out;">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
            <script>
                (function() {
                    var el = document.getElementById('profile-success-alert');
                    if (!el) return;
                    setTimeout(function() {
                        el.style.opacity = '0';
                        el.addEventListener('transitionend', function onEnd() {
                            el.removeEventListener('transitionend', onEnd);
                            el.style.display = 'none';
                        });
                    }, 5000);
                })();
            </script>
        @endif

        <!-- エラーメッセージ -->
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="px-4 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <!-- メールアドレス表示 -->
                    <div class="mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">メールアドレス</h2>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">現在のメールアドレス</p>
                                <p class="text-base font-medium text-gray-900">{{ $user->email }}</p>
                            </div>
                            <a href="{{ route('business.profile.edit.email') }}" 
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-edit mr-2"></i>メールアドレスを変更
                            </a>
                        </div>
                    </div>

                    @if($businessInfo ?? null)
                    <!-- メール通知設定 -->
                    <div class="border-t border-gray-200 pt-6 pb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">メール通知設定</h2>
                        <p class="text-sm text-gray-500 mb-4">クーポン受付メールの通知設定</p>
                        <form method="POST" action="{{ route('business.profile.update.notification') }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            @php
                                $currentFrequency = \App\Enums\CouponNotificationFrequency::tryFrom($businessInfo->email_timing) ?? \App\Enums\CouponNotificationFrequency::Immediate;
                            @endphp
                            <div class="space-y-2 flex justify-between">
                                @foreach(\App\Enums\CouponNotificationFrequency::all() as $frequency)
                                <div class="flex items-center">
                                    <input type="radio" name="email_timing" id="email_timing_{{ $frequency->value }}" value="{{ $frequency->value }}"
                                        {{ $currentFrequency === $frequency ? 'checked' : '' }}
                                        class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                    <label for="email_timing_{{ $frequency->value }}" class="ml-2 block text-sm text-gray-900">{{ $frequency->label() }}</label>
                                </div>
                                @endforeach
								<button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex justify-end">
									<i class="fas fa-save mr-2"></i>保存
								</button>
							</div>
						</form>
                    </div>
                    @endif

                    <!-- パスワード変更 -->
                    <div class="border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">パスワード</h2>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">パスワードを変更する</p>
                            </div>
                            <a href="{{ route('business.profile.edit.password') }}" 
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-key mr-2"></i>パスワードを変更
                            </a>
                        </div>
                    </div>

                    <!-- マニュアルダウンロード -->
                    <div class="border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">マニュアルダウンロード</h2>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">マニュアルをダウンロードする（PDFファイル）</p>
                            </div>
                            <a href="/subdomain_assets/www/pdf/manual_for_business.pdf" target="_blank" 
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-key mr-2"></i>マニュアルダウンロード
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

