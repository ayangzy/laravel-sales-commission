<?php

namespace SalesCommission\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface for models that can have commissions calculated on them (e.g., Order, Sale).
 */
interface Commissionable
{
    /**
     * Get the amount used for commission calculation.
     */
    public function getCommissionableAmount(): float;

    /**
     * Get the agent who should receive commission for this item.
     * Returns null if no agent is assigned.
     */
    public function getCommissionAgent(): ?Model;

    /**
     * Get the date when the commission was earned.
     * Defaults to the model's created_at if not implemented.
     */
    public function getCommissionDate(): \DateTimeInterface;

    /**
     * Get any metadata to attach to the commission earning.
     */
    public function getCommissionMeta(): array;
}
