<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendBulkLoginInfoRequest extends FormRequest
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
        return [
            'issue_voucher' => ['sometimes', 'boolean'],
            'child_id' => ['nullable', 'string'],
            'certification_number' => ['nullable', 'string'],
            'guardian_name' => ['nullable', 'string'],
            'child_name' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'issue_voucher.boolean' => 'クーポン付与の指定が不正です。',
        ];
    }

    public function shouldIssueVoucher(): bool
    {
        return $this->boolean('issue_voucher');
    }
}
