<?php

namespace SalesCommission\Rules;

use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;
use SalesCommission\Models\CommissionEarning;

class BonusThresholdRule extends BaseRule
{
    protected float $threshold;
    protected float $bonusAmount;
    protected string $period;

    public function __construct(float $threshold, float $bonusAmount, string $period = 'monthly')
    {
        $this->threshold = $threshold;
        $this->bonusAmount = $bonusAmount;
        $this->period = $period;
    }

    public function getType(): string
    {
        return 'bonus_threshold';
    }

    public function calculate(Commissionable $commissionable, CommissionAgent $agent, array $context = []): float
    {
        $totalSales = $this->getAgentSalesForPeriod($agent);
        $saleAmount = $commissionable->getCommissionableAmount();

        // Check if this sale pushes them over the threshold
        $previousTotal = $totalSales - $saleAmount;

        if ($previousTotal < $this->threshold && $totalSales >= $this->threshold) {
            return $this->bonusAmount;
        }

        return 0;
    }

    protected function getAgentSalesForPeriod($agent): float
    {
        $period = match ($this->period) {
            'weekly' => now()->format('Y-\WW'),
            'monthly' => now()->format('Y-m'),
            'quarterly' => now()->format('Y-') . ceil(now()->month / 3),
            'yearly' => now()->format('Y'),
            default => now()->format('Y-m'),
        };

        return (float) CommissionEarning::where('agent_type', get_class($agent))
            ->where('agent_id', $agent->getAgentId())
            ->forPeriod($period)
            ->sum('base_amount');
    }

    public function applies(Commissionable $commissionable, CommissionAgent $agent, array $context = []): bool
    {
        if (!parent::applies($commissionable, $agent, $context)) {
            return false;
        }

        // Only apply if agent hasn't already received this bonus
        $totalSales = $this->getAgentSalesForPeriod($agent);

        return $totalSales >= $this->threshold;
    }
}
