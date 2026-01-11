<?php

namespace SalesCommission\Pro\Filament\Pages;

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

    protected static ?string $title = 'Commission Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationGroup = 'Commissions';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('period')
                    ->label('Period')
                    ->options($this->getPeriodOptions())
                    ->default(now()->format('Y-m'))
                    ->selectablePlaceholder(false),
            ]);
    }

    protected function getPeriodOptions(): array
    {
        $options = [];
        
        for ($i = 0; $i < 12; $i++) {
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
            TierDistributionWidget::class,
            RecentActivityWidget::class,
            BonusAwardsWidget::class,
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

