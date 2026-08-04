<?php

namespace Tests\Feature\Admin;

use App\Models\Contact;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactEditSubdomainScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Subdomain, 1: Subdomain, 2: User, 3: Contact, 4: Contact}
     */
    private function createScopedFixtures(): array
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-contact-edit',
            'is_active' => true,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other-contact-edit',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 60,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_contact_edit_scope',
            'is_active' => true,
        ]);

        $sameSubdomainContact = Contact::create([
            'subdomain_id' => $currentSubdomain->id,
            'name' => '同一サブドメイン問い合わせ者',
            'email' => 'same-subdomain-contact@example.com',
            'phone' => '072-111-1111',
            'content' => '同一サブドメインの問い合わせ内容',
            'is_confirmed' => 0,
        ]);

        $otherSubdomainContact = Contact::create([
            'subdomain_id' => $otherSubdomain->id,
            'name' => '他サブドメイン問い合わせ者',
            'email' => 'other-subdomain-contact@example.com',
            'phone' => '072-222-2222',
            'content' => '他サブドメインの問い合わせ内容',
            'is_confirmed' => 0,
        ]);

        return [$currentSubdomain, $otherSubdomain, $adminUser, $sameSubdomainContact, $otherSubdomainContact];
    }

    public function test_show_rejects_contact_from_another_subdomain(): void
    {
        [, , $adminUser, , $otherSubdomainContact] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get('http://current-contact-edit.localhost/admin/contacts/'.$otherSubdomainContact->id);

        $response->assertStatus(403);
    }

    public function test_show_allows_contact_in_current_subdomain(): void
    {
        [, , $adminUser, $sameSubdomainContact] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get('http://current-contact-edit.localhost/admin/contacts/'.$sameSubdomainContact->id);

        $response->assertStatus(200);
        $response->assertSee($sameSubdomainContact->content);
    }

    public function test_update_rejects_contact_from_another_subdomain(): void
    {
        [, , $adminUser, , $otherSubdomainContact] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->put('http://current-contact-edit.localhost/admin/contacts/'.$otherSubdomainContact->id, [
                'remarks' => '改ざんされた備考',
                'is_confirmed' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('contacts', [
            'id' => $otherSubdomainContact->id,
            'is_confirmed' => 0,
        ]);
    }
}
