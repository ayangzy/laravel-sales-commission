<?php

namespace SalesCommission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use SalesCommission\Events\CommissionEarned;

class CommissionEarning extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAYABLE = 'payable';
    public const STATUS_HELD = 'held';
    public const STATUS_PAID = 'paid';
    public const STATUS_CLAWED_BACK = 'clawed_back';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'agent_type',
        'agent_id',
        'commissionable_type',
        'commissionable_id',
        'plan_id',
        'tier_id',
        'rule_id',
        'base_amount',
        'commission_amount',
        'rate',
        'rate_type',
        'currency',
        'status',
        'period',
        'earned_at',
        'approved_at',
        'approved_by',
        'locked_at',
        'paid_at',
        'payout_id',
        'metadata',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'rate' => 'decimal:4',
        'earned_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $dispatchesEvents = [
        'created' => CommissionEarned::class,
    ];

    public function getTable(): string
    {
        return config('sales-commission.tables.commission_earnings', 'commission_earnings');
    }

    /**
     * Get the agent who earned this commission.
     */
    public function agent(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the commissionable item (sale, order, etc.).
     */
    public function commissionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the plan used for this earning.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CommissionPlan::class, 'plan_id');
    }

    /**
     * Get the tier applied to this earning.
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(CommissionTier::class, 'tier_id');
    }

    /**
     * Get the rule applied to this earning.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'rule_id');
    }

    /**
     * Get the payout this earning belongs to.
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    /**
     * Get splits for this earning.
     */
    public function splits(): HasMany
    {
        return $this->hasMany(CommissionSplit::class, 'earning_id');
    }

    /**
     * Get clawbacks for this earning.
     */
    public function clawbacks(): HasMany
    {
        return $this->hasMany(CommissionClawback::class, 'earning_id');
    }

    /**
     * Get the net amount after clawbacks.
     */
    public function getNetAmountAttribute(): float
    {
        return $this->commission_amount - $this->clawbacks->sum('amount');
    }

    /**
     * Scope to pending earnings.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to approved earnings.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to earnings ready for payout.
     */
    public function scopePayable($query)
    {
        return $query->where('status', self::STATUS_PAYABLE)
            ->whereNull('payout_id');
    }

    /**
     * Scope to earnings for a specific agent.
     */
    public function scopeForAgent($query, Model $agent)
    {
        return $query->where('agent_type', get_class($agent))
            ->where('agent_id', $agent->getKey());
    }

    /**
     * Scope to earnings for a specific period.
     */
    public function scopeForPeriod($query, string $period)
    {
        // Format: 'YYYY-MM' for monthly, 'YYYY-WW' for weekly
        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            [$year, $month] = explode('-', $period);
            return $query->whereYear('earned_at', $year)
                ->whereMonth('earned_at', $month);
        }

        return $query;
    }

    /**
     * Approve this earning.
     */
    public function approve(?int $approvedBy = null): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ]);

        return $this;
    }

    /**
     * Mark as paid.
     */
    public function markAsPaid(): self
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return $this;
    }
}
