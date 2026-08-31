<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessClassroomMapToggleTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $adminUser;

    private BusinessInfo $business;

    private ClassroomInfo $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test-classroom-map',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_classroom_map_test',
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
            'email' => 'classroom-map-test@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => '地図非表示教室',
            'use_map' => false,
        ]);
    }

    public function test_edit_classroom_initializes_map_when_use_map_checkbox_is_unchecked(): void
    {
        $path = route('admin.business.edit-classroom', [$this->business, $this->classroom], false);

        $response = $this->actingAs($this->adminUser)
            ->get('http://test-classroom-map.localhost'.$path);

        $response->assertStatus(200);
        $response->assertSee('id="use-map-checkbox"', false);
        $response->assertSee('id="classroom-map"', false);
        $response->assertSee('classroom-map-section', false);
        $response->assertSee('display:none;', false);
        $response->assertSee('function ensureClassroomMap()', false);
        $response->assertSee('if (useMap) {', false);
        $response->assertSee('ensureClassroomMap();', false);
        $response->assertSee('classroomMap.invalidateSize();', false);
    }
}
