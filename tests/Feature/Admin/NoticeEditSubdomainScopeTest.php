<?php

namespace Tests\Feature\Admin;

use App\Models\Notice;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeEditSubdomainScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Subdomain, 1: Subdomain, 2: User, 3: Notice, 4: Notice}
     */
    private function createScopedFixtures(int $roleLevel = 80): array
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-notice-edit',
            'is_active' => true,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other-notice-edit',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => $roleLevel >= 100 ? 'super_admin' : 'subdomain_admin',
            'level' => $roleLevel,
            'is_active' => true,
            'is_global' => $roleLevel >= 100,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_notice_edit_scope_'.$roleLevel,
            'is_active' => true,
        ]);

        $sameSubdomainNotice = Notice::create([
            'subdomain_id' => $currentSubdomain->id,
            'title' => '同一サブドメインお知らせ',
            'content' => '同一サブドメインの本文',
            'notice_date' => now()->toDateString(),
            'show_on_public' => false,
            'show_on_user_dashboard' => false,
            'show_on_business_dashboard' => false,
            'is_deleted' => false,
            'created_user' => $adminUser->id,
            'updated_user' => $adminUser->id,
        ]);

        $otherSubdomainNotice = Notice::create([
            'subdomain_id' => $otherSubdomain->id,
            'title' => '他サブドメインお知らせ',
            'content' => '他サブドメインの本文',
            'notice_date' => now()->toDateString(),
            'show_on_public' => false,
            'show_on_user_dashboard' => false,
            'show_on_business_dashboard' => false,
            'is_deleted' => false,
            'created_user' => $adminUser->id,
            'updated_user' => $adminUser->id,
        ]);

        return [$currentSubdomain, $otherSubdomain, $adminUser, $sameSubdomainNotice, $otherSubdomainNotice];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function url(string $name, array $params = []): string
    {
        return 'http://current-notice-edit.localhost'.route($name, $params, false);
    }

    public function test_edit_rejects_notice_from_another_subdomain(): void
    {
        [, , $adminUser, , $otherSubdomainNotice] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get($this->url('admin.notices.edit', [$otherSubdomainNotice]));

        $response->assertStatus(403);
    }

    public function test_edit_rejects_notice_from_another_subdomain_for_system_admin(): void
    {
        [, , $adminUser, , $otherSubdomainNotice] = $this->createScopedFixtures(100);

        $response = $this->actingAs($adminUser)
            ->get($this->url('admin.notices.edit', [$otherSubdomainNotice]));

        $response->assertStatus(403);
    }

    public function test_edit_allows_notice_in_current_subdomain(): void
    {
        [, , $adminUser, $sameSubdomainNotice] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get($this->url('admin.notices.edit', [$sameSubdomainNotice]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.notices.form');
        $response->assertSee($sameSubdomainNotice->title);
    }

    public function test_update_rejects_notice_from_another_subdomain(): void
    {
        [, , $adminUser, , $otherSubdomainNotice] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->put($this->url('admin.notices.update', [$otherSubdomainNotice]), [
                'subdomain_id' => $otherSubdomainNotice->subdomain_id,
                'title' => '改ざんされたタイトル',
                'content' => '改ざんされた本文',
                'notice_date' => now()->format('Y-m-d'),
                'show_on_public' => false,
                'show_on_user_dashboard' => false,
                'show_on_business_dashboard' => false,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('notices', [
            'id' => $otherSubdomainNotice->id,
            'title' => '他サブドメインお知らせ',
        ]);
    }

    public function test_destroy_rejects_notice_from_another_subdomain(): void
    {
        [, , $adminUser, , $otherSubdomainNotice] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->delete($this->url('admin.notices.destroy', [$otherSubdomainNotice]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('notices', [
            'id' => $otherSubdomainNotice->id,
            'is_deleted' => false,
        ]);
    }
}
