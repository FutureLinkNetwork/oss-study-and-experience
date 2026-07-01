<?php

namespace Tests\Feature;

use App\Models\BusinessInfo;
use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessApplicationEmailDuplicateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_email_is_rejected_on_confirm(): void
    {
        config(['recaptcha.enabled' => false]);

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'localhost',
        ]);

        BusinessInfo::create([
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => '既存事業者',
            'business_name_kana' => 'キソンジギョウシャ',
            'representative_name' => '既存 太郎',
            'representative_name_kana' => 'キソン タロウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '中央1-1-1',
            'phone' => '072-123-4567',
            'email' => 'duplicate-business@example.com',
            'apply' => 0,
            'is_active' => 1,
            'status' => '未着手',
        ]);

        $applicantType = 'corporation';
        $requiredDocumentKeys = BusinessInfo::getRequiredDocumentKeys($applicantType);
        $documentsUploaded = array_fill_keys($requiredDocumentKeys, true);
        $uploadedFiles = [];
        foreach ($requiredDocumentKeys as $key) {
            $uploadedFiles[$key] = ['original_name' => 'dummy.pdf'];
        }

        $response = $this
            ->from(route('business_application.create'))
            ->withSession(['uploaded_files' => $uploadedFiles])
            ->post(route('business_application.confirm'), $this->validPayload([
                'email' => 'duplicate-business@example.com',
                'applicant_type' => $applicantType,
                'documents_uploaded' => $documentsUploaded,
            ]));

        $response->assertRedirect(route('business_application.create'));
        $response->assertSessionHasErrors([
            'email' => '入力されたメールアドレスは既に登録されています。別のメールアドレスをご使用ください。',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $payload = [
            'applicant_type' => 'corporation',
            'antisocial_forces_pledged' => '1',
            'privacy_policy_agreed' => '1',
            'business_name' => '新規事業者',
            'business_name_kana' => 'シンキジギョウシャ',
            'representative_title' => '代表取締役',
            'representative_family_name' => '山田',
            'representative_given_name' => '太郎',
            'representative_title_kana' => 'ダイヒョウトリシマリヤク',
            'representative_family_name_kana' => 'ヤマダ',
            'representative_given_name_kana' => 'タロウ',
            'postal_code' => '664-0002',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '中央2-2-2',
            'phone' => '072-234-5678',
            'email' => 'new-business@example.com',
            'document_address' => '兵庫県伊丹市中央2-2-2',
            'classrooms' => [
                [
                    'classroom_name' => '本校',
                    'classroom_name_kana' => 'ホンコウ',
                    'service_type' => 'group',
                    'lesson_category' => 1,
                    'use_map' => 0,
                ],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
