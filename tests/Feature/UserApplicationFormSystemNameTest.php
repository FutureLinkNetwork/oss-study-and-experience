<?php

namespace Tests\Feature;

use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApplicationFormSystemNameTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 利用者申請フォームに Subdomain の system_name が表示されること
     */
    public function test_form_displays_subdomain_system_name(): void
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

        $response = $this->get('http://test.localhost/user_application');

        $response->assertStatus(200);
        $response->assertSee('このページは「'.$systemName.'」の申請画面です。', false);
        $response->assertSee('私は、'.$systemName.'における助成を申請するにあたり', false);
        $response->assertDontSee('伊丹市子どもの習い事応援事業', false);
    }
}
