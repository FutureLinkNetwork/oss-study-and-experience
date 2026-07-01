<?php

namespace Tests\Feature\Business;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApplicationExportTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $businessUser;

    private BusinessInfo $business;

    private ClassroomInfo $classroom;

    private CourseInfo $course;

    private User $consumerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
        ]);

        $businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);

        $userRole = Role::create([
            'name' => 'subdomain_user',
            'display_name' => '利用者',
            'is_global' => false,
            'level' => 10,
            'is_active' => true,
        ]);

        $this->businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'biz_export_'.uniqid(),
            'name' => '事業者ユーザー',
            'email' => 'biz_export_'.uniqid().'@example.com',
        ]);

        $this->consumerUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'usr_export_'.uniqid(),
            'name' => '申込者太郎',
            'email' => 'usr_export_'.uniqid().'@example.com',
        ]);

        $this->business = BusinessInfo::create([
            'user_id' => $this->businessUser->id,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Business',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'b1@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);

        $this->classroom = ClassroomInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_name' => 'Test Classroom',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);

        $this->course = CourseInfo::create([
            'business_info_id' => $this->business->id,
            'classroom_info_id' => $this->classroom->id,
            'course_name' => 'Test Course',
            'price' => 5000,
            'is_active' => 1,
        ]);
    }

    public function test_export_returns_csv_with_headers_when_authenticated(): void
    {
        VoucherUsage::create([
            'user_id' => $this->consumerUser->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $this->business->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => $this->course->id,
            'amount' => 5000,
            'used_at' => now()->subDay(),
            'is_cancelled' => false,
        ]);

        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/applications/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=Shift_JIS');
        $body = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('申込日', $body);
        $this->assertStringContainsString('申込教室', $body);
        $this->assertStringContainsString('コース', $body);
        $this->assertStringContainsString('金額', $body);
        $this->assertStringContainsString('申込者名', $body);
        $this->assertStringContainsString('状態', $body);
        $this->assertStringContainsString('事業者メモ', $body);
        $this->assertStringContainsString('申込者太郎', $body);
        $this->assertStringContainsString('Test Classroom', $body);
        $this->assertStringContainsString('Test Course', $body);
    }

    public function test_export_respects_search_filters(): void
    {
        $usedAt = Carbon::create(2026, 2, 15, 10, 0);
        VoucherUsage::create([
            'user_id' => $this->consumerUser->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $this->business->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => $this->course->id,
            'amount' => 5000,
            'used_at' => $usedAt,
            'is_cancelled' => false,
        ]);

        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/applications/export?year=2026&month=2&cancelled=exclude');

        $response->assertStatus(200);
        $body = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('申込者太郎', $body);

        $responseExclude = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/applications/export?year=2025&month=1');
        $bodyExclude = mb_convert_encoding($responseExclude->streamedContent(), 'UTF-8', 'SJIS-win');
        $lines = explode("\n", trim($bodyExclude));
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('申込日', $lines[0]);
    }

    public function test_export_returns_headers_only_when_no_results(): void
    {
        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/applications/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=Shift_JIS');
        $body = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('事業者メモ', $body);
        $lines = explode("\n", trim($body));
        $this->assertCount(1, $lines);
    }

    public function test_export_requires_authentication(): void
    {
        $response = $this->get('http://www.localhost/business/applications/export');

        $response->assertRedirect();
    }

    public function test_export_includes_only_own_business_data(): void
    {
        VoucherUsage::create([
            'user_id' => $this->consumerUser->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $this->business->id,
            'classroom_info_id' => $this->classroom->id,
            'course_info_id' => $this->course->id,
            'amount' => 5000,
            'used_at' => now()->subDay(),
            'is_cancelled' => false,
        ]);

        $otherBusinessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->businessUser->role_id,
            'login_id' => 'other_biz_'.uniqid(),
            'email' => 'other_biz_'.uniqid().'@example.com',
        ]);
        $otherBusiness = BusinessInfo::create([
            'user_id' => $otherBusinessUser->id,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Other Business',
            'business_name_kana' => '他',
            'representative_name' => 'Other',
            'representative_name_kana' => 'タ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '2-2',
            'phone' => '0300000001',
            'email' => 'other@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $otherClassroom = ClassroomInfo::create([
            'business_info_id' => $otherBusiness->id,
            'classroom_name' => 'Other Classroom',
            'classroom_name_kana' => '他',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $otherCourse = CourseInfo::create([
            'business_info_id' => $otherBusiness->id,
            'classroom_info_id' => $otherClassroom->id,
            'course_name' => 'Other Course',
            'price' => 3000,
            'is_active' => 1,
        ]);
        VoucherUsage::create([
            'user_id' => $this->consumerUser->id,
            'subdomain_id' => $this->subdomain->id,
            'business_info_id' => $otherBusiness->id,
            'classroom_info_id' => $otherClassroom->id,
            'course_info_id' => $otherCourse->id,
            'amount' => 3000,
            'used_at' => now()->subDay(),
            'is_cancelled' => false,
        ]);

        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/applications/export');

        $response->assertStatus(200);
        $body = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('Test Classroom', $body);
        $this->assertStringNotContainsString('Other Classroom', $body);
    }
}
