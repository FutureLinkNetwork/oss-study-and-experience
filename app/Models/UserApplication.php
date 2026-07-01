<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'subdomain_id',
        'certification_number',
        'guardian_name',
        'guardian_name_kana',
        'guardian_birth_date',
        'guardian_address',
        'guardian_phone',
        'guardian_email',
        'child_name',
        'child_name_kana',
        'child_birth_date',
        'elementary_school_name',
        'grade',
        'child_address',
        'child_address_same_as_guardian',
        'child_registered_in_municipality_and_receiving_scholarship',
        'survey_consent',
        'privacy_policy_agreed',
        'classroom_name_1',
        'classroom_location_1',
        'classroom_phone_1',
        'classroom_contact_person_1',
        'classroom_name_2',
        'classroom_location_2',
        'classroom_phone_2',
        'classroom_contact_person_2',
        'classroom_name_3',
        'classroom_location_3',
        'classroom_phone_3',
        'classroom_contact_person_3',
        'document_s3_key',
        'document_original_filename',
        'document_file_size',
        'document_mime_type',
        'is_exported',
        'is_excluded_from_download',
        'admin_remarks',
    ];

    protected function casts(): array
    {
        return [
            'guardian_birth_date' => 'date',
            'child_birth_date' => 'date',
            'child_address_same_as_guardian' => 'boolean',
            'child_registered_in_municipality_and_receiving_scholarship' => 'boolean',
            'survey_consent' => 'boolean',
            'privacy_policy_agreed' => 'boolean',
            'is_exported' => 'boolean',
            'is_excluded_from_download' => 'boolean',
        ];
    }

    /**
     * 申請が属するサブドメイン
     */
    public function subdomain(): BelongsTo
    {
        return $this->belongsTo(Subdomain::class);
    }

    /**
     * 添付ファイルが存在するかチェック
     */
    public function hasDocument(): bool
    {
        return ! empty($this->document_s3_key);
    }
}
