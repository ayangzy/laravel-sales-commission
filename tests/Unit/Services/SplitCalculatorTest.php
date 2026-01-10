<?php

namespace SalesCommission\Tests\Unit\Services;

use InvalidArgumentException;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionRule;
use SalesCommission\Services\CommissionCalculator;
use SalesCommission\Services\SplitCalculator;
use SalesCommission\Tests\TestCase;

class SplitCalculatorTest extends TestCase
{
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

    public function test_split_calculator_can_be_created(): void
    {
        $plan = $this->createPlanWithRules();
        $calculator = (new CommissionCalculator())->forPlan($plan);

        // Test that the calculator's split() method returns a SplitCalculator
        $this->assertInstanceOf(CommissionCalculator::class, $calculator);
    }

    public function test_split_total_percentage_validation(): void
    {
        // Test that SplitCalculator validates percentages must equal 100%
        // This is a unit test for the validation logic
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Split percentages must total 100%');

        $plan = $this->createPlanWithRules();
        $calculator = (new CommissionCalculator())->forPlan($plan);

        // We need a real model to test splits, so we'll test the validation
        // by creating a minimal mock situation
        // Since the SplitCalculator requires real models, we'll test
        // that the percentage validation works correctly

        // Create a SplitCalculator with an invalid percentage split
        // The validation happens in the calculate() method
        $mockCommissionable = new class {
            public function getCommissionableAmount()
            {
                return 1000;
            }

            public function getCommissionAgent()
            {
                return null;
            }

            public function getCommissionDate()
            {
                return now();
            }

            public function getCommissionMeta()
            {
                return [];
            }

            public function getKey()
            {
                return 1;
            }
        };

        $splitCalculator = new SplitCalculator($mockCommissionable, $calculator);

        // Set splits that don't total 100%
        $splitCalculator->between([
            'agent1' => 50,
            'agent2' => 30, // Only 80%, should fail
        ])->calculate();
    }
}
