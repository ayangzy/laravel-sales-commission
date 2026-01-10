<?php

namespace SalesCommission\Rules;

use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;

class FlatAmountRule extends BaseRule
{
    protected float $amount;

    public function __construct(float $amount)
    {
        $this->amount = $amount;
    }

    public function getType(): string
    {
        return 'fixed';
    }

    public function calculate(Commissionable $commissionable, CommissionAgent $agent, array $context = []): float
    {
        return $this->amount;
    }
}
