<?php

namespace Tests\Unit;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use App\Services\BeneficiaryCsvExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryCsvExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private BeneficiaryCsvExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BeneficiaryCsvExportService;
    }

    public function test_get_headers_returns_expected_columns(): void
    {
        $headers = $this->service->getHeaders();
        $this->assertIsArray($headers);
        $this->assertSame('No.', $headers[0]);
        $this->assertSame('年度', $headers[1]);
        $this->assertSame('抽出日', $headers[array_key_last($headers)]);
    }

    public function test_fiscal_year_from_application_date_april_is_same_year(): void
    {
        $date = Carbon::parse('2024-04-01');
        $this->assertSame('2024', $this->service->fiscalYearFromApplicationDate($date));
    }

    public function test_fiscal_year_from_application_date_march_is_previous_year(): void
    {
        $date = Carbon::parse('2025-03-31');
        $this->assertSame('2024', $this->service->fiscalYearFromApplicationDate($date));
    }

    public function test_fiscal_year_from_application_date_null_returns_empty_string(): void
    {
        $this->assertSame('', $this->service->fiscalYearFromApplicationDate(null));
    }

    public function test_stream_csv_to_writes_header_and_rows(): void
    {
        $subdomain = Subdomain::factory()->create();
        Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'certification_number' => 'EXP-CERT-001',
            'guardian_name' => '出力テスト保護者',
            'application_date' => '2024-06-15',
            'remarks' => 'CSV備考テキスト',
        ]);

        $query = Beneficiary::query()->where('subdomain_id', $subdomain->id);
        $stream = fopen('php://temp', 'r+');
        $this->service->streamCsvTo($stream, $query, Carbon::parse('2025-01-01 00:00:00'));
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        $this->assertStringContainsString('EXP-CERT-001', $content);
        $this->assertStringContainsString('2024', $content); // 年度
        $contentUtf8 = mb_convert_encoding($content, 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('CSV備考テキスト', $contentUtf8);
        $this->assertStringContainsString('2025-01-01 00:00:00', $contentUtf8); // 抽出日
    }
}
