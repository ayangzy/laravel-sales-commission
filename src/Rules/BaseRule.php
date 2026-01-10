<?php

namespace SalesCommission\Rules;

use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;
use SalesCommission\Contracts\RuleInterface;

abstract class BaseRule implements RuleInterface
{
    protected int $priority = 0;
    protected array $conditions = [];

    /**
     * Get the priority of this rule.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set the priority of this rule.
     */
    public function withPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Set conditions for this rule.
     */
    public function when(array $conditions): self
    {
        $this->conditions = $conditions;
        return $this;
    }

    /**
     * Check if conditions match.
     */
    public function applies(Commissionable $commissionable, CommissionAgent $agent, array $context = []): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $key => $expectedValue) {
            $actualValue = data_get($context, $key);

            if ($actualValue != $expectedValue) {
                return false;
            }
        }

        return true;
    }
}
