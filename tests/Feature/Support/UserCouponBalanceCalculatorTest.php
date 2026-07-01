<?php

namespace Tests\Feature\Support;

use App\Enums\CouponNotificationFrequency;
use App\Models\Beneficiary;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Support\UserCouponBalanceCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCouponBalanceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 今年度の発行分・今年度の利用のみを残高計算に含めること
     */
    public function test_balance_uses_only_current_fiscal_year_vouchers_and_usages(): void
    {
        $this->travelTo(Carbon::parse('2026-05-15', 'Asia/Tokyo'));

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
            'voucher_expiry' => 0,
        ]);

        $userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'balance_user',
            'last_login_at' => now(),
            'is_active' => true,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'user_id' => $user->id,
        ]);

        Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'FY26-VOUCHER',
            'issue_date' => '2026-04-10',
            'expiry_date' => '2027-03-31',
            'amount' => 10_000,
            'status' => 'unused',
        ]);

        Voucher::create([
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'voucher_number' => 'FY25-VOUCHER',
            'issue_date' => '2025-06-01',
            'expiry_date' => '2027-03-31',
            'amount' => 99_999,
            'status' => 'unused',
        ]);

        $businessUser = $this->createBusinessUser($subdomain);
        [$business, $classroom] = $this->createBusinessAndClassroom($subdomain, $businessUser);

        VoucherUsage::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => null,
            'amount' => 2_000,
            'used_at' => Carbon::parse('2026-04-20 10:00:00', 'Asia/Tokyo'),
            'memo' => '',
            'is_cancelled' => false,
        ]);

        VoucherUsage::create([
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => null,
            'amount' => 9_999,
            'used_at' => Carbon::parse('2025-11-01 10:00:00', 'Asia/Tokyo'),
            'memo' => '',
            'is_cancelled' => false,
        ]);

        $result = UserCouponBalanceCalculator::calculate($user);
        $resultForBeneficiary = UserCouponBalanceCalculator::calculateForBeneficiary($beneficiary);

        $this->assertSame(8_000, $result['balance']);
        $this->assertSame($result['balance'], $resultForBeneficiary['balance']);
        $this->assertEquals($result['expiry_date']?->toDateString(), $resultForBeneficiary['expiry_date']?->toDateString());
        $this->assertNotNull($result['expiry_date']);
        $this->assertSame('2027-03-31', $result['expiry_date']->toDateString());

        $response = $this->actingAs($user)->get('http://www.localhost/user');

        $response->assertOk();
        $response->assertViewHas('voucherBalance', 8_000);
    }

    private function createBusinessUser(Subdomain $subdomain): User
    {
        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        return User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'balance_business',
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: BusinessInfo, 1: ClassroomInfo}
     */
    private function createBusinessAndClassroom(Subdomain $subdomain, User $businessUser): array
    {
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
            'email' => 'business-balance@example.com',
            'email_timing' => CouponNotificationFrequency::Daily->value,
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
            'classroom_email' => 'classroom-balance@example.com',
            'business_hours' => '9:00-18:00',
            'holiday' => '日曜',
            'classroom_introduction' => '紹介文',
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'apply' => 1,
            'is_active' => 1,
        ]);

        return [$business, $classroom];
    }
}
