<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessManagementGovernmentAgencyApplicantTypeTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $adminUser;

    private BusinessInfo $business;

    /**
     * @return array<string, mixed>
     */
    private function validBusinessPayload(): array
    {
        return [
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_title' => '代表取締役',
            'representative_family_name' => 'テスト',
            'representative_given_name' => '代表',
            'representative_title_kana' => 'ダイヒョウトリシマリヤク',
            'representative_family_name_kana' => 'テスト',
            'representative_given_name_kana' => 'ダイヒョウ',
            'representative_name' => 'テスト代表',
            'representative_name_kana' => 'テストダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'building_name' => '',
            'phone' => '072-123-4567',
            'fax' => '',
            'email' => 'gov-agency-test@example.com',
            'website_url' => '',
            'email_timing' => 'immediate',
            'contact_person' => '担当者',
            'contact_phone' => '072-123-4568',
            'document_person' => '宛名',
            'document_address' => '兵庫県伊丹市荻野1-1-1',
            'business_hours' => '9:00-18:00',
            'holiday' => '日曜',
            'bank_code' => '0001',
            'branch_code' => '001',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder_name' => 'テストジギョウシャ',
            'status' => '利用中',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_gov_agency_test',
            'is_active' => true,
        ]);

        $this->business = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => 'テスト代表',
            'representative_name_kana' => 'テストダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'gov-agency-test@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);
    }

    public function test_create_form_shows_government_agency_applicant_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.create'));

        $response->assertStatus(200);
        $response->assertSee('行政機関', false);
        $response->assertSee('value="government_agency"', false);
    }

    public function test_edit_form_shows_government_agency_applicant_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.edit', $this->business));

        $response->assertStatus(200);
        $response->assertSee('行政機関', false);
        $response->assertSee('applicant_type_government_agency', false);
    }

    public function test_update_accepts_government_agency_applicant_type(): void
    {
        $payload = $this->validBusinessPayload();
        $payload['applicant_type'] = 'government_agency';

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $this->business), $payload);

        $response->assertRedirect(route('admin.business.index'));

        $this->business->refresh();
        $this->assertSame('government_agency', $this->business->applicant_type);
    }

    public function test_update_syncs_representative_name_from_family_and_given(): void
    {
        $payload = $this->validBusinessPayload();
        $payload['representative_family_name'] = '山田';
        $payload['representative_given_name'] = '太郎';
        $payload['representative_family_name_kana'] = 'ヤマダ';
        $payload['representative_given_name_kana'] = 'タロウ';

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $this->business), $payload);

        $response->assertRedirect(route('admin.business.index'));

        $this->business->refresh();
        $this->assertSame('山田 太郎', $this->business->representative_name);
        $this->assertSame('ヤマダ タロウ', $this->business->representative_name_kana);
    }
}
