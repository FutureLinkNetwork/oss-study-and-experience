<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessEditSubdomainScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Subdomain, 1: Subdomain, 2: User, 3: BusinessInfo, 4: BusinessInfo}
     */
    private function createScopedFixtures(): array
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-business-edit',
            'is_active' => true,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other-business-edit',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_business_edit_scope',
            'is_active' => true,
        ]);

        $sameSubdomainBusiness = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $currentSubdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => '同一サブドメイン事業者',
            'business_name_kana' => 'ドウイツサブドメインジギョウシャ',
            'representative_name' => '同一代表',
            'representative_name_kana' => 'ドウイツダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-111-1111',
            'email' => 'same-subdomain-edit@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $otherSubdomainBusiness = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $otherSubdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => '他サブドメイン事業者',
            'business_name_kana' => 'タサブドメインジギョウシャ',
            'representative_name' => '他代表',
            'representative_name_kana' => 'タダイヒョウ',
            'postal_code' => '664-0002',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野2-2-2',
            'phone' => '072-222-2222',
            'email' => 'other-subdomain-edit@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        return [$currentSubdomain, $otherSubdomain, $adminUser, $sameSubdomainBusiness, $otherSubdomainBusiness];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function url(string $name, array $params = []): string
    {
        return 'http://current-business-edit.localhost'.route($name, $params, false);
    }

    public function test_edit_rejects_business_from_another_subdomain(): void
    {
        [, , $adminUser, , $otherSubdomainBusiness] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get($this->url('admin.business.edit', [$otherSubdomainBusiness]));

        $response->assertStatus(403);
    }

    public function test_edit_allows_business_in_current_subdomain(): void
    {
        [, , $adminUser, $sameSubdomainBusiness] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->get($this->url('admin.business.edit', [$sameSubdomainBusiness]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.business.edit');
        $response->assertSee($sameSubdomainBusiness->business_name);
    }

    public function test_update_rejects_business_from_another_subdomain(): void
    {
        [, , $adminUser, , $otherSubdomainBusiness] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->put($this->url('admin.business.update', [$otherSubdomainBusiness]), [
                'applicant_type' => 'corporation',
                'business_name' => '改ざんされた事業者名',
                'business_name_kana' => 'カイザンサレタジギョウシャメイ',
                'representative_title' => '代表取締役',
                'representative_family_name' => '改ざん',
                'representative_given_name' => '代表',
                'representative_title_kana' => 'ダイヒョウトリシマリヤク',
                'representative_family_name_kana' => 'カイザン',
                'representative_given_name_kana' => 'ダイヒョウ',
                'postal_code' => '664-0002',
                'prefecture' => '兵庫県',
                'city' => '伊丹市',
                'address1' => '荻野2-2-2',
                'phone' => '072-222-2222',
                'email' => 'other-subdomain-edit@example.com',
                'email_timing' => 'immediate',
                'contact_person' => '担当者',
                'contact_phone' => '072-222-2223',
                'document_person' => '宛名',
                'document_address' => '兵庫県伊丹市荻野2-2-2',
                'business_hours' => '9:00-18:00',
                'holiday' => '日曜',
                'bank_code' => '0001',
                'branch_code' => '001',
                'account_type' => '普通',
                'account_number' => '1234567',
                'account_holder_name' => 'タサブドメイン',
                'status' => '利用中',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('business_infos', [
            'id' => $otherSubdomainBusiness->id,
            'business_name' => '他サブドメイン事業者',
        ]);
    }
}
