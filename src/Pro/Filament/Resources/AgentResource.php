<?php

namespace SalesCommission\Pro\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Pro\Filament\Resources\AgentResource\Pages;

class AgentResource extends Resource
{
    protected static ?string $model = CommissionEarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Commissions';

    protected static ?string $navigationLabel = 'Agents';

    protected static ?string $modelLabel = 'Agent';

    protected static ?string $pluralModelLabel = 'Agents';

    protected static ?int $navigationSort = 6;

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getAgentsQuery())
            ->columns([
                Tables\Columns\TextColumn::make('agent_id')
                    ->label('Agent ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('agent_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->money('USD')
                    ->sortable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('current_tier')
                    ->label('Current Tier')
                    ->getStateUsing(function ($record) {
                        $plan = CommissionPlan::find($record->plan_id);
                        if (!$plan) return 'No Plan';
                        
                        $tier = $plan->findTierForAmount((float) $record->total_sales);
                        return $tier?->name ?? 'No Tier';
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Bronze' => 'warning',
                        'Silver' => 'gray',
                        'Gold' => 'success',
                        'Platinum' => 'info',
                        'Diamond' => 'primary',
                        'No Tier', 'No Plan' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('current_rate')
                    ->label('Rate')
                    ->getStateUsing(function ($record) {
                        $plan = CommissionPlan::find($record->plan_id);
                        if (!$plan) return '—';
                        
                        $tier = $plan->findTierForAmount((float) $record->total_sales);
                        return $tier ? number_format($tier->rate, 1) . '%' : '—';
                    })
                    ->color('success'),

                Tables\Columns\TextColumn::make('next_tier')
                    ->label('Next Tier')
                    ->getStateUsing(function ($record) {
                        $plan = CommissionPlan::find($record->plan_id);
                        if (!$plan) return '—';
                        
                        $nextTier = $plan->tiers()
                            ->where('min_threshold', '>', (float) $record->total_sales)
                            ->orderBy('min_threshold')
                            ->first();
                        
                        if (!$nextTier) return 'Max Tier ✓';
                        
                        $remaining = $nextTier->min_threshold - (float) $record->total_sales;
                        return $nextTier->name . ' ($' . number_format($remaining, 0) . ' to go)';
                    })
                    ->color('warning'),

                Tables\Columns\TextColumn::make('last_earning_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('total_earned', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => CommissionPlan::pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->having('plan_id', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('viewEarnings')
                    ->label('Earnings')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn ($record) => CommissionEarningResource::getUrl('index', [
                        'tableFilters[agent_id][value]' => $record->agent_id,
                    ])),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No Agents Found')
            ->emptyStateDescription('Agents will appear here once they have commission earnings.')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected static function getAgentsQuery(): Builder
    {
        return CommissionEarning::query()
            ->selectRaw('
                agent_id,
                agent_type,
                plan_id,
                (SELECT name FROM commission_plans WHERE id = commission_earnings.plan_id LIMIT 1) as plan_name,
                SUM(base_amount) as total_sales,
                SUM(CASE WHEN status != "clawed_back" THEN commission_amount ELSE 0 END) as total_earned,
                COUNT(*) as transactions_count,
                MAX(earned_at) as last_earning_at
            ')
            ->groupBy('agent_id', 'agent_type', 'plan_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgents::route('/'),
        ];
    }
}

