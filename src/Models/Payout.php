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
        'currency',
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
     * Returns existing payout if one already exists for the period.
     */
    public static function generate(string $period): ?self
    {
        // Check if an active payout already exists for this period
        $existingPayout = static::where('period', $period)
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_FAILED])
            ->first();

        if ($existingPayout) {
            // Return existing payout instead of creating duplicate
            return $existingPayout;
        }

        // Get config values
        $holdPeriodDays = (int) config('sales-commission.payout.hold_period_days', 0);

        // Query for payable earnings for this period
        $query = CommissionEarning::where('status', CommissionEarning::STATUS_PAYABLE)
            ->whereNull('payout_id')
            ->where('period', $period);
        
        // Apply hold period if configured
        if ($holdPeriodDays > 0) {
            $query->where('earned_at', '<=', now()->subDays($holdPeriodDays));
        }

        // Get all matching earnings
        $earnings = $query->get();

        // If no earnings found, return null
        if ($earnings->isEmpty()) {
            return null;
        }

        // Calculate totals - cast to float to handle decimal strings
        $totalAmount = $earnings->sum(fn ($e) => (float) $e->commission_amount);
        $earningsCount = $earnings->count();

        // Create the payout
        $payout = static::create([
            'period' => $period,
            'status' => self::STATUS_DRAFT,
            'total_amount' => $totalAmount,
            'total_earnings_count' => $earningsCount,
        ]);

        // Attach earnings to payout
        CommissionEarning::whereIn('id', $earnings->pluck('id'))
            ->update(['payout_id' => $payout->id]);

        // Update status
        if (config('sales-commission.payout.auto_approve', false)) {
            $payout->approve();
        } else {
            $payout->update(['status' => self::STATUS_PENDING_APPROVAL]);
        }

        return $payout;
    }
}
