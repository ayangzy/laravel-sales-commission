<?php

namespace SalesCommission\Pro\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use SalesCommission\Models\CommissionEarning;

class BonusAwardsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Bonus Awards';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 1;

    protected function getTableQuery(): Builder
    {
        // Get earnings that came from bonus rules or tier upgrades with bonus
        return CommissionEarning::query()
            ->where(function ($query) {
                // Earnings from bonus_threshold rules
                $query->whereHas('rule', function ($q) {
                    $q->where('type', 'bonus_threshold');
                })
                // Or earnings from tiers with bonus amounts
                ->orWhereHas('tier', function ($q) {
                    $q->where('bonus_amount', '>', 0);
                });
            })
            ->latest('earned_at')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('agent_id')
                ->label('Agent')
                ->formatStateUsing(fn ($state) => "Agent #{$state}"),

            Tables\Columns\TextColumn::make('commission_amount')
                ->label('Bonus')
                ->money('USD')
                ->color('success')
                ->weight('bold'),

            Tables\Columns\TextColumn::make('source')
                ->label('Source')
                ->getStateUsing(function ($record) {
                    if ($record->tier && $record->tier->bonus_amount > 0) {
                        return 'Tier: ' . $record->tier->name;
                    }
                    if ($record->rule) {
                        return 'Rule: ' . ($record->rule->name ?? $record->rule->type);
                    }
                    return 'Bonus';
                })
                ->badge()
                ->color('warning'),

            Tables\Columns\TextColumn::make('earned_at')
                ->label('Awarded')
                ->since(),
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
}
