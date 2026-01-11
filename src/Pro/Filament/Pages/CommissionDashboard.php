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
                Section::make()
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('From')
                            ->default(now()->startOfMonth())
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('M d, Y'),
                        DatePicker::make('end_date')
                            ->label('To')
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('M d, Y'),
                    ])
                    ->columns(2)
                    ->compact(),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            CommissionStatsWidget::class,
            EarningsTrendWidget::class,
            TopEarnersWidget::class,
            BonusAwardsWidget::class,
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
