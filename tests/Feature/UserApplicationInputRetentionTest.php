<?php

namespace Tests\Feature;

use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApplicationInputRetentionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 確認画面から入力画面に戻った際に入力値が保持されること
     */
    public function test_input_is_retained_when_returning_from_confirm_screen(): void
    {
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
        config(['recaptcha.enabled' => false]);

        Subdomain::factory()->create([
            'subdomain' => 'test',
            'is_active' => true,
            'settings' => [
                'grades' => ['1年生', '2年生', '3年生'],
            ],
        ]);

        $formInput = [
            'certification_number' => '1234567890',
            'guardian_name_family' => '山田',
            'guardian_name_given' => '太郎',
            'guardian_name_kana_family' => 'ヤマダ',
            'guardian_name_kana_given' => 'タロウ',
            'guardian_birth_date' => '1980-01-01',
            'guardian_address' => '兵庫県伊丹市千僧1-1',
            'guardian_phone' => '090-1234-5678',
            'guardian_email' => 'guardian@example.com',
            'child_name_family' => '山田',
            'child_name_given' => '花子',
            'child_name_kana_family' => 'ヤマダ',
            'child_name_kana_given' => 'ハナコ',
            'child_birth_date' => '2015-04-01',
            'elementary_school_name' => '伊丹小学校',
            'grade' => '3年生',
            'child_address' => '兵庫県伊丹市千僧1-1',
            'survey_consent' => '1',
            'privacy_policy_agreed' => '1',
            'classroom_name_1' => '英語教室A',
        ];

        $confirmResponse = $this->post('http://test.localhost/user_application/confirm', $formInput);
        $confirmResponse->assertStatus(200);

        $backResponse = $this->get('http://test.localhost/user_application');
        $backResponse->assertStatus(200);
        $backResponse->assertSee('value="1234567890"', false);
        $backResponse->assertSee('value="山田"', false);
        $backResponse->assertSee('value="太郎"', false);
        $backResponse->assertSee('guardian@example.com');
        $backResponse->assertSee('伊丹小学校');
        $backResponse->assertSee('英語教室A');
    }
}
