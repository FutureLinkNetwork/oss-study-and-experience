<?php

namespace Tests\Feature;

use Tests\TestCase;

class HelloControllerTest extends TestCase
{
    /**
     * Hello Worldページが正常に表示されることをテスト
     */
    public function test_hello_page_displays_correctly(): void
    {
        $response = $this->get('/hello');

        $response->assertStatus(200);
        $response->assertSee('Hello World');
        $response->assertSee('Laravel 12でHello Worldページが正常に動作しています！');
    }

    /**
     * Hello Worldページが正しいビューを使用することをテスト
     */
    public function test_hello_page_uses_correct_view(): void
    {
        $response = $this->get('/hello');

        $response->assertViewIs('hello');
    }

    /**
     * ホームページへのリンクが正しく表示されることをテスト
     */
    public function test_hello_page_has_home_link(): void
    {
        $response = $this->get('/hello');

        $response->assertSee('ホームに戻る');
        $response->assertSee(route('welcome'));
    }
}
