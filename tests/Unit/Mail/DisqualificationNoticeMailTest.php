<?php

namespace Tests\Unit\Mail;

use App\Mail\DisqualificationNoticeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DisqualificationNoticeMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_uses_system_name_and_config_from_address(): void
    {
        Config::set('mail.from.address', 'from@example.com');

        $systemName = '伊丹市子どもの習い事応援事業';
        $mailable = new DisqualificationNoticeMail($systemName);
        $envelope = $mailable->envelope();

        $this->assertSame(
            '伊丹市子どもの習い事応援事業にかかる資格要件の審査結果について',
            $envelope->subject
        );
        $this->assertInstanceOf(Address::class, $envelope->from);
        $this->assertSame('from@example.com', $envelope->from->address);
        $this->assertSame($systemName, $envelope->from->name);
    }

    public function test_render_includes_specified_body(): void
    {
        Config::set('mail.from.address', 'from@example.com');

        $systemName = '伊丹市子どもの習い事応援事業';
        $mailable = new DisqualificationNoticeMail($systemName);
        $body = $mailable->render();

        $this->assertStringContainsString('いつもお世話になります。伊丹市子どもの習い事応援事業事務局です。', $body);
        $this->assertStringContainsString('今月のクーポン付与にかかる審査におきまして、本事業の対象要件（就学援助等）を確認できなかったため、クーポンの付与はございません。', $body);
        $this->assertStringContainsString('既に付与済のクーポンについては、今年度末までご利用いただけます。', $body);
        $this->assertStringContainsString('ご不明な点がありましたら、利用者マイページ内の「お問い合わせ」より事務局までお問い合わせください。', $body);
    }
}
