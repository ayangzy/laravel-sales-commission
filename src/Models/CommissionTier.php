<?php

namespace SalesCommission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionTier extends Model
{
    use HasUlids;

    protected $fillable = [
        'plan_id',
        'name',
        'min_threshold',
        'max_threshold',
        'rate',
        'rate_type',
        'bonus_amount',
        'description',
        'color',
    ];

    protected $casts = [
        'min_threshold' => 'decimal:2',
        'max_threshold' => 'decimal:2',
        'rate' => 'decimal:4',
        'bonus_amount' => 'decimal:2',
    ];

    public function getTable(): string
    {
        return config('sales-commission.tables.commission_tiers', 'commission_tiers');
    }

    /**
     * Get the plan this tier belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'plan_id');
    }

    /**
     * Check if a given amount falls within this tier.
     */
    public function containsAmount(float $amount): bool
    {
        if ($amount < $this->min_threshold) {
            return false;
        }

        if ($this->max_threshold !== null && $amount > $this->max_threshold) {
            return false;
        }

        return true;
    }

    /**
     * Calculate commission for a given amount using this tier's rate.
     */
    public function calculateCommission(float $amount): float
    {
        return match ($this->rate_type) {
            'percentage' => $amount * ($this->rate / 100),
            'fixed' => $this->rate,
            default => $amount * ($this->rate / 100),
        };
    }
}
