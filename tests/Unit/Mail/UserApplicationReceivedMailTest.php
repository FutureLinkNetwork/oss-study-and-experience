<?php

namespace Tests\Unit\Mail;

use App\Mail\UserApplicationReceivedMail;
use App\Models\Subdomain;
use App\Models\UserApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UserApplicationReceivedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_uses_system_name_and_config_from_address(): void
    {
        Config::set('mail.from.address', 'from@example.com');
        Config::set('app.name', 'Fallback App');

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'itami',
            'system_name' => '伊丹市子どもの習い事応援事業',
            'is_active' => true,
        ]);

        $userApplication = UserApplication::factory()->create([
            'subdomain_id' => $subdomain->id,
            'guardian_name' => '山田　太郎',
            'guardian_email' => 'guardian-envelope@example.com',
            'child_name' => '山田　花子',
        ]);

        $contactUrl = 'https://itami.example.com/contact';
        $mailable = new UserApplicationReceivedMail($userApplication, $subdomain, $contactUrl);
        $envelope = $mailable->envelope();

        $this->assertSame(
            '【伊丹市子どもの習い事応援事業】利用者申請を受け付けました',
            $envelope->subject
        );
        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame('from@example.com', $envelope->from->address);
        $this->assertSame('伊丹市子どもの習い事応援事業', $envelope->from->name);
    }

    public function test_render_includes_system_name_applicant_names_and_contact_url(): void
    {
        Config::set('mail.from.address', 'from@example.com');

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'itami',
            'system_name' => '伊丹市子どもの習い事応援事業',
            'is_active' => true,
        ]);

        $userApplication = UserApplication::factory()->create([
            'subdomain_id' => $subdomain->id,
            'guardian_name' => '山田　太郎',
            'guardian_email' => 'guardian-render@example.com',
            'child_name' => '山田　花子',
        ]);

        $contactUrl = 'https://itami.example.com/contact';
        $mailable = new UserApplicationReceivedMail($userApplication->fresh(), $subdomain, $contactUrl);
        $body = $mailable->render();

        $this->assertStringContainsString('伊丹市子どもの習い事応援事業', $body);
        $this->assertStringContainsString('伊丹市子どもの習い事応援事業の利用者申請を受け付けました。', $body);
        $this->assertStringContainsString('山田　太郎', $body);
        $this->assertStringContainsString('山田　花子', $body);
        $this->assertStringContainsString($contactUrl, $body);
        $this->assertStringContainsString('伊丹市子どもの習い事応援事業 事務局', $body);
    }

    public function test_envelope_falls_back_to_app_name_when_system_name_is_empty(): void
    {
        Config::set('mail.from.address', 'from@example.com');
        Config::set('app.name', 'Fallback App');

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'itami',
            'system_name' => '',
            'is_active' => true,
        ]);

        $userApplication = UserApplication::factory()->create([
            'subdomain_id' => $subdomain->id,
        ]);

        $mailable = new UserApplicationReceivedMail($userApplication, $subdomain, 'https://itami.example.com/contact');
        $envelope = $mailable->envelope();

        $this->assertSame('【Fallback App】利用者申請を受け付けました', $envelope->subject);
        $this->assertSame('Fallback App', $envelope->from->name);
    }
}
