<?php

namespace App\Http\Requests;

use App\Rules\RecaptchaV3;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
        $rules = [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'content' => 'required|string',
            'privacy_consent' => 'required|accepted',
        ];

        if (config('recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = ['required', 'string', new RecaptchaV3];
        } else {
            $rules['g-recaptcha-response'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'お名前を入力してください。',
            'name.max' => 'お名前は100文字以内で入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'phone.required' => '電話番号を入力してください。',
            'phone.max' => '電話番号は20文字以内で入力してください。',
            'content.required' => '問い合わせ内容を入力してください。',
            'privacy_consent.required' => '個人情報の取り扱いに同意してください。',
            'privacy_consent.accepted' => '個人情報の取り扱いに同意してください。',
            'g-recaptcha-response.required' => '確認に失敗しました。再度お試しください。',
        ];
    }
}
