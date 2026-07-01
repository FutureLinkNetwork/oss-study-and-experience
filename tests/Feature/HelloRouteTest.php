<?php

namespace Tests\Feature;

use Tests\TestCase;

class HelloRouteTest extends TestCase
{
    /**
     * /helloルートが正しく定義されていることをテスト
     */
    public function test_hello_route_is_defined(): void
    {
        $this->assertTrue(route('hello') === url('/hello'));
    }

    /**
     * /helloルートがGETメソッドでアクセス可能であることをテスト
     */
    public function test_hello_route_accepts_get_method(): void
    {
        $response = $this->get(route('hello'));

        $response->assertStatus(200);
    }

    /**
     * /helloルートがPOSTメソッドを拒否することをテスト
     */
    public function test_hello_route_rejects_post_method(): void
    {
        $response = $this->post(route('hello'));

        $response->assertStatus(405); // Method Not Allowed
    }
}
