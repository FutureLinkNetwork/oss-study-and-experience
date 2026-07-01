<?php

namespace Tests\Feature;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\AdminDownload;
use App\Models\Beneficiary;
use App\Models\Inquiry;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportInquiryCsvMonthlyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_download_record_with_download_type_inquiry_and_uploads_csv_to_s3(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);

        $this->artisan('app:export-inquiry-csv-monthly')
            ->assertSuccessful();

        $this->assertDatabaseCount('admin_downloads', 1);
        $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('inquiry', $record->download_type);
        $this->assertStringStartsWith("subdomain_{$subdomain->id}/inquiry_exports/", $record->s3_key);
        $this->assertStringEndsWith('.csv', $record->s3_key);
        $this->assertStringContainsString('問い合わせ（利用者・事業者）CSV 先月分', $record->summary);
        $this->assertTrue(Storage::disk('s3')->exists($record->s3_key));
    }

    public function test_multiple_subdomains_each_get_one_record(): void
    {
        Storage::fake('s3');

        Subdomain::factory()->create(['subdomain' => 'www']);
        Subdomain::factory()->create(['subdomain' => 'other']);

        $this->artisan('app:export-inquiry-csv-monthly')
            ->assertSuccessful();

        $this->assertDatabaseCount('admin_downloads', 2);
    }

    public function test_csv_contains_last_month_inquiry_data_with_user_type_and_status_label(): void
    {
        Storage::fake('s3');

        $fixedNow = Carbon::create(2026, 3, 15, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        try {
            $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
            $userRole = Role::factory()->create([
                'name' => 'subdomain_user',
                'level' => 10,
                'is_active' => true,
            ]);
            $user = User::factory()->create([
                'subdomain_id' => $subdomain->id,
                'role_id' => $userRole->id,
                'login_id' => 'inquiry_user_1',
                'name' => 'ユーザー名',
                'email' => 'inquiry-user@example.com',
                'is_active' => true,
            ]);
            $beneficiary = Beneficiary::factory()->create([
                'subdomain_id' => $subdomain->id,
                'user_id' => $user->id,
                'guardian_name' => '保護者太郎',
                'guardian_phone' => '072-111-2222',
                'guardian_email' => 'guardian@example.com',
            ]);
            $lastMonth = Carbon::create(2026, 2, 5, 14, 30, 0, 'Asia/Tokyo');
            $inquiry = Inquiry::factory()->create([
                'subdomain_id' => $subdomain->id,
                'user_id' => $user->id,
                'inquiry_type' => InquiryType::User,
                'content' => '問い合わせの本文です。',
                'status' => InquiryStatus::InProgress,
                'remarks' => '備考メモ',
                'created_user_id' => $user->id,
            ]);
            DB::table('inquiries')->where('id', $inquiry->id)->update([
                'created_at' => $lastMonth,
                'updated_at' => $lastMonth,
            ]);

            $this->artisan('app:export-inquiry-csv-monthly')
                ->assertSuccessful();

            $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
            $this->assertNotNull($record);
            $rawContent = Storage::disk('s3')->get($record->s3_key);
            $content = mb_convert_encoding($rawContent, 'UTF-8', 'SJIS-win');
            $this->assertStringContainsString('保護者太郎', $content);
            $this->assertStringContainsString('inquiry-user@example.com', $content);
            $this->assertStringContainsString('072-111-2222', $content);
            $this->assertStringContainsString('問い合わせの本文です。', $content);
            $this->assertStringContainsString('対応中', $content);
            $this->assertStringContainsString('備考メモ', $content);
            $this->assertStringContainsString('2026-02-05 14:30', $content);
            $this->assertStringContainsString('利用者', $content);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_zero_inquiries_outputs_csv_with_zero_row_and_registers_admin_download(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);

        $this->artisan('app:export-inquiry-csv-monthly')
            ->assertSuccessful();

        $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
        $this->assertNotNull($record);
        $rawContent = Storage::disk('s3')->get($record->s3_key);
        $content = mb_convert_encoding($rawContent, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('0件', $content);
    }

    public function test_inquiries_from_other_subdomain_are_excluded(): void
    {
        Storage::fake('s3');

        $subdomainA = Subdomain::factory()->create(['subdomain' => 'www']);
        $subdomainB = Subdomain::factory()->create(['subdomain' => 'other']);
        $userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);
        $userB = User::factory()->create([
            'subdomain_id' => $subdomainB->id,
            'role_id' => $userRole->id,
            'login_id' => 'other_user',
            'email' => 'other@example.com',
            'is_active' => true,
        ]);
        $lastMonth = Carbon::now('Asia/Tokyo')->subMonth()->startOfMonth()->addDays(1);
        Inquiry::factory()->create([
            'subdomain_id' => $subdomainB->id,
            'user_id' => $userB->id,
            'created_user_id' => $userB->id,
            'content' => '他サブドメインの問い合わせ',
            'inquiry_type' => InquiryType::User,
        ]);
        DB::table('inquiries')->where('subdomain_id', $subdomainB->id)->update([
            'created_at' => $lastMonth,
            'updated_at' => $lastMonth,
        ]);

        $this->artisan('app:export-inquiry-csv-monthly')
            ->assertSuccessful();

        $recordA = AdminDownload::query()->forSubdomain($subdomainA->id)->first();
        $this->assertNotNull($recordA);
        $rawContent = Storage::disk('s3')->get($recordA->s3_key);
        $content = mb_convert_encoding($rawContent, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('0件', $content);
        $this->assertStringNotContainsString('他サブドメインの問い合わせ', $content);
    }
}
