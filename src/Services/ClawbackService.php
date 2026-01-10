<?php

namespace SalesCommission\Services;

use Illuminate\Database\Eloquent\Model;
use SalesCommission\Models\CommissionClawback;
use SalesCommission\Models\CommissionEarning;

class ClawbackService
{
    /**
     * Process a clawback for an earning.
     */
    public function clawback(
        CommissionEarning $earning,
        string $reason,
        ?float $amount = null,
        ?string $notes = null,
        ?int $processedBy = null
    ): ?CommissionClawback {
        // Check if clawbacks are enabled
        if (!config('sales-commission.clawback.enabled', true)) {
            return null;
        }

        // Check grace period
        if (!CommissionClawback::isWithinGracePeriod($earning)) {
            return null;
        }

        // Default to full commission amount if not specified
        $clawbackAmount = $amount ?? $earning->commission_amount;

        // Ensure we don't clawback more than earned
        $existingClawbacks = $earning->clawbacks()->sum('amount');
        $maxClawback = $earning->commission_amount - $existingClawbacks;
        $clawbackAmount = min($clawbackAmount, $maxClawback);

        if ($clawbackAmount <= 0) {
            return null;
        }

        $clawback = CommissionClawback::create([
            'earning_id' => $earning->id,
            'reason' => $reason,
            'amount' => $clawbackAmount,
            'notes' => $notes,
            'processed_by' => $processedBy,
            'processed_at' => now(),
        ]);

        // Update earning status if fully clawed back
        if ($earning->net_amount <= 0) {
            $earning->update(['status' => CommissionEarning::STATUS_CLAWED_BACK]);
        }

        return $clawback;
    }

    /**
     * Clawback all commissions for a commissionable item.
     */
    public function clawbackForCommissionable(
        Model $commissionable,
        string $reason,
        ?string $notes = null,
        ?int $processedBy = null
    ): array {
        $earnings = CommissionEarning::where('commissionable_type', get_class($commissionable))
            ->where('commissionable_id', $commissionable->getKey())
            ->whereNotIn('status', [
                CommissionEarning::STATUS_CLAWED_BACK,
                CommissionEarning::STATUS_CANCELLED,
            ])
            ->get();

        $clawbacks = [];

        foreach ($earnings as $earning) {
            $clawback = $this->clawback($earning, $reason, null, $notes, $processedBy);
            if ($clawback) {
                $clawbacks[] = $clawback;
            }
        }

        return $clawbacks;
    }

    /**
     * Process a partial clawback (e.g., for partial refund).
     */
    public function partialClawback(
        CommissionEarning $earning,
        float $refundAmount,
        string $reason = CommissionClawback::REASON_REFUND,
        ?string $notes = null
    ): ?CommissionClawback {
        // Calculate proportional commission to clawback
        if ($earning->base_amount <= 0) {
            return null;
        }

        $proportion = $refundAmount / $earning->base_amount;
        $clawbackAmount = $earning->commission_amount * $proportion;

        return $this->clawback($earning, $reason, $clawbackAmount, $notes);
    }
}
