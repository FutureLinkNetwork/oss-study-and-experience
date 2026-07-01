@extends('layouts.app')

@section('title', 'パスワードリセット - ' . $subdomain->system_name)

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-md p-8">
		<div class="max-w-md mx-auto w-full space-y-8">
        <div>
            <div class="mx-auto h-16 w-16 flex items-center justify-center bg-blue-100 rounded-full">
                <i class="fas fa-key text-blue-600 text-2xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                パスワードリセット
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ $subdomain->system_name }}
            </p>
            <div class="mt-2 text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-lock mr-1"></i>新しいパスワードを設定してください
                </span>
            </div>
        </div>
        
        <form class="mt-8 space-y-6" method="POST" action="{{ route('user.reset') }}" id="password-reset-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        新しいパスワード
                    </label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('password') border-red-500 @enderror"
                           placeholder="10文字以上の英数字と記号">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">
                        10文字以上の英数字と記号が使用できます
                    </p>
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        パスワード確認
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('password_confirmation') border-red-500 @enderror"
                           placeholder="パスワードを再入力">
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('token')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div id="password-match-indicator" class="mt-1 text-xs hidden">
                        <span id="password-match-text"></span>
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" id="submit-button"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-check text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    パスワードをリセットする
                </button>
            </div>
        </form>
		</div>
	</div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordMatchIndicator = document.getElementById('password-match-indicator');
    const passwordMatchText = document.getElementById('password-match-text');
    const submitButton = document.getElementById('submit-button');
    const form = document.getElementById('password-reset-form');

    function checkPasswordMatch() {
        const password = passwordInput.value;
        const passwordConfirmation = passwordConfirmationInput.value;

        // 両方のフィールドが入力されている場合のみチェック
        if (password.length === 0 || passwordConfirmation.length === 0) {
            passwordMatchIndicator.classList.add('hidden');
            submitButton.disabled = true;
            return;
        }

        // パスワードが10文字以上かチェック
        if (password.length < 10) {
            passwordMatchIndicator.classList.remove('hidden');
            passwordMatchText.textContent = 'パスワードは10文字以上で入力してください。';
            passwordMatchText.className = 'text-red-600';
            passwordConfirmationInput.classList.add('border-red-500');
            submitButton.disabled = true;
            return;
        }

        // パスワードが一致しているかチェック
        if (password === passwordConfirmation) {
            passwordMatchIndicator.classList.remove('hidden');
            passwordMatchText.textContent = '✓ パスワードが一致しています';
            passwordMatchText.className = 'text-green-600 font-semibold';
            passwordConfirmationInput.classList.remove('border-red-500');
            passwordConfirmationInput.classList.add('border-green-500');
            submitButton.disabled = false;
        } else {
            passwordMatchIndicator.classList.remove('hidden');
            passwordMatchText.textContent = '✗ パスワードが一致しません';
            passwordMatchText.className = 'text-red-600';
            passwordConfirmationInput.classList.remove('border-green-500');
            passwordConfirmationInput.classList.add('border-red-500');
            submitButton.disabled = true;
        }
    }

    // リアルタイムでチェック
    passwordInput.addEventListener('input', checkPasswordMatch);
    passwordConfirmationInput.addEventListener('input', checkPasswordMatch);

    // 初期状態ではボタンを無効化
    submitButton.disabled = true;

    // フォーム送信時の最終チェック
    form.addEventListener('submit', function(e) {
        if (submitButton.disabled) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
@endsection
