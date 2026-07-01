<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessManagementIndexYoungestClassroomTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $adminUser;

    private BusinessInfo $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test-youngest-classroom',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_youngest_classroom_test',
            'is_active' => true,
        ]);

        $this->business = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者（一覧表示テスト）',
            'business_name_kana' => 'テストジギョウシャ（イチランヒョウジ）',
            'representative_name' => 'テスト代表',
            'representative_name_kana' => 'テストダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'youngest-classroom-test@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);
    }

    public function test_admin_business_index_shows_youngest_classroom_name_instead_of_phone(): void
    {
        // 「IDが一番若い」= 最小のIDを持つ教室名を表示する
        $classroom1 = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => '教室A（最小ID）',
        ]);

        $classroom2 = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => '教室B（追加）',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.index'));

        $response->assertStatus(200);
        $response->assertSee('最若い教室名');
        $response->assertSee($classroom1->classroom_name);
        $response->assertDontSee($this->business->phone);
        $response->assertDontSee($classroom2->classroom_name);
    }
}
