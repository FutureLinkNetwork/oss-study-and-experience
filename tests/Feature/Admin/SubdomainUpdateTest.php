<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdomainUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'itami',
            'name' => '伊丹市',
            'name_kana' => 'イタミシ',
            'system_name' => '伊丹市習い事クーポン',
            'description' => 'テスト説明',
            'voucher_amount' => 10000,
            'voucher_expiry' => 12,
            'voucher_publish_date' => 1,
        ]);

        $role = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => '管理者',
            'is_global' => false,
            'level' => 80,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'subdomain_admin_'.uniqid(),
            'email' => 'subdomain_admin_'.uniqid().'@example.com',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '伊丹市',
            'name_kana' => 'イタミシ',
            'system_name' => '伊丹市習い事クーポン',
            'description' => 'テスト説明',
            'voucher_amount' => 10000,
            'voucher_expiry' => 12,
            'voucher_publish_date' => 1,
            'grades' => [],
        ], $overrides);
    }

    public function test_edit_page_shows_municipality_section(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('http://itami.localhost/admin/subdomain/edit');

        $response->assertStatus(200);
        $response->assertSee('自治体情報');
        $response->assertSee('自治体名');
        $response->assertSee('自治体名カナ');
    }

    public function test_admin_can_update_name_and_name_kana(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put('http://itami.localhost/admin/subdomain', $this->validPayload([
                'name' => 'テスト市',
                'name_kana' => 'テストシ',
            ]));

        $response->assertRedirect(route('admin.subdomain.edit'));
        $response->assertSessionHas('success');

        $this->subdomain->refresh();
        $this->assertSame('テスト市', $this->subdomain->name);
        $this->assertSame('テストシ', $this->subdomain->name_kana);
    }

    public function test_name_and_name_kana_are_required(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->from('http://itami.localhost/admin/subdomain/edit')
            ->put('http://itami.localhost/admin/subdomain', $this->validPayload([
                'name' => '',
                'name_kana' => '',
            ]));

        $response->assertRedirect('http://itami.localhost/admin/subdomain/edit');
        $response->assertSessionHasErrors(['name', 'name_kana']);
    }

    public function test_name_kana_must_be_fullwidth_katakana(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->from('http://itami.localhost/admin/subdomain/edit')
            ->put('http://itami.localhost/admin/subdomain', $this->validPayload([
                'name_kana' => 'いたみし',
            ]));

        $response->assertRedirect('http://itami.localhost/admin/subdomain/edit');
        $response->assertSessionHasErrors(['name_kana']);
    }
}
