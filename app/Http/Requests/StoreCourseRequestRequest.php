<?php

namespace App\Http\Requests;

use App\Rules\RecaptchaV3;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequestRequest extends FormRequest
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
            'classroom_name' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'requester_name' => 'required|string|max:100',
            'requester_email' => 'required|email|max:255',
            'requester_phone' => 'required|string|max:20',
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
            'classroom_name.required' => '教室名を入力してください。',
            'classroom_name.max' => '教室名は100文字以内で入力してください。',
            'address.required' => '住所を入力してください。',
            'address.max' => '住所は500文字以内で入力してください。',
            'phone.required' => '電話番号を入力してください。',
            'phone.max' => '電話番号は20文字以内で入力してください。',
            'requester_name.required' => 'お名前を入力してください。',
            'requester_name.max' => 'お名前は100文字以内で入力してください。',
            'requester_email.required' => 'メールアドレスを入力してください。',
            'requester_email.email' => '正しいメールアドレス形式で入力してください。',
            'requester_email.max' => 'メールアドレスは255文字以内で入力してください。',
            'requester_phone.required' => '電話番号を入力してください。',
            'requester_phone.max' => '電話番号は20文字以内で入力してください。',
            'privacy_consent.required' => '個人情報の取り扱いに同意してください。',
            'privacy_consent.accepted' => '個人情報の取り扱いに同意してください。',
            'g-recaptcha-response.required' => '確認に失敗しました。再度お試しください。',
        ];
    }
}
