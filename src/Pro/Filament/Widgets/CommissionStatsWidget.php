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
        [$startDate, $endDate, $label] = $this->getDateRange();

        // Period earnings
        $periodEarnings = CommissionEarning::whereBetween('earned_at', [$startDate, $endDate])
            ->sum('commission_amount');

        // Previous period for comparison
        $periodLength = $startDate->diffInDays($endDate);
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
            Stat::make("Earnings ({$label})", '$' . number_format($periodEarnings, 2))
                ->description($trend >= 0 ? "+{$trend}% vs previous" : "{$trend}% vs previous")
                ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger')
                ->chart($this->getEarningsChartData($startDate, $endDate)),

            Stat::make('Pending Payouts', '$' . number_format($pendingPayouts, 2))
                ->description("{$pendingCount} awaiting action")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Paid', '$' . number_format($paidAmount, 2))
                ->description("Processed in {$label}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Clawbacks', '$' . number_format($clawbacksAmount, 2))
                ->description("Reversed in {$label}")
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color($clawbacksAmount > 0 ? 'danger' : 'gray'),

            Stat::make('YTD Earnings', '$' . number_format($ytdEarnings, 2))
                ->description('Total ' . now()->year)
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }

    /**
     * Get date range based on selected filter.
     */
    protected function getDateRange(): array
    {
        $timeRange = $this->filters['time_range'] ?? 'this_month';
        $period = $this->filters['period'] ?? null;

        // If specific period is selected, use it
        if ($period) {
            $date = Carbon::createFromFormat('Y-m', $period);
            return [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
                $date->format('M Y'),
            ];
        }

        return match ($timeRange) {
            'this_week' => [now()->startOfWeek(), now()->endOfWeek(), 'This Week'],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek(), 'Last Week'],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth(), now()->format('M Y')],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth(), now()->subMonth()->format('M Y')],
            'this_quarter' => [now()->firstOfQuarter(), now()->lastOfQuarter(), 'Q' . ceil(now()->month / 3) . ' ' . now()->year],
            'last_quarter' => [now()->subQuarter()->firstOfQuarter(), now()->subQuarter()->lastOfQuarter(), 'Last Quarter'],
            'this_year' => [now()->startOfYear(), now()->endOfYear(), now()->year],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear(), now()->subYear()->year],
            default => [now()->startOfMonth(), now()->endOfMonth(), now()->format('M Y')],
        };
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
