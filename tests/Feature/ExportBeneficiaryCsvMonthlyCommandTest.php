<?php

namespace Tests\Feature;

use App\Models\AdminDownload;
use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportBeneficiaryCsvMonthlyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_download_record_and_uploads_csv_to_s3(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);

        $this->artisan('app:export-beneficiary-csv-monthly')
            ->assertSuccessful();

        $this->assertDatabaseCount('admin_downloads', 1);
        $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('beneficiary', $record->download_type);
        $this->assertStringStartsWith("subdomain_{$subdomain->id}/beneficiary_exports/", $record->s3_key);
        $this->assertStringEndsWith('.csv', $record->s3_key);
        $this->assertStringContainsString('利用者CSV 全件', $record->summary);
        $this->assertTrue(Storage::disk('s3')->exists($record->s3_key));
    }

    public function test_multiple_subdomains_each_get_one_record(): void
    {
        Storage::fake('s3');

        Subdomain::factory()->create(['subdomain' => 'www']);
        Subdomain::factory()->create(['subdomain' => 'other']);

        $this->artisan('app:export-beneficiary-csv-monthly')
            ->assertSuccessful();

        $this->assertDatabaseCount('admin_downloads', 2);
    }
}
