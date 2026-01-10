<?php

namespace SalesCommission\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Model;
use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionRule;
use SalesCommission\Services\CommissionCalculator;
use SalesCommission\Tests\TestCase;
use Mockery;

class CommissionCalculatorTest extends TestCase
{
    protected CommissionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CommissionCalculator();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createPlanWithRules(): CommissionPlan
    {
        $plan = CommissionPlan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'is_active' => true,
            'is_default' => true,
        ]);

        CommissionRule::create([
            'plan_id' => $plan->id,
            'name' => 'Base Commission',
            'type' => 'percentage',
            'value' => 10,
            'priority' => 1,
            'is_active' => true,
        ]);

        return $plan;
    }

    /**
     * @return Commissionable&\Mockery\MockInterface
     */
    protected function createMockCommissionable(float $amount): Commissionable
    {
        /** @var Commissionable&\Mockery\MockInterface $mock */
        $mock = Mockery::mock(Commissionable::class . ', ' . Model::class);
        $mock->shouldReceive('getCommissionableAmount')->andReturn($amount);
        $mock->shouldReceive('getCommissionAgent')->andReturn(null);
        $mock->shouldReceive('getCommissionDate')->andReturn(now());
        $mock->shouldReceive('getCommissionMeta')->andReturn([]);
        $mock->shouldReceive('getKey')->andReturn(1);
        $mock->shouldReceive('getMorphClass')->andReturn('TestModel');

        return $mock;
    }

    /**
     * @return CommissionAgent&\Mockery\MockInterface
     */
    protected function createMockAgent(): CommissionAgent
    {
        /** @var CommissionAgent&\Mockery\MockInterface $mock */
        $mock = Mockery::mock(CommissionAgent::class . ', ' . Model::class);
        $mock->shouldReceive('getAgentId')->andReturn(1);
        $mock->shouldReceive('getAgentName')->andReturn('Test Agent');
        $mock->shouldReceive('getCommissionPlanId')->andReturn(null);
        $mock->shouldReceive('getKey')->andReturn(1);
        $mock->shouldReceive('getMorphClass')->andReturn('TestAgent');
        $mock->shouldReceive('offsetExists')->andReturn(false);

        return $mock;
    }

    public function test_can_calculate_basic_commission(): void
    {
        $plan = $this->createPlanWithRules();
        $commissionable = $this->createMockCommissionable(1000);
        $agent = $this->createMockAgent();

        $result = $this->calculator
            ->forPlan($plan)
            ->calculate($commissionable, $agent);

        $this->assertInstanceOf(CommissionEarning::class, $result);
        $this->assertEquals(1000, $result->base_amount);
        $this->assertEquals(100, $result->commission_amount); // 10% of 1000
    }

    public function test_for_plan_accepts_plan_model(): void
    {
        $plan = $this->createPlanWithRules();

        $calculator = $this->calculator->forPlan($plan);

        $this->assertSame($this->calculator, $calculator);
    }

    public function test_for_plan_accepts_plan_id(): void
    {
        $plan = $this->createPlanWithRules();

        $calculator = $this->calculator->forPlan($plan->id);

        $this->assertSame($this->calculator, $calculator);
    }

    public function test_for_plan_accepts_plan_slug(): void
    {
        $plan = $this->createPlanWithRules();

        $calculator = $this->calculator->forPlan('test-plan');

        $this->assertSame($this->calculator, $calculator);
    }

    public function test_with_context_adds_context(): void
    {
        $calculator = $this->calculator->withContext(['key' => 'value']);

        $this->assertSame($this->calculator, $calculator);
    }

    public function test_calculate_batch_processes_multiple_items(): void
    {
        $plan = $this->createPlanWithRules();
        $commissionable1 = $this->createMockCommissionable(1000);
        $commissionable2 = $this->createMockCommissionable(2000);
        $agent = $this->createMockAgent();

        $results = $this->calculator
            ->forPlan($plan)
            ->calculateBatch([$commissionable1, $commissionable2], $agent);

        $this->assertCount(2, $results);
        $this->assertEquals(100, $results[0]->commission_amount); // 10% of 1000
        $this->assertEquals(200, $results[1]->commission_amount); // 10% of 2000
    }
}
