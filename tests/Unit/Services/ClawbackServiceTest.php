<?php

namespace SalesCommission\Tests\Unit\Services;

use SalesCommission\Models\CommissionClawback;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Services\ClawbackService;
use SalesCommission\Tests\TestCase;

class ClawbackServiceTest extends TestCase
{
    protected ClawbackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClawbackService();
    }

    protected function createEarning(float $amount = 100, string $status = CommissionEarning::STATUS_PENDING): CommissionEarning
    {
        return CommissionEarning::create([
            'agent_type' => 'App\Models\User',
            'agent_id' => 1,
            'commissionable_type' => 'App\Models\Order',
            'commissionable_id' => 1,
            'base_amount' => $amount * 10,
            'commission_amount' => $amount,
            'rate' => 10,
            'rate_type' => 'percentage',
            'status' => $status,
            'period' => '2026-01',
            'earned_at' => now(),
        ]);
    }

    public function test_clawback_creates_clawback_record(): void
    {
        $earning = $this->createEarning(100);

        $clawback = $this->service->clawback(
            $earning,
            CommissionClawback::REASON_REFUND,
            100,
            'Test clawback'
        );

        $this->assertInstanceOf(CommissionClawback::class, $clawback);
        $this->assertEquals(100, $clawback->amount);
        $this->assertEquals(CommissionClawback::REASON_REFUND, $clawback->reason);
        $this->assertEquals('Test clawback', $clawback->notes);
    }

    public function test_clawback_defaults_to_full_amount(): void
    {
        $earning = $this->createEarning(150);

        $clawback = $this->service->clawback(
            $earning,
            CommissionClawback::REASON_CANCELLATION
        );

        $this->assertEquals(150, $clawback->amount);
    }

    public function test_clawback_limits_to_remaining_amount(): void
    {
        $earning = $this->createEarning(100);

        // First clawback of 60
        $this->service->clawback($earning, CommissionClawback::REASON_REFUND, 60);

        // Second clawback should be limited to 40 (remaining)
        $clawback = $this->service->clawback(
            $earning,
            CommissionClawback::REASON_REFUND,
            100 // Requesting more than available
        );

        $this->assertEquals(40, $clawback->amount);
    }

    public function test_clawback_returns_null_when_nothing_to_clawback(): void
    {
        $earning = $this->createEarning(100);

        // Clawback full amount
        $this->service->clawback($earning, CommissionClawback::REASON_REFUND, 100);

        // Try to clawback again
        $clawback = $this->service->clawback($earning, CommissionClawback::REASON_REFUND);

        $this->assertNull($clawback);
    }

    public function test_clawback_returns_null_when_disabled(): void
    {
        config(['sales-commission.clawback.enabled' => false]);

        $earning = $this->createEarning(100);

        $clawback = $this->service->clawback($earning, CommissionClawback::REASON_REFUND);

        $this->assertNull($clawback);
    }

    public function test_partial_clawback_calculates_proportional_amount(): void
    {
        $earning = $this->createEarning(100); // base_amount = 1000, commission = 100

        $clawback = $this->service->partialClawback(
            $earning,
            500 // Refund half of base amount
        );

        $this->assertEquals(50, $clawback->amount); // 50% of commission
    }

    public function test_partial_clawback_returns_null_for_zero_base(): void
    {
        $earning = CommissionEarning::create([
            'agent_type' => 'App\Models\User',
            'agent_id' => 1,
            'commissionable_type' => 'App\Models\Order',
            'commissionable_id' => 1,
            'base_amount' => 0,
            'commission_amount' => 100,
            'rate' => 10,
            'rate_type' => 'flat',
            'status' => CommissionEarning::STATUS_PENDING,
            'period' => '2026-01',
            'earned_at' => now(),
        ]);

        $clawback = $this->service->partialClawback($earning, 100);

        $this->assertNull($clawback);
    }

    public function test_clawback_updates_earning_status_when_fully_clawed_back(): void
    {
        $earning = $this->createEarning(100);

        $this->service->clawback($earning, CommissionClawback::REASON_CANCELLATION, 100);

        $earning->refresh();
        $this->assertEquals(CommissionEarning::STATUS_CLAWED_BACK, $earning->status);
    }
}
