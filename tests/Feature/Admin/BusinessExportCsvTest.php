<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseInfo;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessExportCsvTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $adminUser;

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
            'login_id' => 'export_csv_admin',
            'is_active' => true,
        ]);
    }

    public function test_export_csv_returns_zip_with_three_csv_files(): void
    {
        BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'individual',
            'antisocial_forces_pledged' => true,
            'privacy_policy_agreed' => true,
            'business_name' => 'CSV出力テスト事業者',
            'business_name_kana' => 'シーエスブイシュツリョクテスト',
            'representative_name' => '代表者',
            'representative_name_kana' => 'ダイヒョウシャ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'export-test@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.export-csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.zip', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $tmpFile = tempnam(sys_get_temp_dir(), 'business_export_test_');
        file_put_contents($tmpFile, $content);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($tmpFile) === true);
        $this->assertSame(3, $zip->numFiles);

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $this->assertContains('事業者情報.csv', $names);
        $this->assertContains('教室情報.csv', $names);
        $this->assertContains('コース情報.csv', $names);

        $zip->close();
        @unlink($tmpFile);
    }

    public function test_export_csv_respects_keyword_filter(): void
    {
        $match = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'ヒットする事業者',
            'business_name_kana' => 'ヒットスルジギョウシャ',
            'representative_name' => '代表',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-111-1111',
            'email' => 'hit@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '未着手',
            'qr_only' => false,
        ]);

        $other = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'ヒットしない事業者',
            'business_name_kana' => 'ヒットシナイジギョウシャ',
            'representative_name' => '代表',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '664-0002',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野2-2-2',
            'phone' => '072-222-2222',
            'email' => 'other@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '未着手',
            'qr_only' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.export-csv', ['keyword' => 'ヒットする']));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $tmpFile = tempnam(sys_get_temp_dir(), 'business_export_filter_');
        file_put_contents($tmpFile, $content);
        $zip = new \ZipArchive;
        $zip->open($tmpFile);
        $businessCsv = $zip->getFromName('事業者情報.csv');
        $zip->close();
        @unlink($tmpFile);

        $this->assertStringContainsString('ヒットする事業者', mb_convert_encoding($businessCsv, 'UTF-8', 'SJIS-win'));
        $this->assertStringNotContainsString('ヒットしない事業者', mb_convert_encoding($businessCsv, 'UTF-8', 'SJIS-win'));
    }

    public function test_export_csv_includes_classroom_and_course_data(): void
    {
        $parentCategory = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親カテゴリ',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $category = CourseCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parentCategory->id,
            'name' => '学習塾',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $business = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => '教室付き事業者',
            'business_name_kana' => 'キョウシツツキジギョウシャ',
            'representative_name' => '代表',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'classroom@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'テスト教室',
            'classroom_name_kana' => 'テストキョウシツ',
            'classroom_postal_code' => '664-0003',
            'classroom_prefecture' => '兵庫県',
            'classroom_city' => '伊丹市',
            'classroom_address1' => '教室町1-1',
            'use_map' => true,
            'service_type' => '教室型',
            'lesson_category' => $category->id,
            'is_active' => 1,
        ]);

        CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'テストコース',
            'price' => 5000,
            'tax_type' => '税込',
            'course_description' => '説明文',
            'grades' => ['小学1年', '小学2年'],
            'is_active' => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.export-csv'));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $tmpFile = tempnam(sys_get_temp_dir(), 'business_export_cc_');
        file_put_contents($tmpFile, $content);
        $zip = new \ZipArchive;
        $zip->open($tmpFile);

        $classroomCsv = $zip->getFromName('教室情報.csv');
        $this->assertNotEmpty($classroomCsv);
        $classroomUtf8 = mb_convert_encoding($classroomCsv, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('テスト教室', $classroomUtf8);
        $this->assertStringContainsString('学習塾', $classroomUtf8);

        $courseCsv = $zip->getFromName('コース情報.csv');
        $this->assertNotEmpty($courseCsv);
        $courseUtf8 = mb_convert_encoding($courseCsv, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('テストコース', $courseUtf8);
        $this->assertStringContainsString('5000', $courseUtf8);

        $zip->close();
        @unlink($tmpFile);
    }

    public function test_export_csv_shows_government_agency_applicant_type_label(): void
    {
        BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'government_agency',
            'antisocial_forces_pledged' => true,
            'privacy_policy_agreed' => true,
            'business_name' => '行政機関テスト事業者',
            'business_name_kana' => 'ギョウセイキカンテストジギョウシャ',
            'representative_name' => '代表者',
            'representative_name_kana' => 'ダイヒョウシャ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'gov-agency-export@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.export-csv'));

        $response->assertStatus(200);

        $tmpFile = tempnam(sys_get_temp_dir(), 'business_export_gov_');
        file_put_contents($tmpFile, $response->streamedContent());
        $zip = new \ZipArchive;
        $zip->open($tmpFile);
        $businessCsv = $zip->getFromName('事業者情報.csv');
        $zip->close();
        @unlink($tmpFile);

        $businessUtf8 = mb_convert_encoding($businessCsv, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('行政機関', $businessUtf8);
        $this->assertStringContainsString('行政機関テスト事業者', $businessUtf8);
    }
}
