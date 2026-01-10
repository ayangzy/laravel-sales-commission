<?php

namespace SalesCommission\Contracts;

use SalesCommission\Models\CommissionEarning;

/**
 * Interface for commission calculation rules.
 */
interface RuleInterface
{
    /**
     * Get the unique identifier for this rule type.
     */
    public function getType(): string;

    /**
     * Calculate the commission amount for a given commissionable item.
     *
     * @param  Commissionable  $commissionable  The item to calculate commission on
     * @param  CommissionAgent  $agent  The agent earning the commission
     * @param  array  $context  Additional context (plan, tier, etc.)
     * @return float The calculated commission amount
     */
    public function calculate(Commissionable $commissionable, CommissionAgent $agent, array $context = []): float;

    /**
     * Check if this rule applies to the given context.
     *
     * @param  Commissionable  $commissionable  The item to check
     * @param  CommissionAgent  $agent  The agent
     * @param  array  $context  Additional context
     * @return bool Whether this rule should be applied
     */
    public function applies(Commissionable $commissionable, CommissionAgent $agent, array $context = []): bool;

    /**
     * Get the priority of this rule (higher = applied first).
     */
    public function getPriority(): int;
}
