<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserProfileUpdateRequest extends FormRequest
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
        $user = Auth::user();

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                    return $query->where('subdomain_id', $user->subdomain_id);
                }),
            ],
            'current_password' => [
                'required_with:password',
                'string',
            ],
            'password' => [
                'nullable',
                'string',
                'min:10',
                'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]+$/',
                'confirmed',
            ],
            'password_confirmation' => [
                'required_with:password',
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
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'current_password.required_with' => 'パスワードを変更する場合は、現在のパスワードを入力してください。',
            'password.min' => 'パスワードは10文字以上で入力してください。',
            'password.regex' => 'パスワードは英数字と記号のみ使用できます。',
            'password.confirmed' => 'パスワードが一致しません。',
            'password_confirmation.required_with' => 'パスワード確認を入力してください。',
            'password_confirmation.min' => 'パスワード確認は10文字以上で入力してください。',
        ];
    }
}
