<?php

namespace Tests\Feature;

use App\Models\AdminDownload;
use App\Models\Contact;
use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportContactCsvMonthlyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_download_record_with_download_type_contact_and_uploads_csv_to_s3(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);

        $this->artisan('app:export-contact-csv-monthly')
            ->assertSuccessful();

        $this->assertDatabaseCount('admin_downloads', 1);
        $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('contact', $record->download_type);
        $this->assertStringStartsWith("subdomain_{$subdomain->id}/contact_exports/", $record->s3_key);
        $this->assertStringEndsWith('.csv', $record->s3_key);
        $this->assertStringContainsString('お問い合わせCSV 先月分', $record->summary);
        $this->assertTrue(Storage::disk('s3')->exists($record->s3_key));
    }

    public function test_multiple_subdomains_each_get_one_record(): void
    {
        Storage::fake('s3');

        Subdomain::factory()->create(['subdomain' => 'www']);
        Subdomain::factory()->create(['subdomain' => 'other']);

        $this->artisan('app:export-contact-csv-monthly')
            ->assertSuccessful();

        $this->assertDatabaseCount('admin_downloads', 2);
    }

    public function test_csv_contains_last_month_contact_data_and_status_label(): void
    {
        Storage::fake('s3');

        // 実行日を固定して「先月」を2026年2月に確定
        $fixedNow = Carbon::create(2026, 3, 15, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        try {
            $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
            $lastMonth = Carbon::create(2026, 2, 5, 14, 30, 0, 'Asia/Tokyo');

            $contact = Contact::query()->create([
                'subdomain_id' => $subdomain->id,
                'name' => '問い合わせ太郎',
                'email' => 'contact@example.com',
                'phone' => '072-111-2222',
                'content' => 'お問い合わせの本文です。',
                'is_confirmed' => 1,
                'remarks' => '備考メモ',
            ]);
            DB::table('contacts')->where('id', $contact->id)->update([
                'created_at' => $lastMonth,
                'updated_at' => $lastMonth,
            ]);

            $this->artisan('app:export-contact-csv-monthly')
                ->assertSuccessful();

            $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
            $this->assertNotNull($record);
            $rawContent = Storage::disk('s3')->get($record->s3_key);
            $content = mb_convert_encoding($rawContent, 'UTF-8', 'SJIS-win');
            $this->assertStringContainsString('問い合わせ太郎', $content);
            $this->assertStringContainsString('contact@example.com', $content);
            $this->assertStringContainsString('072-111-2222', $content);
            $this->assertStringContainsString('お問い合わせの本文です。', $content);
            $this->assertStringContainsString('確認中', $content);
            $this->assertStringContainsString('備考メモ', $content);
            $this->assertStringContainsString('2026-02-05 14:30', $content);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_zero_contacts_outputs_csv_with_zero_row_and_registers_admin_download(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        // No contacts for this subdomain

        $this->artisan('app:export-contact-csv-monthly')
            ->assertSuccessful();

        $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
        $this->assertNotNull($record);
        $rawContent = Storage::disk('s3')->get($record->s3_key);
        $content = mb_convert_encoding($rawContent, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('0件', $content);
    }

    public function test_contacts_with_null_subdomain_id_are_ignored(): void
    {
        Storage::fake('s3');

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $lastMonth = Carbon::now('Asia/Tokyo')->subMonth()->startOfMonth()->addDays(1);

        Contact::query()->create([
            'subdomain_id' => null,
            'name' => 'NULLサブドメイン',
            'email' => 'null@example.com',
            'phone' => '000-000-0000',
            'content' => '含まれない',
            'created_at' => $lastMonth,
            'updated_at' => $lastMonth,
        ]);

        $this->artisan('app:export-contact-csv-monthly')
            ->assertSuccessful();

        $record = AdminDownload::query()->forSubdomain($subdomain->id)->first();
        $this->assertNotNull($record);
        $rawContent = Storage::disk('s3')->get($record->s3_key);
        $content = mb_convert_encoding($rawContent, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('0件', $content);
        $this->assertStringNotContainsString('NULLサブドメイン', $content);
    }
}
