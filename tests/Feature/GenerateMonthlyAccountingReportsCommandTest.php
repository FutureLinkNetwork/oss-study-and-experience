<?php

namespace Tests\Feature;

use App\Models\AccountingReportDownload;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateMonthlyAccountingReportsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 前月を対象に全サブドメイン分のCSV・PDFが個別にS3にアップロードされ、管理テーブルにレコードが作成されること
     */
    public function test_generates_csv_and_pdf_per_subdomain_and_uploads_to_s3(): void
    {
        Storage::fake('s3');

        $subdomain1 = Subdomain::factory()->create(['subdomain' => 'www']);
        $subdomain2 = Subdomain::factory()->create(['subdomain' => 'other']);
        $this->seedVoucherUsageForSubdomain($subdomain1);
        $this->seedVoucherUsageForSubdomain($subdomain2);

        $targetYearMonth = Carbon::today()->subMonth()->format('Y-m');

        $this->artisan('app:generate-monthly-accounting-reports')
            ->assertSuccessful();

        $this->assertDatabaseCount('accounting_report_downloads', 2);

        $record1 = AccountingReportDownload::query()
            ->where('subdomain_id', $subdomain1->id)
            ->whereDate('target_month', $targetYearMonth.'-01')
            ->first();
        $this->assertNotNull($record1);
        $this->assertSame("subdomain_{$subdomain1->id}/accounting_reports/{$targetYearMonth}.csv", $record1->csv_s3_key);
        $this->assertSame("subdomain_{$subdomain1->id}/accounting_reports/{$targetYearMonth}.pdf", $record1->pdf_s3_key);
        $this->assertNull($record1->csv_downloaded_at);
        $this->assertNull($record1->pdf_downloaded_at);

        $this->assertTrue(Storage::disk('s3')->exists($record1->csv_s3_key));
        $this->assertTrue(Storage::disk('s3')->exists($record1->pdf_s3_key));

        $record2 = AccountingReportDownload::query()
            ->where('subdomain_id', $subdomain2->id)
            ->whereDate('target_month', $targetYearMonth.'-01')
            ->first();
        $this->assertNotNull($record2);
        $this->assertTrue(Storage::disk('s3')->exists($record2->csv_s3_key));
        $this->assertTrue(Storage::disk('s3')->exists($record2->pdf_s3_key));
    }

    /**
     * 同一サブドメイン・同一対象月で再実行すると upsert で csv_s3_key / pdf_s3_key が更新されること
     */
    public function test_upserts_when_run_again_for_same_month(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create();
        $this->seedVoucherUsageForSubdomain($subdomain);
        $targetMonth = Carbon::today()->subMonth()->format('Y-m').'-01';

        $this->artisan('app:generate-monthly-accounting-reports')->assertSuccessful();
        $first = AccountingReportDownload::query()->forSubdomain($subdomain->id)->whereDate('target_month', $targetMonth)->first();
        $this->assertNotNull($first);

        $this->artisan('app:generate-monthly-accounting-reports')->assertSuccessful();
        $this->assertDatabaseCount('accounting_report_downloads', 1);
        $second = AccountingReportDownload::query()->forSubdomain($subdomain->id)->whereDate('target_month', $targetMonth)->first();
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
    }

    /**
     * キャンセル済み（is_cancelled=true）の VoucherUsage は会計用 CSV・PDF の対象外であること
     */
    public function test_excludes_cancelled_voucher_usages_from_csv_and_pdf(): void
    {
        Storage::fake('s3');

        $lastMonth = Carbon::today()->subMonth();
        $usedAtCancelled = $lastMonth->copy()->day(10)->startOfDay();
        $usedAtActive = $lastMonth->copy()->day(11)->startOfDay();

        $subdomain = Subdomain::factory()->create();

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

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'acct_biz_'.uniqid(),
            'email' => 'acct_biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'acct_usr_'.uniqid(),
            'email' => 'acct_usr_'.uniqid().'@example.com',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Accounting Test Biz',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'acct-b@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Accounting Classroom',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $course = CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'Accounting Course',
            'price' => 5000,
            'is_active' => 1,
        ]);

        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 7777,
            'used_at' => $usedAtCancelled,
            'is_cancelled' => true,
        ]);
        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 5000,
            'used_at' => $usedAtActive,
            'is_cancelled' => false,
        ]);

        $this->artisan('app:generate-monthly-accounting-reports')->assertSuccessful();

        $targetYearMonth = $lastMonth->format('Y-m');
        $record = AccountingReportDownload::query()
            ->where('subdomain_id', $subdomain->id)
            ->whereDate('target_month', $targetYearMonth.'-01')
            ->first();
        $this->assertNotNull($record);

        $csvRaw = Storage::disk('s3')->get($record->csv_s3_key);
        $this->assertIsString($csvRaw);
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csvRaw) ?? $csvRaw;
        $this->assertStringNotContainsString('7777', $csv);
        $this->assertStringNotContainsString('キャンセル', $csv);
        $this->assertStringContainsString('5000', $csv);
        $this->assertStringContainsString('利用済', $csv);

        $pdf = Storage::disk('s3')->get($record->pdf_s3_key);
        $this->assertIsString($pdf);
        $this->assertGreaterThan(2000, strlen($pdf));
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringNotContainsString('7,777', $pdf);
        $this->assertDoesNotMatchRegularExpression('/12[,.]777/', $pdf);
    }

    public function test_generates_separate_s3_keys_for_target_and_non_target(): void
    {
        Storage::fake('s3');

        $lastMonth = Carbon::today()->subMonth();
        $subdomain = Subdomain::factory()->create(['subdomain' => 'split-test']);

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

        $targetUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'split_target_biz_'.uniqid(),
            'email' => 'split_target_biz_'.uniqid().'@example.com',
        ]);
        $nonTargetUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'split_non_target_biz_'.uniqid(),
            'email' => 'split_non_target_biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'split_consumer_'.uniqid(),
            'email' => 'split_consumer_'.uniqid().'@example.com',
        ]);

        $targetBusiness = BusinessInfo::create([
            'user_id' => $targetUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Target Biz',
            'business_name_kana' => 'ターゲット',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'split-target@example.com',
            'is_public_funds_transfer_target' => true,
            'apply' => 1,
            'is_active' => 1,
        ]);
        $nonTargetBusiness = BusinessInfo::create([
            'user_id' => $nonTargetUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Non Target Biz',
            'business_name_kana' => 'ヒターゲット',
            'representative_name' => 'Rep2',
            'representative_name_kana' => 'ダイヒョウ2',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '2-2',
            'phone' => '0300000001',
            'email' => 'split-non-target@example.com',
            'is_public_funds_transfer_target' => false,
            'apply' => 1,
            'is_active' => 1,
        ]);

        $targetClassroom = ClassroomInfo::create([
            'business_info_id' => $targetBusiness->id,
            'classroom_name' => 'Target Room',
            'classroom_name_kana' => 'ターゲット',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $nonTargetClassroom = ClassroomInfo::create([
            'business_info_id' => $nonTargetBusiness->id,
            'classroom_name' => 'Non Target Room',
            'classroom_name_kana' => 'ヒターゲット',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $targetCourse = CourseInfo::create([
            'business_info_id' => $targetBusiness->id,
            'classroom_info_id' => $targetClassroom->id,
            'course_name' => 'Target Course',
            'price' => 1000,
            'is_active' => 1,
        ]);
        $nonTargetCourse = CourseInfo::create([
            'business_info_id' => $nonTargetBusiness->id,
            'classroom_info_id' => $nonTargetClassroom->id,
            'course_name' => 'Non Target Course',
            'price' => 2000,
            'is_active' => 1,
        ]);

        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $targetBusiness->id,
            'classroom_info_id' => $targetClassroom->id,
            'course_info_id' => $targetCourse->id,
            'amount' => 1000,
            'used_at' => $lastMonth->copy()->day(5)->startOfDay(),
            'is_cancelled' => false,
        ]);
        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $nonTargetBusiness->id,
            'classroom_info_id' => $nonTargetClassroom->id,
            'course_info_id' => $nonTargetCourse->id,
            'amount' => 2000,
            'used_at' => $lastMonth->copy()->day(6)->startOfDay(),
            'is_cancelled' => false,
        ]);

        $targetYearMonth = $lastMonth->format('Y-m');

        $this->artisan('app:generate-monthly-accounting-reports')->assertSuccessful();

        $record = AccountingReportDownload::query()
            ->where('subdomain_id', $subdomain->id)
            ->whereDate('target_month', $targetYearMonth.'-01')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame("subdomain_{$subdomain->id}/accounting_reports/{$targetYearMonth}.csv", $record->csv_s3_key);
        $this->assertSame("subdomain_{$subdomain->id}/accounting_reports/{$targetYearMonth}.pdf", $record->pdf_s3_key);
        $this->assertSame("subdomain_{$subdomain->id}/accounting_reports/{$targetYearMonth}_non_target.csv", $record->csv_s3_key_non_target);
        $this->assertSame("subdomain_{$subdomain->id}/accounting_reports/{$targetYearMonth}_non_target.pdf", $record->pdf_s3_key_non_target);

        $targetCsv = Storage::disk('s3')->get($record->csv_s3_key);
        $nonTargetCsv = Storage::disk('s3')->get($record->csv_s3_key_non_target);
        $this->assertStringContainsString('1000', $targetCsv);
        $this->assertStringNotContainsString('2000', $targetCsv);
        $this->assertStringContainsString('2000', $nonTargetCsv);
        $this->assertStringNotContainsString('1000', $nonTargetCsv);
    }

    private function seedVoucherUsageForSubdomain(Subdomain $subdomain): void
    {
        $lastMonth = Carbon::today()->subMonth();

        $businessRole = Role::query()->firstOrCreate(
            ['name' => 'subdomain_business'],
            ['display_name' => '事業者', 'is_global' => false, 'level' => 20, 'is_active' => true]
        );
        $userRole = Role::query()->firstOrCreate(
            ['name' => 'subdomain_user'],
            ['display_name' => '利用者', 'is_global' => false, 'level' => 10, 'is_active' => true]
        );

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'seed_biz_'.uniqid(),
            'email' => 'seed_biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'seed_user_'.uniqid(),
            'email' => 'seed_user_'.uniqid().'@example.com',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Seed Business',
            'business_name_kana' => 'シード',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'seed-'.$subdomain->id.'@example.com',
            'is_public_funds_transfer_target' => true,
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Seed Classroom',
            'classroom_name_kana' => 'シード',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $course = CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'Seed Course',
            'price' => 1000,
            'is_active' => 1,
        ]);

        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 1000,
            'used_at' => $lastMonth->copy()->day(10)->startOfDay(),
            'is_cancelled' => false,
        ]);
    }
}
