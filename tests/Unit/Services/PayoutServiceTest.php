<?php

namespace SalesCommission\Tests\Unit\Services;

use SalesCommission\Models\Payout;
use SalesCommission\Services\PayoutService;
use SalesCommission\Tests\TestCase;

class PayoutServiceTest extends TestCase
{
    protected PayoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PayoutService();
    }

    public function test_generate_for_period_returns_null_when_no_earnings(): void
    {
        $period = '2026-01';

        $payout = $this->service->generateForPeriod($period);

        // Should return null when no payable earnings exist
        $this->assertNull($payout);
    }

    public function test_generate_for_current_period_returns_null_when_no_earnings(): void
    {
        $payout = $this->service->generateForCurrentPeriod();

        // Should return null when no payable earnings exist
        $this->assertNull($payout);
    }

    public function test_get_current_period_returns_monthly_format_by_default(): void
    {
        $period = $this->service->getCurrentPeriod();

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $period);
    }

    public function test_get_current_period_returns_weekly_format_when_configured(): void
    {
        config(['sales-commission.payout.schedule' => 'weekly']);

        $period = $this->service->getCurrentPeriod();

        $this->assertMatchesRegularExpression('/^\d{4}-W\d{2}$/', $period);
    }

    public function test_get_payout_stats_returns_correct_structure(): void
    {
        $stats = $this->service->getPayoutStats();

        $this->assertArrayHasKey('total_paid', $stats);
        $this->assertArrayHasKey('total_pending', $stats);
        $this->assertArrayHasKey('payout_count', $stats);
    }

    public function test_get_payout_stats_filters_by_period(): void
    {
        Payout::create([
            'period' => '2026-01',
            'status' => Payout::STATUS_PAID,
            'total_amount' => 1000,
        ]);

        Payout::create([
            'period' => '2026-02',
            'status' => Payout::STATUS_PAID,
            'total_amount' => 2000,
        ]);

        $stats = $this->service->getPayoutStats('2026-01');

        $this->assertEquals(1000, $stats['total_paid']);
        $this->assertEquals(1, $stats['payout_count']);
    }

    public function test_process_approved_payouts_marks_as_paid(): void
    {
        $payout = Payout::create([
            'period' => '2026-01',
            'status' => Payout::STATUS_APPROVED,
            'total_amount' => 1000,
        ]);

        $processed = $this->service->processApprovedPayouts();

        $this->assertCount(1, $processed);
        $this->assertEquals(Payout::STATUS_PAID, $payout->fresh()->status);
    }

    public function test_process_approved_payouts_ignores_non_approved(): void
    {
        Payout::create([
            'period' => '2026-01',
            'status' => Payout::STATUS_DRAFT,
            'total_amount' => 1000,
        ]);

        Payout::create([
            'period' => '2026-02',
            'status' => Payout::STATUS_PENDING_APPROVAL,
            'total_amount' => 2000,
        ]);

        $processed = $this->service->processApprovedPayouts();

        $this->assertCount(0, $processed);
    }
}
