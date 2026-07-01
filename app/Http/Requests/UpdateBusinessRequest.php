<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
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
        $businessId = $this->route('business')->id ?? null;

        return [
            'applicant_type' => 'required|in:individual,corporation,voluntary_group,government_agency',
            'business_name' => 'required|string|max:100',
            'business_name_kana' => 'nullable|string|max:100',
            'representative_title' => 'required|string|max:50',
            'representative_family_name' => 'required|string|max:50',
            'representative_given_name' => 'required|string|max:50',
            'representative_title_kana' => 'required|string|max:50',
            'representative_family_name_kana' => 'required|string|max:50',
            'representative_given_name_kana' => 'required|string|max:50',
            'representative_name' => 'nullable|string|max:50',
            'representative_name_kana' => 'nullable|string|max:50',
            'postal_code' => 'required|string|max:8',
            'prefecture' => 'required|string|max:10',
            'city' => 'required|string|max:50',
            'address1' => 'required|string|max:100',
            'building_name' => 'nullable|string|max:100',
            'phone' => 'required|string|max:20',
            'fax' => 'nullable|string|max:20',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('business_infos', 'email')->ignore($businessId),
            ],
            'website_url' => 'nullable|url|max:255',
            'email_timing' => 'nullable|string|in:immediate,daily,none',
            'contact_person' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:20',
            'document_person' => 'required|string|max:50',
            'document_address' => 'required|string|max:255',
            'business_hours' => 'required|string',
            'holiday' => 'required|string',
            'bank_code' => 'required|string|max:50',
            'branch_code' => 'required|string|max:50',
            'account_type' => 'required|in:普通,当座',
            'account_number' => 'required|string|max:20',
            'account_holder_name' => 'required|string|max:50',
            'is_active' => 'boolean',
            'apply' => 'nullable|boolean',
            'status' => [
                'nullable',
                'string',
                Rule::in(\App\Models\BusinessInfo::getAvailableStatuses()),
            ],
            'is_public_funds_transfer_target' => 'nullable|boolean',
            'admin_remarks' => 'nullable|string',
            'admin_attachment_remove' => 'nullable|array',
            'admin_attachment_remove.*' => 'string|max:500',
            'admin_attachments' => 'nullable|array|max:5',
            'admin_attachments.*' => 'file|max:8192',
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
            'applicant_type.required' => '申請者種別を選択してください。',
            'applicant_type.in' => '申請者種別が正しくありません。',
            'business_name.required' => '事業者名を入力してください。',
            'business_name.max' => '事業者名は100文字以内で入力してください。',
            'representative_title.required' => '代表者役職名を入力してください。',
            'representative_title.max' => '代表者役職名は50文字以内で入力してください。',
            'representative_family_name.required' => '代表者名（姓）を入力してください。',
            'representative_family_name.max' => '代表者名（姓）は50文字以内で入力してください。',
            'representative_given_name.required' => '代表者名（名）を入力してください。',
            'representative_given_name.max' => '代表者名（名）は50文字以内で入力してください。',
            'representative_family_name_kana.required' => '代表者名（カナ）（姓）を入力してください。',
            'representative_family_name_kana.max' => '代表者名（カナ）（姓）は50文字以内で入力してください。',
            'representative_given_name_kana.required' => '代表者名（カナ）（名）を入力してください。',
            'representative_given_name_kana.max' => '代表者名（カナ）（名）は50文字以内で入力してください。',
            'postal_code.required' => '郵便番号を入力してください。',
            'postal_code.max' => '郵便番号は8文字以内で入力してください。',
            'prefecture.required' => '都道府県を入力してください。',
            'prefecture.max' => '都道府県は10文字以内で入力してください。',
            'city.required' => '市区町村を入力してください。',
            'city.max' => '市区町村は50文字以内で入力してください。',
            'address1.required' => '住所を入力してください。',
            'address1.max' => '住所は100文字以内で入力してください。',
            'phone.required' => '電話番号を入力してください。',
            'phone.max' => '電話番号は20文字以内で入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'website_url.url' => '正しいURL形式で入力してください。',
            'bank_code.required' => '銀行名を選択してください。',
            'bank_code.max' => '銀行コードは50文字以内で入力してください。',
            'branch_code.required' => '支店名を選択してください。',
            'branch_code.max' => '支店コードは50文字以内で入力してください。',
            'account_type.required' => '口座種別を選択してください。',
            'account_type.in' => '口座種別は「普通」または「当座」を選択してください。',
            'account_number.required' => '口座番号を入力してください。',
            'account_number.max' => '口座番号は20文字以内で入力してください。',
            'account_holder_name.required' => '口座名義（カナ）を入力してください。',
            'account_holder_name.max' => '口座名義（カナ）は50文字以内で入力してください。',
            'contact_person.required' => '連絡先：担当者名を入力してください。',
            'contact_person.max' => '連絡先：担当者名は50文字以内で入力してください。',
            'contact_phone.required' => '連絡先：電話番号を入力してください。',
            'contact_phone.max' => '連絡先：電話番号は20文字以内で入力してください。',
            'document_person.required' => '文書等送付先：宛名を入力してください。',
            'document_person.max' => '文書等送付先：宛名は50文字以内で入力してください。',
            'document_address.required' => '文書等送付先：住所を入力してください。',
            'document_address.max' => '文書等送付先：住所は255文字以内で入力してください。',
            'business_hours.required' => '連絡先：営業時間を入力してください。',
            'holiday.required' => '連絡先：定休日を入力してください。',
            'status.in' => '選択されたステータスが無効です。',
        ];
    }
}
