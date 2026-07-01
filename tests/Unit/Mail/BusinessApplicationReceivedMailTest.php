<?php

namespace Tests\Unit\Mail;

use App\Mail\BusinessApplicationReceivedMail;
use App\Models\BusinessInfo;
use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BusinessApplicationReceivedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_uses_system_name_and_config_from_address(): void
    {
        Config::set('mail.from.address', 'from@example.com');
        Config::set('app.name', 'Fallback App');

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'system_name' => '子どもの習い事応援事業',
            'is_active' => true,
        ]);

        $businessInfo = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者株式会社',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => '代表',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '1-1-1',
            'phone' => '072-123-4567',
            'email' => 'applicant-envelope@example.com',
            'apply' => 0,
            'is_active' => 0,
        ]);

        $contactUrl = 'https://www.example.com/contact';
        $mailable = new BusinessApplicationReceivedMail($businessInfo, $subdomain, $contactUrl);
        $envelope = $mailable->envelope();

        $this->assertSame(
            '【子どもの習い事応援事業】事業者登録申請を受け付けました',
            $envelope->subject
        );
        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame('from@example.com', $envelope->from->address);
        $this->assertSame('子どもの習い事応援事業', $envelope->from->name);
    }

    public function test_render_includes_business_name_contact_url_and_footer_system_name(): void
    {
        Config::set('mail.from.address', 'from@example.com');

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'system_name' => '子どもの習い事応援事業',
            'is_active' => true,
        ]);

        $businessInfo = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者株式会社',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => '代表',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '1-1-1',
            'phone' => '072-123-4567',
            'email' => 'applicant-render@example.com',
            'apply' => 0,
            'is_active' => 0,
        ]);

        $contactUrl = 'https://www.example.com/contact';
        $mailable = new BusinessApplicationReceivedMail($businessInfo->fresh(), $subdomain, $contactUrl);
        $body = $mailable->render();

        $this->assertStringContainsString('テスト事業者株式会社', $body);
        $this->assertStringContainsString($contactUrl, $body);
        $this->assertStringContainsString('子どもの習い事応援事業 事務局', $body);
    }
}
