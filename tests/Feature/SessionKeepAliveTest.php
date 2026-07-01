<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionKeepAliveTest extends TestCase
{
    public function test_session_keep_alive_returns_json_with_csrf_token(): void
    {
        $response = $this->get(route('session.keep_alive'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/json');
        $response->assertJsonStructure(['token']);
        $this->assertNotSame('', $response->json('token'));
    }
}
