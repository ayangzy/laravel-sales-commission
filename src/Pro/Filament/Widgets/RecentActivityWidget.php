<?php

namespace SalesCommission\Pro\Filament\Widgets;

use Filament\Widgets\Widget;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionClawback;
use SalesCommission\Models\Payout;

class RecentActivityWidget extends Widget
{
    protected static string $view = 'sales-commission::filament.widgets.recent-activity';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    public function getActivities(): array
    {
        $activities = [];

        // Recent earnings
        $earnings = CommissionEarning::with([])
            ->latest('earned_at')
            ->take(5)
            ->get()
            ->map(fn ($earning) => [
                'type' => 'earning',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'success',
                'title' => 'Commission Earned',
                'description' => '$' . number_format($earning->commission_amount, 2) . ' by Agent #' . $earning->agent_id,
                'timestamp' => $earning->earned_at,
            ]);

        // Recent clawbacks
        $clawbacks = CommissionClawback::latest()
            ->take(3)
            ->get()
            ->map(fn ($clawback) => [
                'type' => 'clawback',
                'icon' => 'heroicon-o-arrow-uturn-left',
                'color' => 'danger',
                'title' => 'Commission Clawed Back',
                'description' => '$' . number_format($clawback->amount, 2) . ' - ' . ucfirst($clawback->reason),
                'timestamp' => $clawback->created_at,
            ]);

        // Recent payouts
        $payouts = Payout::whereIn('status', [
            Payout::STATUS_PAID,
            Payout::STATUS_APPROVED,
            Payout::STATUS_PENDING_APPROVAL,
        ])
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($payout) => [
                'type' => 'payout',
                'icon' => $payout->status === Payout::STATUS_PAID ? 'heroicon-o-check-circle' : 'heroicon-o-clock',
                'color' => $payout->status === Payout::STATUS_PAID ? 'info' : 'warning',
                'title' => 'Payout ' . ucfirst(str_replace('_', ' ', $payout->status)),
                'description' => '$' . number_format($payout->total_amount, 2) . ' for ' . $payout->period,
                'timestamp' => $payout->status === Payout::STATUS_PAID ? $payout->processed_at : $payout->created_at,
            ]);

        // Merge and sort by timestamp
        $activities = $earnings->concat($clawbacks)->concat($payouts)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values()
            ->toArray();

        return $activities;
    }
}
