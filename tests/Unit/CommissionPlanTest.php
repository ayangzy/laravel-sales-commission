<?php

namespace SalesCommission\Tests\Unit;

use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionTier;
use SalesCommission\Tests\TestCase;

class CommissionPlanTest extends TestCase
{
    public function test_can_create_commission_plan(): void
    {
        $plan = CommissionPlan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('commission_plans', [
            'name' => 'Test Plan',
            'slug' => 'test-plan',
        ]);
    }

    public function test_can_add_tiers_to_plan(): void
    {
        $plan = CommissionPlan::create([
            'name' => 'Tiered Plan',
            'slug' => 'tiered-plan',
            'is_active' => true,
        ]);

        $plan->tiers()->createMany([
            ['name' => 'Bronze', 'min_threshold' => 0, 'max_threshold' => 10000, 'rate' => 5],
            ['name' => 'Silver', 'min_threshold' => 10001, 'max_threshold' => 50000, 'rate' => 7.5],
            ['name' => 'Gold', 'min_threshold' => 50001, 'max_threshold' => null, 'rate' => 10],
        ]);

        $this->assertCount(3, $plan->tiers);
    }

    public function test_can_find_tier_for_amount(): void
    {
        $plan = CommissionPlan::create([
            'name' => 'Tiered Plan',
            'slug' => 'tiered-plan',
            'is_active' => true,
        ]);

        $plan->tiers()->createMany([
            ['name' => 'Bronze', 'min_threshold' => 0, 'max_threshold' => 10000, 'rate' => 5],
            ['name' => 'Silver', 'min_threshold' => 10001, 'max_threshold' => 50000, 'rate' => 7.5],
            ['name' => 'Gold', 'min_threshold' => 50001, 'max_threshold' => null, 'rate' => 10],
        ]);

        $bronzeTier = $plan->findTierForAmount(5000);
        $this->assertEquals('Bronze', $bronzeTier->name);

        $silverTier = $plan->findTierForAmount(25000);
        $this->assertEquals('Silver', $silverTier->name);

        $goldTier = $plan->findTierForAmount(100000);
        $this->assertEquals('Gold', $goldTier->name);
    }

    public function test_scope_active_filters_plans(): void
    {
        CommissionPlan::create([
            'name' => 'Active Plan',
            'slug' => 'active',
            'is_active' => true,
        ]);

        CommissionPlan::create([
            'name' => 'Inactive Plan',
            'slug' => 'inactive',
            'is_active' => false,
        ]);

        $activePlans = CommissionPlan::active()->get();

        $this->assertCount(1, $activePlans);
        $this->assertEquals('Active Plan', $activePlans->first()->name);
    }
}
