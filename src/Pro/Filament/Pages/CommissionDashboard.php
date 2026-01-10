<?php

namespace SalesCommission\Pro\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\Payout;
use SalesCommission\Models\CommissionClawback;

class CommissionDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'sales-commission::filament.pages.commission-dashboard';

    protected static ?string $title = 'Commission Dashboard';

    protected static ?string $navigationLabel = 'Commission Dashboard';

    protected static ?int $navigationSort = -2;

    public function getStats(): array
    {
        $currentPeriod = now()->format('Y-m');
        $lastPeriod = now()->subMonth()->format('Y-m');

        $currentEarnings = CommissionEarning::where('period', $currentPeriod)->sum('commission_amount');
        $lastEarnings = CommissionEarning::where('period', $lastPeriod)->sum('commission_amount');

        $pendingPayouts = Payout::whereIn('status', [Payout::STATUS_PENDING_APPROVAL, Payout::STATUS_APPROVED])
            ->sum('total_amount');

        $paidThisMonth = Payout::where('status', Payout::STATUS_PAID)
            ->whereMonth('processed_at', now()->month)
            ->sum('total_amount');

        $clawbacksThisMonth = CommissionClawback::whereMonth('created_at', now()->month)
            ->sum('amount');

        $activePlans = CommissionPlan::where('is_active', true)->count();

        return [
            [
                'label' => 'Earnings This Month',
                'value' => '$' . number_format($currentEarnings, 2),
                'description' => $this->getChangeDescription($currentEarnings, $lastEarnings),
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'success',
            ],
            [
                'label' => 'Pending Payouts',
                'value' => '$' . number_format($pendingPayouts, 2),
                'description' => 'Awaiting processing',
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
            ],
            [
                'label' => 'Paid This Month',
                'value' => '$' . number_format($paidThisMonth, 2),
                'description' => 'Successfully processed',
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ],
            [
                'label' => 'Clawbacks This Month',
                'value' => '$' . number_format($clawbacksThisMonth, 2),
                'description' => 'Reversed commissions',
                'icon' => 'heroicon-o-arrow-uturn-left',
                'color' => 'danger',
            ],
            [
                'label' => 'Active Plans',
                'value' => $activePlans,
                'description' => 'Commission plans',
                'icon' => 'heroicon-o-document-text',
                'color' => 'info',
            ],
        ];
    }

    protected function getChangeDescription(float $current, float $previous): string
    {
        if ($previous == 0) {
            return 'No data from last month';
        }

        $change = (($current - $previous) / $previous) * 100;
        $direction = $change >= 0 ? '↑' : '↓';
        
        return $direction . ' ' . abs(round($change, 1)) . '% vs last month';
    }
}
