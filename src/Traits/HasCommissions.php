<?php

namespace SalesCommission\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionSplit;

/**
 * Trait for agent/user models that can earn commissions.
 */
trait HasCommissions
{
    /**
     * Get all commission earnings for this agent.
     */
    public function commissionEarnings(): MorphMany
    {
        return $this->morphMany(CommissionEarning::class, 'agent');
    }

    /**
     * Get all commission splits for this agent.
     */
    public function commissionSplits(): MorphMany
    {
        return $this->morphMany(CommissionSplit::class, 'agent');
    }

    /**
     * Get total commission earnings.
     */
    public function getTotalCommissionsAttribute(): float
    {
        return (float) $this->commissionEarnings()
            ->whereIn('status', [
                CommissionEarning::STATUS_PENDING,
                CommissionEarning::STATUS_APPROVED,
                CommissionEarning::STATUS_PAID,
            ])
            ->sum('commission_amount');
    }

    /**
     * Get pending (unpaid) commissions.
     */
    public function getPendingCommissionsAttribute(): float
    {
        return (float) $this->commissionEarnings()
            ->whereIn('status', [
                CommissionEarning::STATUS_PENDING,
                CommissionEarning::STATUS_APPROVED,
            ])
            ->sum('commission_amount');
    }

    /**
     * Get paid commissions.
     */
    public function getPaidCommissionsAttribute(): float
    {
        return (float) $this->commissionEarnings()
            ->where('status', CommissionEarning::STATUS_PAID)
            ->sum('commission_amount');
    }

    /**
     * Get total commissionable sales.
     * Used for tier calculation.
     */
    public function getTotalCommissionableSales(): float
    {
        return (float) $this->commissionEarnings()
            ->whereIn('status', [
                CommissionEarning::STATUS_PENDING,
                CommissionEarning::STATUS_APPROVED,
                CommissionEarning::STATUS_PAID,
            ])
            ->sum('base_amount');
    }

    /**
     * Get commissions for a specific period.
     */
    public function getCommissionsForPeriod(string $period): float
    {
        return (float) $this->commissionEarnings()
            ->forPeriod($period)
            ->sum('commission_amount');
    }

    /**
     * Get the agent's display name.
     * Override this in your model if needed.
     */
    public function getAgentName(): string
    {
        return $this->name ?? $this->email ?? 'Agent #' . $this->getKey();
    }

    /**
     * Get the agent's ID.
     */
    public function getAgentId(): string|int
    {
        return $this->getKey();
    }

    /**
     * Get the commission plan ID for this agent.
     * Override in your model to assign specific plans.
     */
    public function getCommissionPlanId(): ?string
    {
        return $this->commission_plan_id ?? null;
    }
}
