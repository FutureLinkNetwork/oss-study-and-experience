<?php

namespace Tests\Feature\Mail;

use App\Mail\UserApplicationReceivedMail;
use App\Models\Subdomain;
use App\Models\UserApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserApplicationReceivedMailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validFormInput(): array
    {
        return [
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
        ];
    }

    public function test_store_sends_received_mail_with_system_name(): void
    {
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
        config(['recaptcha.enabled' => false]);

        $systemName = 'テスト市子どもの習い事応援事業';

        Subdomain::factory()->create([
            'subdomain' => 'test',
            'system_name' => $systemName,
            'is_active' => true,
            'settings' => [
                'grades' => ['1年生', '2年生', '3年生'],
            ],
        ]);

        Mail::fake();

        $response = $this->post('http://test.localhost/user_application/store', $this->validFormInput());

        $response->assertRedirect(route('user_application.complete'));
        $this->assertDatabaseHas('user_applications', [
            'guardian_email' => 'guardian@example.com',
            'guardian_name' => '山田　太郎',
            'child_name' => '山田　花子',
        ]);

        Mail::assertSent(UserApplicationReceivedMail::class, function (UserApplicationReceivedMail $mail) use ($systemName) {
            $this->assertSame(
                '【'.$systemName.'】利用者申請を受け付けました',
                $mail->envelope()->subject
            );
            $this->assertTrue($mail->hasTo('guardian@example.com'));

            $body = $mail->render();
            $this->assertStringContainsString($systemName, $body);
            $this->assertStringContainsString($systemName.'の利用者申請を受け付けました。', $body);
            $this->assertStringContainsString('山田　太郎', $body);
            $this->assertStringContainsString('山田　花子', $body);

            return true;
        });
    }

    public function test_store_does_not_send_mail_when_validation_fails(): void
    {
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
        config(['recaptcha.enabled' => false]);

        Subdomain::factory()->create([
            'subdomain' => 'test',
            'system_name' => 'テスト市子どもの習い事応援事業',
            'is_active' => true,
        ]);

        Mail::fake();

        $invalidInput = $this->validFormInput();
        $invalidInput['guardian_email'] = '';

        $response = $this->post('http://test.localhost/user_application/store', $invalidInput);

        $response->assertRedirect(route('user_application.create'));
        $this->assertDatabaseCount('user_applications', 0);
        Mail::assertNothingSent();
    }

    public function test_store_completes_even_when_mail_sending_fails(): void
    {
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
        config(['recaptcha.enabled' => false]);

        Subdomain::factory()->create([
            'subdomain' => 'test',
            'system_name' => 'テスト市子どもの習い事応援事業',
            'is_active' => true,
        ]);

        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP error'));

        $response = $this->post('http://test.localhost/user_application/store', $this->validFormInput());

        $response->assertRedirect(route('user_application.complete'));
        $this->assertDatabaseHas('user_applications', [
            'guardian_email' => 'guardian@example.com',
        ]);
        $this->assertSame(1, UserApplication::query()->count());
    }
}
