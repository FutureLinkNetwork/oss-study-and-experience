<?php

namespace Tests\Unit;

use App\Services\BusinessCsvExportService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BusinessCsvExportServiceApplicantTypeTest extends TestCase
{
    public function test_applicant_type_labels_include_government_agency(): void
    {
        $reflection = new ReflectionClass(BusinessCsvExportService::class);
        /** @var array<string, string> $labels */
        $labels = $reflection->getConstant('APPLICANT_TYPE_LABELS');

        $this->assertArrayHasKey('government_agency', $labels);
        $this->assertSame('行政機関', $labels['government_agency']);
    }
}
