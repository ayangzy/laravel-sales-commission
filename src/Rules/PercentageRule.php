<?php

namespace SalesCommission\Rules;

use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;

class PercentageRule extends BaseRule
{
    protected float $percentage;

    public function __construct(float $percentage)
    {
        $this->percentage = $percentage;
    }

    public function getType(): string
    {
        return 'percentage';
    }

    public function calculate(Commissionable $commissionable, CommissionAgent $agent, array $context = []): float
    {
        $amount = $commissionable->getCommissionableAmount();
        return round($amount * ($this->percentage / 100), 2);
    }
}
