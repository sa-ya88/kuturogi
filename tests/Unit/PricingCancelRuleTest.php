<?php

namespace Tests\Unit;

use App\Models\PricingCancelRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PricingCancelRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PricingCancelRule::query()->create([
            'label' => '8日前まで',
            'days_before_from' => 365,
            'days_before_to' => 8,
            'charge_percent' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        PricingCancelRule::query()->create([
            'label' => '7日前〜2日前',
            'days_before_from' => 7,
            'days_before_to' => 2,
            'charge_percent' => 50,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        PricingCancelRule::query()->create([
            'label' => '前日',
            'days_before_from' => 1,
            'days_before_to' => 1,
            'charge_percent' => 80,
            'sort_order' => 3,
            'is_active' => true,
        ]);
        PricingCancelRule::query()->create([
            'label' => '当日',
            'days_before_from' => 0,
            'days_before_to' => 0,
            'charge_percent' => 100,
            'sort_order' => 4,
            'is_active' => true,
        ]);
    }

    public function test_free_cancellation_when_charge_percent_is_zero(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-16'));

        $this->assertTrue(PricingCancelRule::allowsFreeCancellation('2026-09-01'));
        $this->assertSame(0, PricingCancelRule::chargePercentForDaysBefore(16));
    }

    public function test_charged_cancellation_uses_matching_rule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-28'));

        $this->assertFalse(PricingCancelRule::allowsFreeCancellation('2026-09-01'));
        $this->assertSame(50, PricingCancelRule::chargePercentForDaysBefore(4));
    }

    public function test_past_checkin_cannot_be_cancelled_online(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02'));

        $this->assertFalse(PricingCancelRule::allowsFreeCancellation('2026-09-01'));
    }

    public function test_dates_outside_charged_windows_are_free(): void
    {
        PricingCancelRule::query()->delete();
        PricingCancelRule::query()->create([
            'label' => '3日前〜前日',
            'days_before_from' => 3,
            'days_before_to' => 1,
            'charge_percent' => 20,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        PricingCancelRule::query()->create([
            'label' => '当日',
            'days_before_from' => 0,
            'days_before_to' => 0,
            'charge_percent' => 80,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-16'));

        $this->assertTrue(PricingCancelRule::allowsFreeCancellation('2026-09-01'));
        $this->assertSame(0, PricingCancelRule::chargePercentForDaysBefore(16));
        $this->assertFalse(PricingCancelRule::allowsFreeCancellation('2026-08-18'));
        $this->assertSame(20, PricingCancelRule::chargePercentForDaysBefore(2));
    }
}
