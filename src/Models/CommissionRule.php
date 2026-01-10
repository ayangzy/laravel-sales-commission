<?php

namespace SalesCommission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    use HasUlids;

    protected $fillable = [
        'plan_id',
        'name',
        'type',
        'value',
        'conditions',
        'priority',
        'is_active',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'conditions' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('sales-commission.tables.commission_rules', 'commission_rules');
    }

    /**
     * Get the plan this rule belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'plan_id');
    }

    /**
     * Scope to only active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if the conditions match the given context.
     */
    public function matchesConditions(array $context): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $key => $expectedValue) {
            $actualValue = data_get($context, $key);

            if (is_array($expectedValue)) {
                // Handle operators: ['>=', 1000], ['in', ['category1', 'category2']]
                $operator = $expectedValue[0] ?? '=';
                $compareValue = $expectedValue[1] ?? null;

                if (!$this->compareValues($actualValue, $operator, $compareValue)) {
                    return false;
                }
            } else {
                // Simple equality check
                if ($actualValue != $expectedValue) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Compare values using an operator.
     */
    protected function compareValues($actual, string $operator, $expected): bool
    {
        return match ($operator) {
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            'in' => in_array($actual, (array) $expected),
            'not_in' => !in_array($actual, (array) $expected),
            default => $actual == $expected,
        };
    }
}
