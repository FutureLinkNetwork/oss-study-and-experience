<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
        ]);
    }

    /**
     * お問い合わせページが表示されること（www サブドメイン）
     */
    public function test_contact_page_displays(): void
    {
        $response = $this->get('http://www.localhost/contact');

        $response->assertStatus(200);
        $response->assertSee('お問い合わせ');
    }

    /**
     * 開発・テスト環境では reCAPTCHA なしでお問い合わせ送信が成功すること
     */
    public function test_contact_store_succeeds_without_recaptcha_when_disabled(): void
    {
        $this->assertFalse(config('recaptcha.enabled'));

        $data = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'phone' => '072-123-4567',
            'content' => '問い合わせ内容です。',
            'privacy_consent' => '1',
        ];

        $response = $this->post('http://www.localhost/contact', $data);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('success', 'お問い合わせを送信しました。ありがとうございます。');
        $this->assertDatabaseHas('contacts', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'content' => '問い合わせ内容です。',
        ]);
    }

    /**
     * reCAPTCHA 有効時、トークンなしで送信するとバリデーションエラーになること
     */
    public function test_contact_store_fails_without_token_when_recaptcha_enabled(): void
    {
        config(['recaptcha.enabled' => true]);

        try {
            $data = [
                'name' => 'テスト太郎',
                'email' => 'test@example.com',
                'phone' => '072-123-4567',
                'content' => '問い合わせ内容です。',
                'privacy_consent' => '1',
            ];

            $response = $this->post('http://www.localhost/contact', $data);

            $response->assertSessionHasErrors('g-recaptcha-response');
            $this->assertEquals(0, Contact::count());
        } finally {
            config(['recaptcha.enabled' => ! in_array(config('app.env'), ['local', 'testing'], true)]);
        }
    }
}
