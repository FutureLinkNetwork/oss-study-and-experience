<?php

namespace Tests\Unit;

use App\Enums\CouponNotificationFrequency;
use PHPUnit\Framework\TestCase;

class CouponNotificationFrequencyTest extends TestCase
{
    /**
     * label が期待どおりの日本語を返すこと
     */
    public function test_labels_return_expected_strings(): void
    {
        $this->assertSame('都度', CouponNotificationFrequency::Immediate->label());
        $this->assertSame('一日1回（9時頃）', CouponNotificationFrequency::Daily->label());
        $this->assertSame('通知しない', CouponNotificationFrequency::None->label());
    }

    /**
     * all が3ケースを返すこと
     */
    public function test_all_returns_three_cases(): void
    {
        $all = CouponNotificationFrequency::all();
        $this->assertCount(3, $all);
        $this->assertContains(CouponNotificationFrequency::Immediate, $all);
        $this->assertContains(CouponNotificationFrequency::Daily, $all);
        $this->assertContains(CouponNotificationFrequency::None, $all);
    }

    /**
     * tryFrom が正しい値を解釈すること
     */
    public function test_try_from_accepts_valid_values(): void
    {
        $this->assertSame(CouponNotificationFrequency::Immediate, CouponNotificationFrequency::tryFrom('immediate'));
        $this->assertSame(CouponNotificationFrequency::Daily, CouponNotificationFrequency::tryFrom('daily'));
        $this->assertSame(CouponNotificationFrequency::None, CouponNotificationFrequency::tryFrom('none'));
        $this->assertNull(CouponNotificationFrequency::tryFrom('invalid'));
    }
}
