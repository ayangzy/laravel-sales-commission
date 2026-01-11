<?php

namespace SalesCommission\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\Payout;

class PayoutService
{
    /**
     * Generate a payout for a specific period.
     * Returns null if no payable earnings are found.
     */
    public function generateForPeriod(string $period): ?Payout
    {
        return Payout::generate($period);
    }

    /**
     * Generate a payout for the current period based on schedule.
     * Returns null if no payable earnings are found.
     */
    public function generateForCurrentPeriod(): ?Payout
    {
        $period = $this->getCurrentPeriod();
        return $this->generateForPeriod($period);
    }

    /**
     * Get the current period string based on configuration.
     */
    public function getCurrentPeriod(): string
    {
        $schedule = config('sales-commission.payout.schedule', 'monthly');

        return match ($schedule) {
            'weekly' => now()->format('Y-\WW'),
            'bi-weekly' => now()->format('Y-\WW'), // Same format, different logic in generator
            'monthly' => now()->format('Y-m'),
            default => now()->format('Y-m'),
        };
    }

    /**
     * Get pending payout amount for an agent.
     */
    public function getPendingPayoutAmount($agent): float
    {
        return (float) CommissionEarning::forAgent($agent)
            ->payable()
            ->sum('commission_amount');
    }

    /**
     * Get payout history for an agent.
     */
    public function getPayoutHistory($agent, int $limit = 10): Collection
    {
        return Payout::whereHas('earnings', function ($query) use ($agent) {
            $query->where('agent_type', get_class($agent))
                ->where('agent_id', $agent->getKey());
        })
            ->where('status', Payout::STATUS_PAID)
            ->orderByDesc('processed_at')
            ->limit($limit)
            ->get()
            ->map(function ($payout) use ($agent) {
                $agentAmount = $payout->earnings()
                    ->where('agent_type', get_class($agent))
                    ->where('agent_id', $agent->getKey())
                    ->sum('commission_amount');

                return [
                    'payout' => $payout,
                    'amount' => $agentAmount,
                ];
            });
    }

    /**
     * Get summary statistics for payouts.
     */
    public function getPayoutStats(?string $period = null): array
    {
        $query = Payout::query();

        if ($period) {
            $query->where('period', $period);
        }

        $paid = (clone $query)->where('status', Payout::STATUS_PAID)->sum('total_amount');
        $pending = (clone $query)->whereIn('status', [
            Payout::STATUS_DRAFT,
            Payout::STATUS_PENDING_APPROVAL,
            Payout::STATUS_APPROVED,
        ])->sum('total_amount');

        return [
            'total_paid' => $paid,
            'total_pending' => $pending,
            'payout_count' => $query->count(),
        ];
    }

    /**
     * Process approved payouts.
     */
    public function processApprovedPayouts(): Collection
    {
        $payouts = Payout::where('status', Payout::STATUS_APPROVED)->get();
        $processed = collect();

        foreach ($payouts as $payout) {
            $payout->markAsProcessing();

            // Here you would integrate with your payment provider
            // For now, we just mark as paid
            $payout->markAsPaid([
                'reference' => 'MANUAL-' . now()->timestamp,
                'method' => 'manual',
            ]);

            $processed->push($payout);
        }

        return $processed;
    }
}
