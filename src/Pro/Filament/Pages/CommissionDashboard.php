<?php

namespace SalesCommission\Pro\Filament\Pages;

use Filament\Pages\Dashboard;
use SalesCommission\Pro\Filament\Widgets\CommissionStatsWidget;
use SalesCommission\Pro\Filament\Widgets\EarningsTrendWidget;
use SalesCommission\Pro\Filament\Widgets\TopEarnersWidget;
use SalesCommission\Pro\Filament\Widgets\RecentActivityWidget;
use SalesCommission\Pro\Filament\Widgets\TierDistributionWidget;

class CommissionDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $title = 'Commission Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationGroup = 'Commissions';

    public function getWidgets(): array
    {
        return [
            CommissionStatsWidget::class,
            EarningsTrendWidget::class,
            TopEarnersWidget::class,
            TierDistributionWidget::class,
            RecentActivityWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'lg' => 3,
            'xl' => 3,
        ];
    }
}
