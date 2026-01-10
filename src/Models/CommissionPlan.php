<?php

namespace SalesCommission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionPlan extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'is_default',
        'effective_from',
        'effective_until',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('sales-commission.tables.commission_plans', 'commission_plans');
    }

    /**
     * Get the tiers for this plan.
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(CommissionTier::class, 'plan_id')->orderBy('min_threshold');
    }

    /**
     * Get the rules for this plan.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(CommissionRule::class, 'plan_id')->orderByDesc('priority');
    }

    /**
     * Get the earnings using this plan.
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(CommissionEarning::class, 'plan_id');
    }

    /**
     * Scope to only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', now());
            });
    }

    /**
     * Get the default plan.
     */
    public static function getDefault(): ?self
    {
        return static::active()->where('is_default', true)->first();
    }

    /**
     * Find the applicable tier for a given amount.
     */
    public function findTierForAmount(float $amount): ?CommissionTier
    {
        return $this->tiers()
            ->where('min_threshold', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_threshold')
                    ->orWhere('max_threshold', '>=', $amount);
            })
            ->first();
    }
}
