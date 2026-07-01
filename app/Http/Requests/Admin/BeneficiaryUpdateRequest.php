<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BeneficiaryUpdateRequest extends FormRequest
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
        // 申請者と同一の住所がチェックされているかどうか
        $childAddressSameAsGuardian = $this->has('child_address_same_as_guardian') && $this->child_address_same_as_guardian === '1';

        return [
            'child_id' => 'nullable|string|max:100',
            'certification_number' => 'required|string|max:100',
            'guardian_name' => 'required|string|max:100',
            'guardian_name_kana' => 'nullable|string|max:100',
            'guardian_birth_date' => 'required|date',
            'guardian_address' => 'required|string|max:500',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'required|email|max:255',
            'child_name' => 'required|string|max:100',
            'child_name_kana' => 'nullable|string|max:100',
            'child_birth_date' => 'required|date',
            'elementary_school_name' => 'required|string|max:100',
            'grade' => 'required|string|max:50',
            'child_address' => $childAddressSameAsGuardian ? 'nullable|string|max:500' : 'required|string|max:500',
            'child_address_same_as_guardian' => 'nullable|boolean',
            'survey_consent' => 'nullable|boolean',
            'classroom_name_1' => 'nullable|string|max:100',
            'classroom_location_1' => 'nullable|string|max:200',
            'classroom_phone_1' => 'nullable|string|max:20',
            'classroom_contact_person_1' => 'nullable|string|max:100',
            'classroom_name_2' => 'nullable|string|max:100',
            'classroom_location_2' => 'nullable|string|max:200',
            'classroom_phone_2' => 'nullable|string|max:20',
            'classroom_contact_person_2' => 'nullable|string|max:100',
            'classroom_name_3' => 'nullable|string|max:100',
            'classroom_location_3' => 'nullable|string|max:200',
            'classroom_phone_3' => 'nullable|string|max:20',
            'classroom_contact_person_3' => 'nullable|string|max:100',
            'application_date' => 'required|date',
            'certification_date' => 'required|date',
            'status' => 'required|string|in:決定通知書未送信,決定通知書送信待ち,決定通知書送信失敗,決定通知書送信済,ログイン認証済み,資格喪失予定,資格喪失',
            'disqualification_date' => 'nullable|date',
            'labels' => 'nullable|array',
            'labels.*' => 'nullable|string|in:DV避難等',
            'remarks' => 'nullable|string|max:65535',
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
            'certification_number.required' => '就学援助認定番号を入力してください。',
            'guardian_name.required' => '就学援助認定者名（保護者名）を入力してください。',
            'guardian_birth_date.required' => '就学援助認定者生年月日を入力してください。',
            'guardian_address.required' => '住所を入力してください。',
            'guardian_phone.required' => '電話番号を入力してください。',
            'guardian_email.required' => 'メールアドレスを入力してください。',
            'guardian_email.email' => 'メールアドレスの形式が正しくありません。',
            'child_name.required' => '対象児童名を入力してください。',
            'child_birth_date.required' => '対象児童生年月日を入力してください。',
            'elementary_school_name.required' => '小学校名を入力してください。',
            'grade.required' => '学年を入力してください。',
            'child_address.required' => '対象児童の住所を入力してください。',
            'application_date.required' => '申請日を入力してください。',
            'certification_date.required' => '認定日を入力してください。',
            'certification_date.date' => '認定日の形式が正しくありません。',
        ];
    }
}
