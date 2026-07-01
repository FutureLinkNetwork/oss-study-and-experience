<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BasicAuthMiddlewareTest extends TestCase
{
    private const TEST_USERNAME = 'testuser';

    private const TEST_PASSWORD = 'testpass';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('basic_auth.username', self::TEST_USERNAME);
        Config::set('basic_auth.password', self::TEST_PASSWORD);
        Config::set('basic_auth.enabled', true);
        Config::set('basic_auth.routes.user_login', true);
        Config::set('basic_auth.routes.business_login', true);
        Config::set('basic_auth.routes.admin_login', true);
    }

    public function test_user_login_returns_401_without_basic_auth_when_enabled(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(401);
        $response->assertHeader('WWW-Authenticate');
    }

    public function test_user_login_passes_middleware_with_valid_basic_auth(): void
    {
        $response = $this->withBasicAuthCredentials(self::TEST_USERNAME, self::TEST_PASSWORD)
            ->get('/login');

        $this->assertNotEquals(401, $response->status(), 'Basic auth middleware should pass with valid credentials');
    }

    public function test_user_login_returns_401_with_invalid_basic_auth(): void
    {
        $response = $this->withBasicAuthCredentials(self::TEST_USERNAME, 'wrongpassword')
            ->get('/login');

        $response->assertStatus(401);
    }

    public function test_user_login_post_returns_401_without_basic_auth_when_enabled(): void
    {
        $response = $this->post('/login', [
            'login_id' => 'dummy',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_business_login_returns_401_without_basic_auth_when_enabled(): void
    {
        $response = $this->get('/business/login');

        $response->assertStatus(401);
    }

    public function test_business_login_passes_middleware_with_valid_basic_auth(): void
    {
        $response = $this->withBasicAuthCredentials(self::TEST_USERNAME, self::TEST_PASSWORD)
            ->get('/business/login');

        $this->assertNotEquals(401, $response->status(), 'Basic auth middleware should pass with valid credentials');
    }

    public function test_admin_login_returns_401_without_basic_auth_when_enabled(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(401);
    }

    public function test_admin_login_passes_middleware_with_valid_basic_auth(): void
    {
        $response = $this->withBasicAuthCredentials(self::TEST_USERNAME, self::TEST_PASSWORD)
            ->get('/admin/login');

        $this->assertNotEquals(401, $response->status(), 'Basic auth middleware should pass with valid credentials');
    }

    public function test_login_passes_through_when_basic_auth_disabled(): void
    {
        Config::set('basic_auth.enabled', false);

        $response = $this->get('/login');

        $this->assertNotEquals(401, $response->status(), 'Basic auth should be skipped when disabled');
    }

    public function test_business_login_passes_through_when_business_route_disabled(): void
    {
        Config::set('basic_auth.routes.business_login', false);

        $response = $this->get('/business/login');

        $this->assertNotEquals(401, $response->status(), 'Basic auth should be skipped for business when route disabled');
    }

    public function test_user_login_still_requires_basic_auth_when_business_disabled(): void
    {
        Config::set('basic_auth.routes.business_login', false);

        $response = $this->get('/login');

        $response->assertStatus(401);
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return $this
     */
    private function withBasicAuthCredentials(string $username, string $password, array $headers = []): self
    {
        $credentials = base64_encode("{$username}:{$password}");

        return $this->withHeaders(array_merge($headers, [
            'Authorization' => 'Basic '.$credentials,
        ]));
    }
}
