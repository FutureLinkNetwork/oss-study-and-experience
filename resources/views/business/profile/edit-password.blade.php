@extends('layouts.app')

@section('title', 'パスワード変更 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('business.profile.edit') }}" class="hover:text-gray-700">設定</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>パスワード変更</span></li>
            </ol>
        </nav>		
	
    <!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
        <!-- 成功メッセージ -->
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
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
                    <form method="POST" action="{{ route('business.profile.update.password') }}" id="password-update-form">
                        @csrf
                        @method('PUT')

                        <!-- 現在のパスワード -->
                        <div class="mb-4">
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                                現在のパスワード <span class="text-red-500">*</span>
                            </label>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm @error('current_password') border-red-500 @enderror"
                                   placeholder="現在のパスワードを入力">
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 新しいパスワード -->
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                新しいパスワード <span class="text-red-500">*</span>
                            </label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm @error('password') border-red-500 @enderror"
                                   placeholder="10文字以上の英数字と記号">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                10文字以上の英数字と記号が使用できます
                            </p>
                        </div>

                        <!-- パスワード確認 -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                パスワード確認 <span class="text-red-500">*</span>
                            </label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm @error('password_confirmation') border-red-500 @enderror"
                                   placeholder="パスワードを再入力">
                            @error('password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div id="password-match-indicator" class="mt-1 text-xs hidden">
                                <span id="password-match-text"></span>
                            </div>
                        </div>

                        <!-- 送信ボタン -->
                        <div class="mt-6 flex justify-end space-x-4">
                            <a href="{{ route('business.profile.edit') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md text-sm font-medium">
                                キャンセル
                            </a>
                            <button type="submit" id="password-submit-button"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                <i class="fas fa-save mr-2"></i>パスワードを更新
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
    const passwordSubmitButton = document.getElementById('password-submit-button');
    const passwordForm = document.getElementById('password-update-form');

    function checkPasswordFields() {
        const password = passwordInput.value;
        const passwordConfirmation = passwordConfirmationInput.value;

        // 両方のフィールドが入力されている場合のみチェック
        if (password.length === 0 || passwordConfirmation.length === 0) {
            passwordMatchIndicator.classList.add('hidden');
            passwordConfirmationInput.classList.remove('border-red-500', 'border-green-500');
            passwordSubmitButton.disabled = false;
            return;
        }

        // パスワードが10文字以上かチェック
        if (password.length < 10) {
            passwordMatchIndicator.classList.remove('hidden');
            passwordMatchText.textContent = 'パスワードは10文字以上で入力してください。';
            passwordMatchText.className = 'text-red-600';
            passwordConfirmationInput.classList.add('border-red-500');
            passwordConfirmationInput.classList.remove('border-green-500');
            passwordSubmitButton.disabled = true;
            return;
        }

        // パスワードが一致しているかチェック
        if (password === passwordConfirmation) {
            passwordMatchIndicator.classList.remove('hidden');
            passwordMatchText.textContent = '✓ パスワードが一致しています';
            passwordMatchText.className = 'text-green-600 font-semibold';
            passwordConfirmationInput.classList.remove('border-red-500');
            passwordConfirmationInput.classList.add('border-green-500');
            passwordSubmitButton.disabled = false;
        } else {
            passwordMatchIndicator.classList.remove('hidden');
            passwordMatchText.textContent = '✗ パスワードが一致しません';
            passwordMatchText.className = 'text-red-600';
            passwordConfirmationInput.classList.remove('border-green-500');
            passwordConfirmationInput.classList.add('border-red-500');
            passwordSubmitButton.disabled = true;
        }
    }

    // リアルタイムでチェック
    passwordInput.addEventListener('input', checkPasswordFields);
    passwordConfirmationInput.addEventListener('input', checkPasswordFields);

    // フォーム送信時の最終チェック
    passwordForm.addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const passwordConfirmation = passwordConfirmationInput.value;

        // パスワードが一致しない場合
        if (password !== passwordConfirmation) {
            e.preventDefault();
            alert('パスワードが一致しません。');
            return false;
        }

        // パスワードが10文字未満の場合
        if (password.length < 10) {
            e.preventDefault();
            alert('パスワードは10文字以上で入力してください。');
            return false;
        }

        if (passwordSubmitButton.disabled) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
@endsection

