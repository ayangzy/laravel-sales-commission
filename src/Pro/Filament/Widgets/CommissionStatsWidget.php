<?php

namespace SalesCommission\Pro\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionClawback;
use SalesCommission\Models\Payout;

class CommissionStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $currentPeriod = $this->filters['period'] ?? now()->format('Y-m');
        $previousPeriod = Carbon::createFromFormat('Y-m', $currentPeriod)->subMonth()->format('Y-m');
        $periodDate = Carbon::createFromFormat('Y-m', $currentPeriod);

        // Period earnings
        $periodEarnings = CommissionEarning::where('period', $currentPeriod)
            ->sum('commission_amount');
        $previousEarnings = CommissionEarning::where('period', $previousPeriod)
            ->sum('commission_amount');

        // Pending payouts for this period
        $pendingPayouts = Payout::where('period', $currentPeriod)
            ->whereIn('status', [
                Payout::STATUS_PENDING_APPROVAL,
                Payout::STATUS_APPROVED,
            ])->sum('total_amount');

        $pendingCount = Payout::where('period', $currentPeriod)
            ->whereIn('status', [
                Payout::STATUS_PENDING_APPROVAL,
                Payout::STATUS_APPROVED,
            ])->count();

        // Paid for this period
        $paidThisPeriod = Payout::where('period', $currentPeriod)
            ->where('status', Payout::STATUS_PAID)
            ->sum('total_amount');

        // Clawbacks for this period
        $clawbacksThisPeriod = CommissionClawback::whereMonth('created_at', $periodDate->month)
            ->whereYear('created_at', $periodDate->year)
            ->sum('amount');

        // YTD earnings
        $ytdEarnings = CommissionEarning::whereYear('earned_at', $periodDate->year)
            ->sum('commission_amount');

        // Calculate trend
        $trend = $previousEarnings > 0 
            ? round((($periodEarnings - $previousEarnings) / $previousEarnings) * 100, 1) 
            : 0;

        $periodLabel = $periodDate->format('M Y');

        return [
            Stat::make("Earnings ({$periodLabel})", '$' . number_format($periodEarnings, 2))
                ->description($trend >= 0 ? "+{$trend}% vs prev month" : "{$trend}% vs prev month")
                ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger')
                ->chart($this->getEarningsChartData($currentPeriod)),

            Stat::make('Pending Payouts', '$' . number_format($pendingPayouts, 2))
                ->description("{$pendingCount} awaiting action")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Paid', '$' . number_format($paidThisPeriod, 2))
                ->description("Processed for {$periodLabel}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Clawbacks', '$' . number_format($clawbacksThisPeriod, 2))
                ->description("Reversed in {$periodLabel}")
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($clawbacksThisPeriod > 0 ? 'danger' : 'gray'),

            Stat::make('YTD Earnings', '$' . number_format($ytdEarnings, 2))
                ->description('Total ' . $periodDate->year)
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }

    /**
     * Get mini chart data for earnings trend.
     */
    protected function getEarningsChartData(string $currentPeriod): array
    {
        $data = [];
        $startPeriod = Carbon::createFromFormat('Y-m', $currentPeriod)->subMonths(5);
        
        for ($i = 0; $i < 6; $i++) {
            $period = $startPeriod->copy()->addMonths($i)->format('Y-m');
            $data[] = (float) CommissionEarning::where('period', $period)
                ->sum('commission_amount');
        }

        return $data;
    }
}

