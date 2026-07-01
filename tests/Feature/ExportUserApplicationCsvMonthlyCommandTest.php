<?php

namespace Tests\Feature;

use App\Models\AdminDownload;
use App\Models\Subdomain;
use App\Models\UserApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportUserApplicationCsvMonthlyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_download_record_and_uploads_csv_to_s3(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);

        $this->artisan('app:export-user-application-csv-monthly')
            ->assertSuccessful();

        $this->assertDatabaseCount('admin_downloads', 1);
        $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('user_application', $record->download_type);
        $this->assertStringStartsWith("subdomain_{$subdomain->id}/user_application_exports/", $record->s3_key);
        $this->assertStringEndsWith('.csv', $record->s3_key);
        $this->assertStringContainsString('利用者申請CSV 全件', $record->summary);
        $this->assertTrue(Storage::disk('s3')->exists($record->s3_key));
    }

    public function test_multiple_subdomains_each_get_one_record(): void
    {
        Storage::fake('s3');

        Subdomain::factory()->create(['subdomain' => 'www']);
        Subdomain::factory()->create(['subdomain' => 'other']);

        $this->artisan('app:export-user-application-csv-monthly')
            ->assertSuccessful();

        $this->assertDatabaseCount('admin_downloads', 2);
    }

    public function test_csv_contains_application_data_with_created_at_as_application_date(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        UserApplication::factory()->create([
            'subdomain_id' => $subdomain->id,
            'certification_number' => '12345',
            'guardian_name' => '山田太郎',
            'guardian_name_kana' => 'ヤマダタロウ',
            'child_name' => '山田花子',
            'created_at' => '2026-02-15 10:30:00',
        ]);

        $this->artisan('app:export-user-application-csv-monthly')
            ->assertSuccessful();

        $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
        $this->assertNotNull($record);
        $rawContent = Storage::disk('s3')->get($record->s3_key);
        $content = mb_convert_encoding($rawContent, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('12345', $content);
        $this->assertStringContainsString('山田太郎', $content);
        $this->assertStringContainsString('山田花子', $content);
        $this->assertStringContainsString('2026-02-15 10:30:00', $content);
    }
}
