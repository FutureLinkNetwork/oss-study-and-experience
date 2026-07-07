<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsurePasswordChangedMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'itami',
            'is_active' => true,
        ]);
    }

    private function createUser(string $roleName, ?string $lastLoginAt): User
    {
        $role = Role::factory()->create([
            'name' => $roleName,
            'level' => 10,
            'is_active' => true,
        ]);

        return User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $role->id,
            'login_id' => $roleName,
            'last_login_at' => $lastLoginAt,
            'is_active' => true,
        ]);
    }

    /**
     * 初回ログインの利用者はダッシュボードからパスワード変更画面へリダイレクトされること
     */
    public function test_first_login_user_is_redirected_from_dashboard(): void
    {
        $user = $this->createUser('subdomain_user', null);

        $response = $this->actingAs($user)
            ->get('http://itami.localhost/user');

        $response->assertRedirectToRoute('user.password.change');
    }

    /**
     * 初回ログインの利用者はQRコード等で教室詳細へ直接遷移してもパスワード変更画面へリダイレクトされること
     */
    public function test_first_login_user_is_redirected_from_course_show(): void
    {
        $user = $this->createUser('subdomain_user', null);

        $response = $this->actingAs($user)
            ->get('http://itami.localhost/user/course/1');

        $response->assertRedirectToRoute('user.password.change');
    }

    /**
     * 初回ログインの利用者はパスワード変更画面自体にはアクセスできること
     */
    public function test_first_login_user_can_access_password_change_page(): void
    {
        $user = $this->createUser('subdomain_user', null);

        $response = $this->actingAs($user)
            ->get('http://itami.localhost/user/password/change');

        $response->assertStatus(200);
    }

    /**
     * 既にログイン済み（last_login_atあり）の利用者はリダイレクトされないこと
     */
    public function test_logged_in_user_is_not_redirected(): void
    {
        $user = $this->createUser('subdomain_user', now());

        $response = $this->actingAs($user)
            ->get('http://itami.localhost/user');

        $response->assertStatus(200);
    }

    /**
     * 初回ログインの事業者はダッシュボードからパスワード変更画面へリダイレクトされること
     */
    public function test_first_login_business_is_redirected_from_dashboard(): void
    {
        $user = $this->createUser('subdomain_business', null);

        $response = $this->actingAs($user)
            ->get('http://itami.localhost/business');

        $response->assertRedirectToRoute('business.password.change');
    }
}
