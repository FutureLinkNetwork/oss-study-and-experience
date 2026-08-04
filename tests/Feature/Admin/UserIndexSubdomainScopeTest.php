<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIndexSubdomainScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_only_users_in_the_current_subdomain(): void
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-admin-users',
            'is_active' => true,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other-admin-users',
            'is_active' => true,
        ]);

        $globalAdminRole = Role::factory()->create([
            'name' => 'super_admin',
            'is_global' => true,
            'level' => 100,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $otherSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'global_admin_user_index',
            'name' => 'グローバル管理者',
            'is_active' => true,
        ]);

        $visibleUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'visible_user_index',
            'name' => '表示対象ユーザー',
            'email' => 'visible-user@example.com',
            'is_active' => true,
        ]);

        $hiddenUser = User::factory()->create([
            'subdomain_id' => $otherSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'hidden_user_index',
            'name' => '他サブドメインユーザー',
            'email' => 'hidden-user@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)
            ->get('http://current-admin-users.localhost/admin/users');

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertSee($visibleUser->name);
        $response->assertDontSee($hiddenUser->name);
    }

    public function test_edit_rejects_user_from_another_subdomain(): void
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-admin-users-edit',
            'is_active' => true,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other-admin-users-edit',
            'is_active' => true,
        ]);

        $globalAdminRole = Role::factory()->create([
            'name' => 'super_admin',
            'is_global' => true,
            'level' => 100,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'global_admin_user_edit',
            'name' => 'グローバル管理者',
            'is_active' => true,
        ]);

        $otherSubdomainUser = User::factory()->create([
            'subdomain_id' => $otherSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'other_subdomain_edit_user',
            'name' => '他サブドメイン編集対象',
            'email' => 'other-edit-user@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)
            ->get('http://current-admin-users-edit.localhost/admin/users/'.$otherSubdomainUser->id.'/edit');

        $response->assertStatus(403);
    }

    public function test_edit_allows_user_in_current_subdomain(): void
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-admin-users-edit-ok',
            'is_active' => true,
        ]);

        $globalAdminRole = Role::factory()->create([
            'name' => 'super_admin',
            'is_global' => true,
            'level' => 100,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'global_admin_user_edit_ok',
            'name' => 'グローバル管理者',
            'is_active' => true,
        ]);

        $targetUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'same_subdomain_edit_user',
            'name' => '同一サブドメイン編集対象',
            'email' => 'same-edit-user@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)
            ->get('http://current-admin-users-edit-ok.localhost/admin/users/'.$targetUser->id.'/edit');

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.user-form');
        $response->assertSee($targetUser->name);
    }

    public function test_update_rejects_user_from_another_subdomain(): void
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-admin-users-update',
            'is_active' => true,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other-admin-users-update',
            'is_active' => true,
        ]);

        $globalAdminRole = Role::factory()->create([
            'name' => 'super_admin',
            'is_global' => true,
            'level' => 100,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'global_admin_user_update',
            'name' => 'グローバル管理者',
            'is_active' => true,
        ]);

        $otherSubdomainUser = User::factory()->create([
            'subdomain_id' => $otherSubdomain->id,
            'role_id' => $globalAdminRole->id,
            'login_id' => 'other_subdomain_update_user',
            'name' => '他サブドメイン更新対象',
            'email' => 'other-update-user@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)
            ->put('http://current-admin-users-update.localhost/admin/users/'.$otherSubdomainUser->id, [
                'name' => '改ざんされた名前',
                'display_name' => null,
                'login_id' => $otherSubdomainUser->login_id,
                'email' => $otherSubdomainUser->email,
                'role_id' => $globalAdminRole->id,
                'is_active' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $otherSubdomainUser->id,
            'name' => '他サブドメイン更新対象',
        ]);
    }
}
