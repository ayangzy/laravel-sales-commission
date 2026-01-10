<?php

namespace SalesCommission\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use SalesCommission\Models\CommissionEarning;

/**
 * Trait for models that can have commissions calculated on them (Order, Sale, etc.).
 */
trait Commissionable
{
    /**
     * Get all commission earnings for this item.
     */
    public function commissionEarnings(): MorphMany
    {
        return $this->morphMany(CommissionEarning::class, 'commissionable');
    }

    /**
     * Get the amount to use for commission calculation.
     * Override this in your model.
     */
    public function getCommissionableAmount(): float
    {
        // Try common amount fields
        foreach (['total', 'amount', 'price', 'subtotal', 'grand_total'] as $field) {
            if (isset($this->$field)) {
                return (float) $this->$field;
            }
        }

        return 0;
    }

    /**
     * Get the agent who should receive commission.
     * Override this in your model.
     */
    public function getCommissionAgent(): ?Model
    {
        // Try common relationship names
        foreach (['salesRep', 'agent', 'user', 'seller', 'assignedTo'] as $relation) {
            if (method_exists($this, $relation)) {
                $related = $this->$relation;
                if ($related instanceof Model) {
                    return $related;
                }
            }
        }

        // Try common foreign key fields
        foreach (['sales_rep_id', 'agent_id', 'user_id', 'seller_id'] as $field) {
            if (isset($this->$field)) {
                $agentModel = config('sales-commission.models.agent');
                return $agentModel::find($this->$field);
            }
        }

        return null;
    }

    /**
     * Get the date when commission was earned.
     */
    public function getCommissionDate(): \DateTimeInterface
    {
        // Try common date fields
        foreach (['completed_at', 'paid_at', 'closed_at', 'created_at'] as $field) {
            if (isset($this->$field) && $this->$field) {
                return $this->$field;
            }
        }

        return now();
    }

    /**
     * Get metadata to attach to the commission.
     * Override to add custom data.
     */
    public function getCommissionMeta(): array
    {
        return [];
    }

    /**
     * Check if commission has been calculated for this item.
     */
    public function hasCommission(): bool
    {
        return $this->commissionEarnings()->exists();
    }

    /**
     * Get the total commission amount for this item.
     */
    public function getTotalCommissionAttribute(): float
    {
        return (float) $this->commissionEarnings()->sum('commission_amount');
    }
}
