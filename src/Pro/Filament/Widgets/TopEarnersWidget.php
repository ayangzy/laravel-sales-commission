<?php

namespace SalesCommission\Pro\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Support\CurrencyHelper;

class TopEarnersWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Earners This Month';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected function getTableQuery(): Builder
    {
        $currentPeriod = now()->format('Y-m');

        return CommissionEarning::query()
            ->where('period', $currentPeriod)
            ->whereNotIn('status', [CommissionEarning::STATUS_CLAWED_BACK])
            ->selectRaw('agent_id, agent_type, SUM(commission_amount) as total_earned, COUNT(*) as sales_count')
            ->groupBy('agent_id', 'agent_type')
            ->orderByDesc('total_earned')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('rank')
                ->label('#')
                ->rowIndex(),

            Tables\Columns\TextColumn::make('agent_id')
                ->label('Agent')
                ->formatStateUsing(fn ($state) => "Agent #{$state}")
                ->searchable(),

            Tables\Columns\TextColumn::make('total_earned')
                ->label('Earned')
                ->formatStateUsing(fn ($state) => CurrencyHelper::format((float) $state))
                ->sortable()
                ->color('success')
                ->weight('bold'),

            Tables\Columns\TextColumn::make('sales_count')
                ->label('Sales')
                ->badge()
                ->color('primary'),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    public function getTableRecordKey($record): string
    {
        return $record->agent_type . ':' . $record->agent_id;
    }
}
