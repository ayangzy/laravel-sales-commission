<?php

namespace SalesCommission\Pro\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms\Components\Select;
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
                Select::make('time_range')
                    ->label('Period-Based Tracking')
                    ->options([
                        'this_week' => 'This Week',
                        'last_week' => 'Last Week',
                        'this_month' => 'This Month (' . now()->format('M Y') . ')',
                        'last_month' => 'Last Month (' . now()->subMonth()->format('M Y') . ')',
                        'this_quarter' => 'This Quarter (Q' . ceil(now()->month / 3) . ' ' . now()->year . ')',
                        'last_quarter' => 'Last Quarter',
                        'this_year' => 'This Year (' . now()->year . ')',
                        'last_year' => 'Last Year (' . now()->subYear()->year . ')',
                    ])
                    ->default('this_month')
                    ->selectablePlaceholder(false)
                    ->live(),

                Select::make('period')
                    ->label('Specific Month')
                    ->options($this->getPeriodOptions())
                    ->placeholder('Or select month...')
                    ->visible(fn ($get) => empty($get('time_range')) || $get('time_range') === 'custom'),
            ]);
    }

    protected function getPeriodOptions(): array
    {
        $options = [];
        
        for ($i = 0; $i < 24; $i++) {
            $date = now()->subMonths($i);
            $value = $date->format('Y-m');
            $label = $date->format('F Y');
            $options[$value] = $label;
        }
        
        return $options;
    }

    public function getWidgets(): array
    {
        return [
            CommissionStatsWidget::class,
            EarningsTrendWidget::class,
            TopEarnersWidget::class,
            BonusAwardsWidget::class, // Right after TopEarners
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
