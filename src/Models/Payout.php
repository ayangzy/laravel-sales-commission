<?php

namespace SalesCommission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SalesCommission\Events\PayoutProcessed;

class Payout extends Model
{
    use HasUlids;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'period',
        'total_amount',
        'total_earnings_count',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'processed_at',
        'payment_reference',
        'payment_method',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_earnings_count' => 'integer',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('sales-commission.tables.payouts', 'payouts');
    }

    /**
     * Get the earnings in this payout.
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(CommissionEarning::class);
    }

    /**
     * Calculate and update the totals.
     */
    public function recalculateTotals(): self
    {
        $this->update([
            'total_amount' => $this->earnings()->sum('commission_amount'),
            'total_earnings_count' => $this->earnings()->count(),
        ]);

        return $this;
    }

    /**
     * Approve the payout.
     */
    public function approve(?int $approvedBy = null): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);

        return $this;
    }

    /**
     * Mark as paid.
     */
    public function markAsPaid(array $paymentDetails = []): self
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'processed_at' => now(),
            'payment_reference' => $paymentDetails['reference'] ?? null,
            'payment_method' => $paymentDetails['method'] ?? null,
        ]);

        // Mark all earnings as paid
        $this->earnings()->update([
            'status' => CommissionEarning::STATUS_PAID,
            'paid_at' => now(),
        ]);

        event(new PayoutProcessed($this));

        return $this;
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason,
        ]);

        return $this;
    }

    /**
     * Create a new payout for a period.
     * Returns null if no payable earnings are found.
     */
    public static function generate(string $period): ?self
    {
        // Get config values
        $minThreshold = config('sales-commission.payout.min_threshold', 0);
        $holdPeriodDays = config('sales-commission.payout.hold_period_days', 0);

        // Query for payable earnings for this period that have passed the hold period
        $earningsQuery = CommissionEarning::payable()
            ->where('period', $period);
        
        if ($holdPeriodDays > 0) {
            $earningsQuery->where('earned_at', '<=', now()->subDays($holdPeriodDays));
        }

        // Get all matching earnings
        $allEarnings = $earningsQuery->get();

        // Filter by agent min threshold
        $qualifiedEarnings = $allEarnings
            ->groupBy(function ($earning) {
                return $earning->agent_type . ':' . $earning->agent_id;
            })
            ->filter(function ($earnings) use ($minThreshold) {
                return $earnings->sum('commission_amount') >= $minThreshold;
            })
            ->flatten();

        // If no qualified earnings, return null (don't create empty payout)
        if ($qualifiedEarnings->isEmpty()) {
            return null;
        }

        // Create the payout only if we have earnings
        $payout = static::create([
            'period' => $period,
            'status' => self::STATUS_DRAFT,
            'total_amount' => $qualifiedEarnings->sum('commission_amount'),
            'total_earnings_count' => $qualifiedEarnings->count(),
        ]);

        // Attach earnings to payout
        $qualifiedEarnings->each(function ($earning) use ($payout) {
            $earning->update(['payout_id' => $payout->id]);
        });

        if (config('sales-commission.payout.auto_approve', false)) {
            $payout->approve();
        } else {
            $payout->update(['status' => self::STATUS_PENDING_APPROVAL]);
        }

        return $payout;
    }
}
