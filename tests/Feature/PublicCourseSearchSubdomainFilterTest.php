<?php

namespace Tests\Feature;

use App\Enums\CouponNotificationFrequency;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourseSearchSubdomainFilterTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private ClassroomInfo $ownClassroom;

    private ClassroomInfo $otherClassroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'itami',
            'is_active' => true,
            'voucher_expiry' => 0,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'othercity',
            'is_active' => true,
            'voucher_expiry' => 0,
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $this->ownClassroom = $this->createApprovedClassroom(
            $this->subdomain,
            $businessRole,
            'own_pcsf',
            '公開自サブドメイン教室',
            'own-pcsf@example.com',
            'own-classroom-pcsf@example.com'
        );

        $this->otherClassroom = $this->createApprovedClassroom(
            $otherSubdomain,
            $businessRole,
            'other_pcsf',
            '公開他サブドメイン教室',
            'other-pcsf@example.com',
            'other-classroom-pcsf@example.com'
        );
    }

    public function test_public_search_shows_only_current_subdomain_classrooms(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'itami.localhost'])
            ->get('/course/search?tab=condition');

        $response->assertOk();
        $response->assertSee('公開自サブドメイン教室', false);
        $response->assertDontSee('公開他サブドメイン教室', false);
    }

    public function test_public_show_returns_not_found_for_other_subdomain_classroom(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'itami.localhost'])
            ->get('/course/'.$this->otherClassroom->id);

        $response->assertNotFound();
    }

    private function createApprovedClassroom(
        Subdomain $subdomain,
        Role $businessRole,
        string $loginId,
        string $classroomName,
        string $businessEmail,
        string $classroomEmail
    ): ClassroomInfo {
        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => $loginId,
            'is_active' => true,
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => $classroomName.'事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => '代表者',
            'representative_name_kana' => 'ダイヒョウシャ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => 'テスト1-1',
            'phone' => '072-123-4567',
            'email' => $businessEmail,
            'email_timing' => CouponNotificationFrequency::Immediate->value,
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
        ]);

        $parentCategory = CourseParentCategory::create([
            'subdomain_id' => $subdomain->id,
            'name' => $classroomName.'親カテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $category = CourseCategory::create([
            'subdomain_id' => $subdomain->id,
            'parent_category_id' => $parentCategory->id,
            'name' => $classroomName.'カテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        return ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => $classroomName,
            'classroom_name_kana' => 'テストキョウシツ',
            'classroom_representative_name' => '教室責任者',
            'classroom_postal_code' => '664-0001',
            'classroom_prefecture' => '兵庫県',
            'classroom_city' => '伊丹市',
            'classroom_address1' => 'テスト1-1',
            'classroom_email' => $classroomEmail,
            'business_hours' => '平日10:00〜20:00',
            'holiday' => '日曜',
            'classroom_introduction' => '紹介',
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'apply' => 1,
            'is_active' => 1,
        ]);
    }
}
