<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessPasswordUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
                'min:10',
                'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]+$/',
                'confirmed',
            ],
            'password_confirmation' => [
                'required',
                'string',
                'min:10',
            ],
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
            'current_password.required' => '現在のパスワードを入力してください。',
            'password.required' => '新しいパスワードを入力してください。',
            'password.min' => 'パスワードは10文字以上で入力してください。',
            'password.regex' => 'パスワードは英数字と記号のみ使用できます。',
            'password.confirmed' => 'パスワードが一致しません。',
            'password_confirmation.required' => 'パスワード確認を入力してください。',
            'password_confirmation.min' => 'パスワード確認は10文字以上で入力してください。',
        ];
    }
}

