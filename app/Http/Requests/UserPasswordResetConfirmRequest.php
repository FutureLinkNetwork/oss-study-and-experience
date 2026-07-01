<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserPasswordResetConfirmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // 英数字と一般的な記号を許可（10文字以上）
        return [
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:10', 'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]+$/', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'トークンが必要です。',
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは10文字以上で入力してください。',
            'password.regex' => 'パスワードは英数字と記号のみ使用できます。',
            'password.confirmed' => 'パスワードが一致しません。',
            'password_confirmation.required' => 'パスワード確認を入力してください。',
            'password_confirmation.min' => 'パスワード確認は10文字以上で入力してください。',
        ];
    }
}
