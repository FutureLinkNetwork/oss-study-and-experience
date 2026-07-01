<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserApplicationRequest extends FormRequest
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
        // 確認画面からの申請かどうかを判定
        $isFromConfirm = $this->has('document_uploaded');

        $rules = [
            // 必須項目
            'certification_number' => 'required|string|max:100',
            'guardian_name' => 'required|string|max:100',
            'guardian_birth_date' => 'required|date',
            'guardian_address' => 'required|string|max:500',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'required|email|max:255',
            'child_name' => 'required|string|max:100',
            'child_birth_date' => 'required|date',
            'elementary_school_name' => 'required|string|max:100',
            'grade' => 'required|string|max:50',
            'child_address' => 'required|string|max:500',
            'survey_consent' => 'required|accepted',

            // 任意項目（教室情報、最大3つ）
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
        ];

        // 添付ファイルのバリデーション（任意項目）
        if (! $isFromConfirm) {
            // 初回申請時（確認画面へ）は、ファイルアップロードは任意
            $rules['tax_document'] = 'nullable|file|mimes:jpeg,jpg,png,pdf|max:10240'; // 10MB
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
            'certification_number.required' => '就学援助認定番号を入力してください。',
            'certification_number.max' => '就学援助認定番号は100文字以内で入力してください。',
            'guardian_name.required' => '就学援助認定者名（保護者名）を入力してください。',
            'guardian_name.max' => '就学援助認定者名（保護者名）は100文字以内で入力してください。',
            'guardian_birth_date.required' => '就学援助認定者生年月日を入力してください。',
            'guardian_birth_date.date' => '就学援助認定者生年月日は正しい日付形式で入力してください。',
            'guardian_address.required' => '住所を入力してください。',
            'guardian_address.max' => '住所は500文字以内で入力してください。',
            'guardian_phone.required' => '電話番号を入力してください。',
            'guardian_phone.max' => '電話番号は20文字以内で入力してください。',
            'guardian_email.required' => 'メールアドレスを入力してください。',
            'guardian_email.email' => '正しいメールアドレス形式で入力してください。',
            'guardian_email.max' => 'メールアドレスは255文字以内で入力してください。',
            'child_name.required' => '対象児童名を入力してください。',
            'child_name.max' => '対象児童名は100文字以内で入力してください。',
            'child_birth_date.required' => '対象児童生年月日を入力してください。',
            'child_birth_date.date' => '対象児童生年月日は正しい日付形式で入力してください。',
            'elementary_school_name.required' => '小学校名を入力してください。',
            'elementary_school_name.max' => '小学校名は100文字以内で入力してください。',
            'grade.required' => '学年を入力してください。',
            'grade.max' => '学年は50文字以内で入力してください。',
            'child_address.required' => '対象児童の住所を入力してください。',
            'child_address.max' => '対象児童の住所は500文字以内で入力してください。',
            'survey_consent.required' => '調査同意にチェックを入れてください。',
            'survey_consent.accepted' => '調査同意にチェックを入れてください。',

            // 教室情報
            'classroom_name_1.max' => '教室名1は100文字以内で入力してください。',
            'classroom_location_1.max' => '所在地1は200文字以内で入力してください。',
            'classroom_phone_1.max' => '電話番号1は20文字以内で入力してください。',
            'classroom_contact_person_1.max' => '担当者1は100文字以内で入力してください。',
            'classroom_name_2.max' => '教室名2は100文字以内で入力してください。',
            'classroom_location_2.max' => '所在地2は200文字以内で入力してください。',
            'classroom_phone_2.max' => '電話番号2は20文字以内で入力してください。',
            'classroom_contact_person_2.max' => '担当者2は100文字以内で入力してください。',
            'classroom_name_3.max' => '教室名3は100文字以内で入力してください。',
            'classroom_location_3.max' => '所在地3は200文字以内で入力してください。',
            'classroom_phone_3.max' => '電話番号3は20文字以内で入力してください。',
            'classroom_contact_person_3.max' => '担当者3は100文字以内で入力してください。',

            // 添付ファイル
            'tax_document.file' => '課税証明書等は有効なファイルをアップロードしてください。',
            'tax_document.mimes' => '課税証明書等はJPEG、PNG、PDF形式のファイルをアップロードしてください。',
            'tax_document.max' => '課税証明書等のファイルサイズが大きすぎます。10MB以下のファイルをアップロードしてください。',
        ];
    }
}
