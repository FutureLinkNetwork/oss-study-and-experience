<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
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
     * 管理者ロールで /user にアクセスすると 403 になること
     */
    public function test_admin_cannot_access_user_area(): void
    {
        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)
            ->get('http://www.localhost/user');

        $response->assertStatus(403);
    }

    /**
     * サブドメイン利用者で /user にアクセスすると 200 になること
     */
    public function test_subdomain_user_can_access_user_area(): void
    {
        $userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'user1',
            'last_login_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('http://www.localhost/user');

        $response->assertStatus(200);
    }

    /**
     * 利用者ロールで /business にアクセスすると 403 になること
     */
    public function test_subdomain_user_cannot_access_business_area(): void
    {
        $userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'user1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('http://www.localhost/business');

        $response->assertStatus(403);
    }

    /**
     * サブドメイン事業者で /business にアクセスすると 200 になること
     */
    public function test_subdomain_business_can_access_business_area(): void
    {
        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'business1',
            'last_login_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($businessUser)
            ->get('http://www.localhost/business');

        $response->assertStatus(200);
    }

    /**
     * 利用者ロールで /admin にアクセスすると 403 になること
     */
    public function test_subdomain_user_cannot_access_admin_area(): void
    {
        $userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'user1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('http://www.localhost/admin');

        $response->assertStatus(403);
    }

    /**
     * 管理者ロールで /admin にアクセスすると 200 になること
     */
    public function test_admin_can_access_admin_area(): void
    {
        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)
            ->get('http://www.localhost/admin');

        $response->assertStatus(200);
    }
}
