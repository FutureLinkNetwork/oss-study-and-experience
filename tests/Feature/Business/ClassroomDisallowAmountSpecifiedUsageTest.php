<?php

namespace Tests\Feature\Business;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomDisallowAmountSpecifiedUsageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 事業者が教室更新で disallow_amount_specified_usage を保存できること
     */
    public function test_business_can_update_disallow_amount_specified_usage(): void
    {
        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'biz_classroom_'.uniqid(),
            'is_active' => true,
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => '代表者',
            'representative_name_kana' => 'ダイヒョウシャ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => 'テスト1-1',
            'phone' => '072-123-4567',
            'email' => 'biz_classroom_'.uniqid().'@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
        ]);

        $parentCategory = CourseParentCategory::create([
            'subdomain_id' => $subdomain->id,
            'name' => '親カテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $category = CourseCategory::create([
            'subdomain_id' => $subdomain->id,
            'parent_category_id' => $parentCategory->id,
            'name' => 'テストカテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $businessUser->id,
            'updated_user_id' => $businessUser->id,
        ]);

        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'テスト教室',
            'classroom_name_kana' => 'テストキョウシツ',
            'classroom_representative_name' => '教室責任者',
            'classroom_postal_code' => '664-0001',
            'classroom_prefecture' => '兵庫県',
            'classroom_city' => '伊丹市',
            'classroom_address1' => 'テスト1-1',
            'classroom_email' => 'classroom@example.com',
            'business_hours' => '9:00-18:00',
            'holiday' => '日曜',
            'classroom_introduction' => '紹介文',
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'apply' => 1,
            'is_active' => 1,
            'disallow_amount_specified_usage' => false,
        ]);

        $this->assertFalse($classroom->fresh()->disallow_amount_specified_usage);

        $response = $this->actingAs($businessUser)->put(
            'http://www.localhost/business/classrooms/'.$classroom->id,
            [
                'classroom_introduction' => '更新後',
                'is_active' => '1',
                'disallow_amount_specified_usage' => '1',
            ]
        );

        $response->assertRedirect(route('business.classrooms.show', $classroom));
        $this->assertTrue($classroom->fresh()->disallow_amount_specified_usage);

        $this->actingAs($businessUser)->put(
            'http://www.localhost/business/classrooms/'.$classroom->id,
            [
                'classroom_introduction' => '更新後',
                'is_active' => '1',
            ]
        );

        $this->assertFalse($classroom->fresh()->disallow_amount_specified_usage);
    }
}
