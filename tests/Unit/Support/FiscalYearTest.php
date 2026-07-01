<?php

namespace Tests\Unit\Support;

use App\Support\FiscalYear;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FiscalYearTest extends TestCase
{
    public function test_current_start_year_for_april_or_later_uses_calendar_year(): void
    {
        $this->assertSame(2026, FiscalYear::currentStartYear(Carbon::parse('2026-04-01')));
        $this->assertSame(2026, FiscalYear::currentStartYear(Carbon::parse('2026-12-31')));
    }

    public function test_current_start_year_for_january_through_march_uses_previous_year(): void
    {
        $this->assertSame(2025, FiscalYear::currentStartYear(Carbon::parse('2026-01-15')));
        $this->assertSame(2025, FiscalYear::currentStartYear(Carbon::parse('2026-03-31')));
    }

    public function test_start_and_end_dates_for_fiscal_year_starting_2025(): void
    {
        $start = FiscalYear::startDateFor(2025);
        $end = FiscalYear::endDateFor(2025);

        $this->assertSame('2025-04-01', $start->toDateString());
        $this->assertSame('2026-03-31', $end->toDateString());
    }
}
