<?php

namespace SalesCommission\Pro\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use SalesCommission\Models\CommissionClawback;
use SalesCommission\Pro\Filament\Resources\ClawbackResource\Pages;

class ClawbackResource extends Resource
{
    protected static ?string $model = CommissionClawback::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationGroup = 'Commissions';

    protected static ?string $navigationLabel = 'Clawbacks';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('earning_id')
                    ->label('Earning')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(config('sales-commission.currency', 'USD'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'refund' => 'warning',
                        'chargeback' => 'danger',
                        'cancellation' => 'gray',
                        'manual' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'refund' => 'Refund',
                        'chargeback' => 'Chargeback',
                        'cancellation' => 'Cancellation',
                        'manual' => 'Manual',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClawbacks::route('/'),
        ];
    }
}
