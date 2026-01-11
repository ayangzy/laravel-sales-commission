<?php

namespace SalesCommission\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;
use SalesCommission\Events\CommissionEarned;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionRule;
use SalesCommission\Models\CommissionTier;
use SalesCommission\Traits\HasCommissions;

class CommissionCalculator
{
    protected ?CommissionPlan $plan = null;
    protected ?CommissionTier $tier = null;
    protected array $context = [];

    /**
     * Set a specific plan to use for calculation.
     */
    public function forPlan(string|int|CommissionPlan $plan): self
    {
        $this->plan = $plan instanceof CommissionPlan
            ? $plan
            : CommissionPlan::where('id', $plan)->orWhere('slug', $plan)->first();

        return $this;
    }

    /**
     * Set additional context for rule evaluation.
     */
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    /**
     * Calculate commission for a single commissionable item.
     */
    public function calculate($commissionable, $agent = null): ?CommissionEarning
    {
        // Resolve agent
        $agent = $agent ?? $this->resolveAgent($commissionable);
        if (!$agent) {
            return null;
        }

        // Resolve plan
        $plan = $this->resolvePlan($agent);
        if (!$plan) {
            return null;
        }

        // Get base amount
        $baseAmount = $this->getCommissionableAmount($commissionable);
        if ($baseAmount <= 0) {
            return null;
        }

        // Get applicable tier
        $tier = $this->resolveTier($plan, $agent);

        // Get applicable rules
        $rules = $this->getApplicableRules($plan, $commissionable, $agent);

        // Calculate commission
        $commissionAmount = $this->applyRules($rules, $baseAmount, $tier, $commissionable, $agent);

        // Don't create earning if commission is 0 (no applicable rules)
        if ($commissionAmount <= 0 && $rules->isEmpty() && !$tier) {
            return null;
        }

        // Get commissionable type (prefer getMorphClass method if available)
        $commissionableType = method_exists($commissionable, 'getMorphClass') 
            ? $commissionable->getMorphClass() 
            : get_class($commissionable);

        // Get commissionable ID (prefer getKey method)
        $commissionableId = method_exists($commissionable, 'getKey')
            ? $commissionable->getKey()
            : ($commissionable->id ?? null);

        // Create earning record
        $earnedAt = $this->getCommissionDate($commissionable);
        $earning = CommissionEarning::create([
            'agent_type' => get_class($agent),
            'agent_id' => $agent->getKey(),
            'commissionable_type' => $commissionableType,
            'commissionable_id' => $commissionableId,
            'plan_id' => $plan->id,
            'tier_id' => $tier?->id,
            'rule_id' => $rules->first()?->id,
            'base_amount' => $baseAmount,
            'commission_amount' => $commissionAmount,
            'rate' => $this->getAppliedRate($rules, $tier),
            'rate_type' => $this->getAppliedRateType($rules, $tier),
            'status' => CommissionEarning::STATUS_PENDING,
            'period' => $earnedAt->format('Y-m'),
            'earned_at' => $earnedAt,
            'metadata' => $this->buildMetadata($commissionable, $agent),
        ]);

        // Auto-approve if configured
        if (config('sales-commission.payout.auto_approve', false)) {
            $earning->approve();
        }

        // Evaluate tier progression for the agent
        $this->evaluateTierProgression($agent, $plan);

        // Fire commission earned event
        if (config('sales-commission.events.commission_earned', true)) {
            event(new CommissionEarned($earning));
        }

        // Reset state for next calculation
        $this->reset();

        return $earning;
    }

    /**
     * Calculate commissions for multiple items.
     */
    public function calculateBatch(iterable $commissionables, $agent = null): Collection
    {
        $earnings = collect();

        foreach ($commissionables as $commissionable) {
            $earning = $this->calculate($commissionable, $agent);
            if ($earning) {
                $earnings->push($earning);
            }
        }

        return $earnings;
    }

    /**
     * Get total earnings for an agent.
     */
    public function getTotalEarnings($agent, ?string $period = null): float
    {
        $query = CommissionEarning::forAgent($agent)
            ->whereIn('status', [
                CommissionEarning::STATUS_PENDING,
                CommissionEarning::STATUS_APPROVED,
                CommissionEarning::STATUS_PAID,
            ]);

        if ($period) {
            $query->forPeriod($period);
        }

        return (float) $query->sum('commission_amount');
    }

    /**
     * Get pending (unpaid) earnings for an agent.
     */
    public function getPendingEarnings($agent): float
    {
        return (float) CommissionEarning::forAgent($agent)
            ->whereIn('status', [
                CommissionEarning::STATUS_PENDING,
                CommissionEarning::STATUS_APPROVED,
            ])
            ->sum('commission_amount');
    }

    /**
     * Get the current tier for an agent.
     */
    public function getCurrentTier($agent, $plan = null): ?CommissionTier
    {
        $plan = $plan ?? $this->resolvePlan($agent);
        if (!$plan) {
            return null;
        }

        $totalSales = $this->getAgentTotalSales($agent);

        return $plan->findTierForAmount($totalSales);
    }

    /**
     * Create a split calculator for team sales.
     */
    public function split($commissionable): SplitCalculator
    {
        return new SplitCalculator($commissionable, $this);
    }

    /**
     * Resolve the agent from a commissionable item.
     */
    protected function resolveAgent($commissionable): ?Model
    {
        if ($commissionable instanceof Commissionable) {
            return $commissionable->getCommissionAgent();
        }

        // Try common relationship names
        foreach (['salesRep', 'agent', 'user', 'seller'] as $relation) {
            if (method_exists($commissionable, $relation)) {
                return $commissionable->$relation;
            }
        }

        return null;
    }

    /**
     * Resolve the plan to use for calculation.
     */
    protected function resolvePlan($agent): ?CommissionPlan
    {
        if ($this->plan) {
            return $this->plan;
        }

        // Check if agent has a specific plan
        if ($agent instanceof CommissionAgent && $planId = $agent->getCommissionPlanId()) {
            return CommissionPlan::find($planId);
        }

        // Check if agent has plan_id column
        if (isset($agent->commission_plan_id)) {
            return CommissionPlan::find($agent->commission_plan_id);
        }

        // Use default plan
        $defaultPlan = config('sales-commission.default_plan');
        if ($defaultPlan) {
            return CommissionPlan::where('id', $defaultPlan)
                ->orWhere('slug', $defaultPlan)
                ->first();
        }

        return CommissionPlan::getDefault();
    }

    /**
     * Resolve the tier based on agent's total sales.
     */
    protected function resolveTier(CommissionPlan $plan, $agent): ?CommissionTier
    {
        if ($this->tier) {
            return $this->tier;
        }

        $totalSales = $this->getAgentTotalSales($agent);

        return $plan->findTierForAmount($totalSales);
    }

    /**
     * Get agent's total sales for tier calculation.
     */
    protected function getAgentTotalSales($agent): float
    {
        // Check if agent uses HasCommissions trait
        if (method_exists($agent, 'getTotalCommissionableSales')) {
            return $agent->getTotalCommissionableSales();
        }

        // Fallback: sum of all base amounts from earnings
        return (float) CommissionEarning::forAgent($agent)
            ->whereIn('status', [
                CommissionEarning::STATUS_PENDING,
                CommissionEarning::STATUS_APPROVED,
                CommissionEarning::STATUS_PAID,
            ])
            ->sum('base_amount');
    }

    /**
     * Get applicable rules for the calculation.
     */
    protected function getApplicableRules(CommissionPlan $plan, $commissionable, $agent): Collection
    {
        $context = $this->buildRuleContext($commissionable, $agent);

        return $plan->rules()
            ->active()
            ->orderByDesc('priority')
            ->get()
            ->filter(fn(CommissionRule $rule) => $rule->matchesConditions($context));
    }

    /**
     * Apply rules to calculate commission.
     */
    protected function applyRules(Collection $rules, float $baseAmount, ?CommissionTier $tier, $commissionable, $agent): float
    {
        // If no rules, use tier rate or default
        if ($rules->isEmpty()) {
            if ($tier) {
                return $tier->calculateCommission($baseAmount);
            }
            return 0;
        }

        $commission = 0;

        foreach ($rules as $rule) {
            $commission += $this->applyRule($rule, $baseAmount, $tier);
        }

        return round($commission, 2);
    }

    /**
     * Apply a single rule.
     */
    protected function applyRule(CommissionRule $rule, float $baseAmount, ?CommissionTier $tier): float
    {
        return match ($rule->type) {
            'percentage' => $baseAmount * ($rule->value / 100),
            'fixed' => $rule->value,
            'tiered' => $tier ? $tier->calculateCommission($baseAmount) : 0,
            'bonus' => $rule->value, // Fixed bonus amount
            default => 0,
        };
    }

    /**
     * Build context for rule evaluation.
     */
    protected function buildRuleContext($commissionable, $agent): array
    {
        $context = $this->context;

        // Add commissionable properties
        if ($commissionable instanceof Commissionable) {
            $context['amount'] = $commissionable->getCommissionableAmount();
            $context = array_merge($context, $commissionable->getCommissionMeta());
        } else {
            $context['amount'] = $this->getCommissionableAmount($commissionable);
        }

        // Add agent properties
        if ($agent instanceof CommissionAgent) {
            $context['agent_id'] = $agent->getAgentId();
        }

        return $context;
    }

    /**
     * Get the commissionable amount from an item.
     */
    protected function getCommissionableAmount($commissionable): float
    {
        // Check interface first
        if ($commissionable instanceof Commissionable) {
            return $commissionable->getCommissionableAmount();
        }

        // Check if object has the method (for anonymous classes from API)
        if (method_exists($commissionable, 'getCommissionableAmount')) {
            return (float) $commissionable->getCommissionableAmount();
        }

        // Try common amount fields
        foreach (['total', 'amount', 'price', 'subtotal'] as $field) {
            if (isset($commissionable->$field)) {
                return (float) $commissionable->$field;
            }
        }

        return 0;
    }

    /**
     * Get the commission date.
     */
    protected function getCommissionDate($commissionable): \DateTimeInterface
    {
        if ($commissionable instanceof Commissionable) {
            return $commissionable->getCommissionDate();
        }

        return $commissionable->created_at ?? now();
    }

    /**
     * Get the applied rate.
     */
    protected function getAppliedRate(Collection $rules, ?CommissionTier $tier): ?float
    {
        if ($rules->isNotEmpty()) {
            $rule = $rules->first();
            if ($rule->type === 'percentage') {
                return $rule->value;
            }
        }

        return $tier?->rate;
    }

    /**
     * Get the applied rate type.
     */
    protected function getAppliedRateType(Collection $rules, ?CommissionTier $tier): ?string
    {
        if ($rules->isNotEmpty()) {
            return $rules->first()->type;
        }

        return $tier?->rate_type ?? 'percentage';
    }

    /**
     * Build metadata for the earning record.
     */
    protected function buildMetadata($commissionable, $agent): array
    {
        $metadata = [];

        if ($commissionable instanceof Commissionable) {
            $metadata['commissionable'] = $commissionable->getCommissionMeta();
        }

        return $metadata;
    }

    /**
     * Reset calculator state.
     */
    protected function reset(): void
    {
        $this->plan = null;
        $this->tier = null;
        $this->context = [];
    }

    /**
     * Evaluate tier progression for an agent after commission calculation.
     */
    protected function evaluateTierProgression($agent, CommissionPlan $plan): void
    {
        if (!config('sales-commission.events.tier_achieved', true)) {
            return;
        }

        // Use TierEvaluator service to check for tier changes
        $evaluator = new TierEvaluator();
        $evaluator->evaluate($agent, $plan);
    }
}
