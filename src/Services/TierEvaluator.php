<?php

namespace SalesCommission\Services;

use SalesCommission\Events\TierAchieved;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionTier;

class TierEvaluator
{
    /**
     * Evaluate and update an agent's tier.
     */
    public function evaluate($agent, ?CommissionPlan $plan = null): ?CommissionTier
    {
        $plan = $plan ?? $this->getAgentPlan($agent);
        if (!$plan) {
            return null;
        }

        $totalSales = $this->getAgentTotalSales($agent);
        $currentTier = $plan->findTierForAmount($totalSales);

        // Check if tier has changed
        $previousTier = $this->getAgentPreviousTier($agent, $plan);

        if ($currentTier && (!$previousTier || $currentTier->id !== $previousTier->id)) {
            // Tier has changed - dispatch event
            if ($this->isTierUpgrade($previousTier, $currentTier)) {
                event(new TierAchieved($agent, $currentTier, $totalSales));
            }

            // Store current tier on agent if they have the column
            $this->updateAgentTier($agent, $currentTier);
        }

        return $currentTier;
    }

    /**
     * Get the agent's current plan.
     */
    protected function getAgentPlan($agent): ?CommissionPlan
    {
        if (isset($agent->commission_plan_id)) {
            return CommissionPlan::find($agent->commission_plan_id);
        }

        return CommissionPlan::getDefault();
    }

    /**
     * Get the agent's total sales.
     */
    protected function getAgentTotalSales($agent): float
    {
        if (method_exists($agent, 'getTotalCommissionableSales')) {
            return $agent->getTotalCommissionableSales();
        }

        return (float) CommissionEarning::forAgent($agent)
            ->whereIn('status', [
                CommissionEarning::STATUS_PENDING,
                CommissionEarning::STATUS_APPROVED,
                CommissionEarning::STATUS_PAID,
            ])
            ->sum('base_amount');
    }

    /**
     * Get the agent's previous tier.
     */
    protected function getAgentPreviousTier($agent, CommissionPlan $plan): ?CommissionTier
    {
        if (isset($agent->commission_tier_id)) {
            return CommissionTier::find($agent->commission_tier_id);
        }

        return null;
    }

    /**
     * Check if the new tier is an upgrade.
     */
    protected function isTierUpgrade(?CommissionTier $previousTier, CommissionTier $currentTier): bool
    {
        if (!$previousTier) {
            return true;
        }

        return $currentTier->min_threshold > $previousTier->min_threshold;
    }

    /**
     * Update the agent's tier reference.
     */
    protected function updateAgentTier($agent, CommissionTier $tier): void
    {
        if (isset($agent->commission_tier_id)) {
            $agent->update(['commission_tier_id' => $tier->id]);
        }
    }

    /**
     * Recalculate tiers for all agents on a plan.
     */
    public function recalculateForPlan(CommissionPlan $plan): int
    {
        $agentModel = config('sales-commission.models.agent');
        $count = 0;

        // Get unique agents with earnings on this plan
        $agents = CommissionEarning::where('plan_id', $plan->id)
            ->distinct()
            ->get(['agent_type', 'agent_id']);

        foreach ($agents as $earningRef) {
            $agent = $earningRef->agent_type::find($earningRef->agent_id);
            if ($agent) {
                $this->evaluate($agent, $plan);
                $count++;
            }
        }

        return $count;
    }
}
