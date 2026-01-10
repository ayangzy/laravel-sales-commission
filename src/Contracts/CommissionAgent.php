<?php

namespace SalesCommission\Contracts;

/**
 * Interface for models that can earn commissions (e.g., User, SalesRep).
 */
interface CommissionAgent
{
    /**
     * Get the agent's unique identifier.
     */
    public function getAgentId(): string|int;

    /**
     * Get the agent's display name.
     */
    public function getAgentName(): string;

    /**
     * Get the commission plan assigned to this agent.
     * Returns null to use the default plan.
     */
    public function getCommissionPlanId(): ?string;
}
