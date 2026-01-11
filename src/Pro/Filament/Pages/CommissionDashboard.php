<?php

namespace SalesCommission\Pro\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use SalesCommission\Pro\Filament\Widgets\BonusAwardsWidget;
use SalesCommission\Pro\Filament\Widgets\CommissionStatsWidget;
use SalesCommission\Pro\Filament\Widgets\EarningsTrendWidget;
use SalesCommission\Pro\Filament\Widgets\TopEarnersWidget;
use SalesCommission\Pro\Filament\Widgets\RecentActivityWidget;
use SalesCommission\Pro\Filament\Widgets\TierDistributionWidget;

class CommissionDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $title = 'Sales Commission';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationGroup = 'Commissions';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Dashboard Data')
                    ->description('Select a date range to filter all dashboard statistics and reports')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()->startOfMonth())
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('M d, Y'),
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('M d, Y'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            CommissionStatsWidget::class,
            EarningsTrendWidget::class,
            TopEarnersWidget::class,      // Column 1 (with BonusAwards below)
            BonusAwardsWidget::class,     // Column 1 (below TopEarners)
            RecentActivityWidget::class,  // Column 2 (with TierDistribution below)
            TierDistributionWidget::class, // Column 2 (below RecentActivity)
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'lg' => 2,
            'xl' => 2,
        ];
    }
}
