<?php

namespace SalesCommission\Rules;

use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;

class TieredPercentageRule extends BaseRule
{
    protected array $tiers;

    /**
     * @param array $tiers Array of ['min' => x, 'max' => y, 'rate' => z]
     */
    public function __construct(array $tiers)
    {
        $this->tiers = $tiers;
    }

    public function getType(): string
    {
        return 'tiered';
    }

    public function calculate(Commissionable $commissionable, CommissionAgent $agent, array $context = []): float
    {
        $amount = $commissionable->getCommissionableAmount();
        $tier = $this->findTier($amount);

        if (!$tier) {
            return 0;
        }

        return round($amount * ($tier['rate'] / 100), 2);
    }

    protected function findTier(float $amount): ?array
    {
        foreach ($this->tiers as $tier) {
            $min = $tier['min'] ?? 0;
            $max = $tier['max'] ?? PHP_FLOAT_MAX;

            if ($amount >= $min && $amount <= $max) {
                return $tier;
            }
        }

        return null;
    }
}
