@extends('default.www.layout')

@section('title', '習い事リクエスト ｜ 習い事クーポン管理システム')
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
                <li><span>習い事リクエスト</span></li>
            </ul>
			
            <div class="uk-container uk-margin-large">
			<h2 class="uk-h2 uk-text-center"><span class="ttl anime">習い事リクエスト</span></h2>
				<div class="content_wrap uk-clearfix anime" uk-grid>
					<div class="content_inner uk-flex-wrap-stretch">
                        <!-- 成功メッセージ -->
                        @if(session('success'))
                            <div class="uk-alert-success" uk-alert>
                                <a class="uk-alert-close" uk-close></a>
                                <p>{{ session('success') }}</p>
                            </div>
                        @endif

                        <!-- フォーム -->
                        <form method="POST" action="{{ route('course.request.store') }}" class="uk-form-stacked" id="course-request-form">
                            @csrf
                            @if(config('recaptcha.enabled'))
                                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
                            @endif
                            <div class="uk-grid-small" uk-grid>
                                <!-- 教室名 -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold" for="classroom_name">
                                        教室名 <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input class="uk-input @error('classroom_name') uk-form-danger @enderror" 
                                               type="text" 
                                               id="classroom_name" 
                                               name="classroom_name" 
                                               value="{{ old('classroom_name') }}" 
                                               placeholder="教室名を入力してください"
                                               required>
                                        @error('classroom_name')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- 教室所在地 -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="address">
										教室所在地 <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <textarea class="uk-textarea @error('address') uk-form-danger @enderror" 
                                                  id="address" 
                                                  name="address" 
                                                  rows="3" 
                                                  placeholder="教室所在地を入力してください"
                                                  required>{{ old('address') }}</textarea>
                                        @error('address')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- 教室電話番号 -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="phone">
									教室電話番号 <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input class="uk-input @error('phone') uk-form-danger @enderror" 
                                               type="tel" 
                                               id="phone" 
                                               name="phone" 
                                               value="{{ old('phone') }}" 
                                               placeholder="教室電話番号を入力してください"
                                               required>
                                        @error('phone')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- リクエストした人の名前 -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="requester_name">
                                        ご自身のお名前 <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input class="uk-input @error('requester_name') uk-form-danger @enderror" 
                                               type="text" 
                                               id="requester_name" 
                                               name="requester_name" 
                                               value="{{ old('requester_name') }}" 
                                               placeholder="お名前を入力してください"
                                               required>
                                        @error('requester_name')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- リクエストした人のメールアドレス -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="requester_email">
                                        ご自身のメールアドレス <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input class="uk-input @error('requester_email') uk-form-danger @enderror" 
                                               type="email" 
                                               id="requester_email" 
                                               name="requester_email" 
                                               value="{{ old('requester_email') }}" 
                                               placeholder="メールアドレスを入力してください"
                                               required>
                                        @error('requester_email')
                                            <span class="uk-text-danger uk-text-small">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- リクエストした人の電話番号 -->
                                <div class="uk-width-1-1">
                                    <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="requester_phone">
                                        ご自身の電話番号 <span class="uk-text-danger">*</span>
                                    </label>
                                    <div class="uk-form-controls">
                                        <input class="uk-input @error('requester_phone') uk-form-danger @enderror" 
                                               type="tel" 
                                               id="requester_phone" 
                                               name="requester_phone" 
                                               value="{{ old('requester_phone') }}" 
                                               placeholder="電話番号を入力してください"
                                               required>
                                        @error('requester_phone')
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
											<a href="" target="_blank" rel="noopener noreferrer" style="color:#0000FF !important; text-decoration:underline !important;">プライバシーポリシー</a>に同意します <span class="uk-text-danger">*</span>
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
                                <button type="submit" class="uk-button uk-button-primary uk-margin-left">送信</button>
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
            var form = document.getElementById('course-request-form');
            var siteKey = '{{ config('recaptcha.site_key') }}';
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, { action: 'course_request' }).then(function(token) {
                        document.getElementById('g-recaptcha-response').value = token;
                        form.submit();
                    });
                });
            });
        })();
    </script>
@endif
@endsection
