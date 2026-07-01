<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubdomainRequest extends FormRequest
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
            'system_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'voucher_amount' => 'required|integer|min:0',
            'voucher_expiry' => 'required|integer|min:0',
            'voucher_publish_date' => 'required|integer|min:1|max:31',
            'postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'transfer_date_rule' => 'nullable|string|in:next_month_end,month_after_next_end',
            'zengin_requester_code' => 'nullable|string|max:10',
            'zengin_requester_name' => 'nullable|string|max:40',
            'zengin_bank_code' => 'nullable|string|max:4',
            'zengin_bank_name' => 'nullable|string|max:15',
            'zengin_branch_code' => 'nullable|string|max:3',
            'zengin_branch_name' => 'nullable|string|max:15',
            'zengin_account_type' => 'nullable|string|size:1|in:1,2,4',
            'zengin_account_number' => 'nullable|string|max:7',
            'grades' => 'nullable|array',
            'grades.*' => 'required|string|max:100|distinct',
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
            'system_name.required' => 'システム名を入力してください。',
            'system_name.max' => 'システム名は255文字以内で入力してください。',
            'description.max' => '説明は1000文字以内で入力してください。',
            'voucher_amount.required' => 'クーポン金額を入力してください。',
            'voucher_amount.integer' => 'クーポン金額は整数で入力してください。',
            'voucher_amount.min' => 'クーポン金額は0以上で入力してください。',
            'voucher_expiry.required' => 'クーポン有効期限を入力してください。',
            'voucher_expiry.integer' => 'クーポン有効期限は整数で入力してください。',
            'voucher_expiry.min' => 'クーポン有効期限は0以上で入力してください。',
            'voucher_publish_date.required' => 'クーポン発行日を入力してください。',
            'voucher_publish_date.integer' => 'クーポン発行日は整数で入力してください。',
            'voucher_publish_date.min' => 'クーポン発行日は1以上で入力してください。',
            'voucher_publish_date.max' => 'クーポン発行日は31以下で入力してください。',
            'grades.array' => '学年は配列形式で入力してください。',
            'grades.*.required' => '学年名を入力してください。',
            'grades.*.string' => '学年名は文字列で入力してください。',
            'grades.*.max' => '学年名は100文字以内で入力してください。',
            'grades.*.distinct' => '学年名が重複しています。',
            'zengin_account_type.in' => '預金種目は1（普通）、2（当座）、4（貯蓄）のいずれかを選択してください。',
        ];
    }
}
