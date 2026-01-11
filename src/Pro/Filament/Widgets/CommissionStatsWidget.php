<?php

namespace SalesCommission\Pro\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionClawback;
use SalesCommission\Models\Payout;

class CommissionStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $currentPeriod = now()->format('Y-m');
        $lastPeriod = now()->subMonth()->format('Y-m');

        // Current month earnings
        $currentEarnings = CommissionEarning::where('period', $currentPeriod)
            ->sum('commission_amount');
        $lastEarnings = CommissionEarning::where('period', $lastPeriod)
            ->sum('commission_amount');

        // Pending payouts
        $pendingPayouts = Payout::whereIn('status', [
            Payout::STATUS_PENDING_APPROVAL,
            Payout::STATUS_APPROVED,
        ])->sum('total_amount');

        $pendingCount = Payout::whereIn('status', [
            Payout::STATUS_PENDING_APPROVAL,
            Payout::STATUS_APPROVED,
        ])->count();

        // Paid this month
        $paidThisMonth = Payout::where('status', Payout::STATUS_PAID)
            ->whereMonth('processed_at', now()->month)
            ->whereYear('processed_at', now()->year)
            ->sum('total_amount');

        // Clawbacks this month
        $clawbacksThisMonth = CommissionClawback::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // YTD earnings
        $ytdEarnings = CommissionEarning::whereYear('earned_at', now()->year)
            ->sum('commission_amount');

        // Calculate trend
        $trend = $lastEarnings > 0 
            ? round((($currentEarnings - $lastEarnings) / $lastEarnings) * 100, 1) 
            : 0;

        return [
            Stat::make('Earnings This Month', '$' . number_format($currentEarnings, 2))
                ->description($trend >= 0 ? "+{$trend}% from last month" : "{$trend}% from last month")
                ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger')
                ->chart($this->getEarningsChartData()),

            Stat::make('Pending Payouts', '$' . number_format($pendingPayouts, 2))
                ->description("{$pendingCount} payouts awaiting action")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Paid This Month', '$' . number_format($paidThisMonth, 2))
                ->description('Successfully processed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Clawbacks', '$' . number_format($clawbacksThisMonth, 2))
                ->description('Reversed this month')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($clawbacksThisMonth > 0 ? 'danger' : 'gray'),

            Stat::make('YTD Earnings', '$' . number_format($ytdEarnings, 2))
                ->description('Total ' . now()->year)
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }

    /**
     * Get mini chart data for earnings trend.
     */
    protected function getEarningsChartData(): array
    {
        $data = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $period = now()->subMonths($i)->format('Y-m');
            $data[] = (float) CommissionEarning::where('period', $period)
                ->sum('commission_amount');
        }

        return $data;
    }
}
