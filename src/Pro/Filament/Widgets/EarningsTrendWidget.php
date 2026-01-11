<?php

namespace SalesCommission\Pro\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use SalesCommission\Models\CommissionEarning;

class EarningsTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Earnings Trend';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $labels = [];
        $earnings = [];
        $clawbacks = [];

        // Last 6 months of data
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $period = $date->format('Y-m');
            $labels[] = $date->format('M Y');

            $earnings[] = (float) CommissionEarning::where('period', $period)
                ->whereIn('status', [
                    CommissionEarning::STATUS_PENDING,
                    CommissionEarning::STATUS_PAYABLE,
                    CommissionEarning::STATUS_PAID,
                ])
                ->sum('commission_amount');

            $clawbacks[] = (float) CommissionEarning::where('period', $period)
                ->where('status', CommissionEarning::STATUS_CLAWED_BACK)
                ->sum('commission_amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Earnings',
                    'data' => $earnings,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Clawbacks',
                    'data' => $clawbacks,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return '$' + value.toLocaleString(); }",
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { return context.dataset.label + ': $' + context.parsed.y.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
