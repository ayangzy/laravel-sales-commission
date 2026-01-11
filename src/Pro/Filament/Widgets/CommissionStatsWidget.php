<?php

namespace SalesCommission\Pro\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionClawback;
use SalesCommission\Models\Payout;
use SalesCommission\Support\CurrencyHelper;

class CommissionStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        [$startDate, $endDate] = $this->getDateRange();
        $symbol = CurrencyHelper::getConfiguredSymbol();
        $label = $this->getDateLabel($startDate, $endDate);

        // Period earnings
        $periodEarnings = CommissionEarning::whereBetween('earned_at', [$startDate, $endDate])
            ->sum('commission_amount');

        // Previous period for comparison
        $periodLength = max(1, $startDate->diffInDays($endDate));
        $previousStart = $startDate->copy()->subDays($periodLength + 1);
        $previousEnd = $startDate->copy()->subDay();
        $previousEarnings = CommissionEarning::whereBetween('earned_at', [$previousStart, $previousEnd])
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

        // Paid in range
        $paidAmount = Payout::where('status', Payout::STATUS_PAID)
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('total_amount');

        // Clawbacks in range
        $clawbacksAmount = CommissionClawback::whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // YTD earnings
        $ytdStart = Carbon::now()->startOfYear();
        $ytdEarnings = CommissionEarning::whereBetween('earned_at', [$ytdStart, now()])
            ->sum('commission_amount');

        // Calculate trend
        $trend = $previousEarnings > 0 
            ? round((($periodEarnings - $previousEarnings) / $previousEarnings) * 100, 1) 
            : 0;

        return [
            Stat::make("Earnings", $symbol . number_format($periodEarnings, 2))
                ->description($label . ' | ' . ($trend >= 0 ? "+{$trend}%" : "{$trend}%") . ' vs previous')
                ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger')
                ->chart($this->getEarningsChartData($startDate, $endDate)),

            Stat::make('Pending Payouts', $symbol . number_format($pendingPayouts, 2))
                ->description("{$pendingCount} awaiting action")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Paid', $symbol . number_format($paidAmount, 2))
                ->description("Processed in period")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Clawbacks', $symbol . number_format($clawbacksAmount, 2))
                ->description("Reversed in period")
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($clawbacksAmount > 0 ? 'danger' : 'gray'),

            Stat::make('YTD Earnings', $symbol . number_format($ytdEarnings, 2))
                ->description('Total ' . now()->year)
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }

    /**
     * Get date range from filters.
     */
    protected function getDateRange(): array
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        return [$start, $end];
    }

    /**
     * Get readable date label.
     */
    protected function getDateLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('M d, Y');
        }
        
        if ($start->isSameMonth($end)) {
            return $start->format('M d') . ' - ' . $end->format('d, Y');
        }
        
        return $start->format('M d') . ' - ' . $end->format('M d, Y');
    }

    /**
     * Get mini chart data for earnings trend.
     */
    protected function getEarningsChartData(Carbon $startDate, Carbon $endDate): array
    {
        $data = [];
        $periodLength = max(1, $startDate->diffInDays($endDate));
        
        // Show 6 periods for the chart
        for ($i = 5; $i >= 0; $i--) {
            $periodStart = $startDate->copy()->subDays($periodLength * $i);
            $periodEnd = $periodStart->copy()->addDays($periodLength);
            
            $data[] = (float) CommissionEarning::whereBetween('earned_at', [$periodStart, $periodEnd])
                ->sum('commission_amount');
        }

        return $data;
    }
}
