<?php

namespace SalesCommission\Tests\Unit\Rules;

use SalesCommission\Rules\PercentageRule;
use SalesCommission\Rules\FlatAmountRule;
use SalesCommission\Rules\TieredPercentageRule;
use SalesCommission\Tests\TestCase;
use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;
use Mockery;

class RulesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createMockCommissionable(float $amount): Commissionable
    {
        $mock = Mockery::mock(Commissionable::class);
        $mock->shouldReceive('getCommissionableAmount')->andReturn($amount);
        $mock->shouldReceive('getCommissionAgent')->andReturn(null);
        $mock->shouldReceive('getCommissionDate')->andReturn(now());
        $mock->shouldReceive('getCommissionMeta')->andReturn([]);

        return $mock;
    }

    protected function createMockAgent(): CommissionAgent
    {
        $mock = Mockery::mock(CommissionAgent::class);
        $mock->shouldReceive('getAgentId')->andReturn(1);
        $mock->shouldReceive('getAgentName')->andReturn('Test Agent');
        $mock->shouldReceive('getCommissionPlanId')->andReturn(null);

        return $mock;
    }

    public function test_percentage_rule_calculates_correctly(): void
    {
        $rule = new PercentageRule(10);
        $commissionable = $this->createMockCommissionable(1000);
        $agent = $this->createMockAgent();

        $result = $rule->calculate($commissionable, $agent);

        $this->assertEquals(100.00, $result);
    }

    public function test_flat_amount_rule_returns_fixed_amount(): void
    {
        $rule = new FlatAmountRule(50);
        $commissionable = $this->createMockCommissionable(1000);
        $agent = $this->createMockAgent();

        $result = $rule->calculate($commissionable, $agent);

        $this->assertEquals(50.00, $result);
    }

    public function test_tiered_rule_finds_correct_tier(): void
    {
        $rule = new TieredPercentageRule([
            ['min' => 0, 'max' => 1000, 'rate' => 5],
            ['min' => 1001, 'max' => 5000, 'rate' => 10],
            ['min' => 5001, 'max' => null, 'rate' => 15],
        ]);

        $agent = $this->createMockAgent();

        // Test low tier
        $lowCommissionable = $this->createMockCommissionable(500);
        $this->assertEquals(25.00, $rule->calculate($lowCommissionable, $agent));

        // Test mid tier
        $midCommissionable = $this->createMockCommissionable(2000);
        $this->assertEquals(200.00, $rule->calculate($midCommissionable, $agent));

        // Test high tier
        $highCommissionable = $this->createMockCommissionable(10000);
        $this->assertEquals(1500.00, $rule->calculate($highCommissionable, $agent));
    }
}
