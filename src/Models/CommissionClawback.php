<?php

namespace SalesCommission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SalesCommission\Events\CommissionClawedBack;

class CommissionClawback extends Model
{
    use HasUlids;

    public const REASON_REFUND = 'refund';
    public const REASON_CHARGEBACK = 'chargeback';
    public const REASON_CANCELLATION = 'cancellation';
    public const REASON_MANUAL = 'manual';

    protected $fillable = [
        'earning_id',
        'reason',
        'amount',
        'notes',
        'processed_by',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $dispatchesEvents = [
        'created' => CommissionClawedBack::class,
    ];

    public function getTable(): string
    {
        return config('sales-commission.tables.commission_clawbacks', 'commission_clawbacks');
    }

    /**
     * Get the original earning.
     */
    public function earning(): BelongsTo
    {
        return $this->belongsTo(CommissionEarning::class, 'earning_id');
    }

    /**
     * Scope to clawbacks for a specific reason.
     */
    public function scopeForReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Check if the clawback is within the grace period.
     */
    public static function isWithinGracePeriod(CommissionEarning $earning): bool
    {
        $gracePeriodDays = config('sales-commission.clawback.grace_period_days');

        if ($gracePeriodDays === null) {
            return true;
        }

        return $earning->earned_at->addDays($gracePeriodDays)->isFuture();
    }
}
