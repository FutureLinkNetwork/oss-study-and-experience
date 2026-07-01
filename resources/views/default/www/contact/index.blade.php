@extends('default.www.layout')

@section('title', 'お問い合わせ ｜ 習い事クーポン管理システム')
@section('body_id', 'other')

@section('content')
<style>
.uk-container a {
    text-decoration: none;
}
</style>
<div class="bg_content_wrap">
    <div class="bg_content_inner">
        <section id="sec_article">
            <!-- breadcrumb -->
            <ul class="uk-breadcrumb">
                <li><a href="/">TOP</a></li>
                <li><span>お問い合わせ</span></li>
            </ul>
            <div class="uk-container uk-margin-large">
			<h2 class="uk-h2 uk-text-center"><span class="ttl anime"><img src="{{ asset('subdomain_assets/www/images/ttl_inquiry_h.png') }}" alt="お問い合わせ"></span></h2>
				<div class="content_wrap uk-clearfix anime" uk-grid>
					<div class="content_inner uk-flex-wrap-stretch">
						<div class="illust_01 other_news"><img src="{{ asset('subdomain_assets/www/images/img_about_01.png') }}" width="165" alt=""></div>
                        <!-- 成功メッセージ -->
                        @if(session('success'))
                            <div class="uk-alert-success" uk-alert>
                                <a class="uk-alert-close" uk-close></a>
                                <p>{{ session('success') }}</p>
                            </div>
                        @endif

                        <!-- フォーム -->
                        <form method="POST" action="{{ route('contact.store') }}" class="uk-form-stacked" id="contact-form">
                            @csrf
                            @if(config('recaptcha.enabled'))
                                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
                            @endif
                            <div class="uk-grid-small" uk-grid>
                                <!-- 名前 -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold" for="name">
                                        お名前・教室名 <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input class="uk-input @error('name') uk-form-danger @enderror" 
                                               type="text" 
                                               id="name" 
                                               name="name" 
                                               value="{{ old('name') }}" 
                                               placeholder="お名前を入力してください"
                                               required>
                                        @error('name')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- メールアドレス -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="email">
                                        メールアドレス <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input class="uk-input @error('email') uk-form-danger @enderror" 
                                               type="email" 
                                               id="email" 
                                               name="email" 
                                               value="{{ old('email') }}" 
                                               placeholder="メールアドレスを入力してください"
                                               required>
                                        @error('email')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- 電話番号 -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="phone">
                                        電話番号 <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input class="uk-input @error('phone') uk-form-danger @enderror" 
                                               type="tel" 
                                               id="phone" 
                                               name="phone" 
                                               value="{{ old('phone') }}" 
                                               placeholder="電話番号を入力してください"
                                               required>
                                        @error('phone')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- 問い合わせ内容 -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="content">
                                        問い合わせ内容 <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <textarea class="uk-textarea @error('content') uk-form-danger @enderror" 
                                                  id="content" 
                                                  name="content" 
                                                  rows="6" 
                                                  placeholder="問い合わせ内容を入力してください"
                                                  required>{{ old('content') }}</textarea>
                                        @error('content')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- プライバシーポリシーに同意 -->
                                <div class="uk-width-1-1">
                                    <div class="uk-form-controls uk-margin-top">
                                        <label class="uk-flex uk-flex-middle">
                                            <input class="uk-checkbox @error('privacy_consent') uk-form-danger @enderror" 
                                                   type="checkbox" 
                                                   name="privacy_consent" 
                                                   value="1"
                                                   {{ old('privacy_consent') ? 'checked' : '' }}
                                                   required>
                                            <span class="uk-margin-small-left">
                                                <a href="{{ route('privacy_policy') }}" target="_blank" rel="noopener noreferrer" style="color:#0000FF !important; text-decoration:underline !important;">プライバシーポリシー</a>に同意します <span class="uk-text-danger">*</span>
                                            </span>
                                        </label>
                                        @error('privacy_consent')
                                            <div class="uk-text-danger uk-text-small uk-margin-small-top">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="uk-margin-top uk-text-center">
                                @error('g-recaptcha-response')
                                    <div class="uk-text-danger uk-text-small uk-margin-bottom">{{ $message }}</div>
                                @enderror
                                <button type="submit" class="uk-button uk-button-primary uk-margin-left" id="contact-submit">送信</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@if(config('recaptcha.enabled'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}"></script>
    <script>
        (function() {
            var form = document.getElementById('contact-form');
            var siteKey = '{{ config('recaptcha.site_key') }}';
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, { action: 'contact' }).then(function(token) {
                        document.getElementById('g-recaptcha-response').value = token;
                        form.submit();
                    });
                });
            });
        })();
    </script>
@endif
@endsection
