<?php

namespace SalesCommission\Pro\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Pro\Filament\Resources\CommissionEarningResource\Pages;

class CommissionEarningResource extends Resource
{
    protected static ?string $model = CommissionEarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Commissions';

    protected static ?string $navigationLabel = 'Earnings';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Earning Details')
                    ->schema([
                        Forms\Components\TextInput::make('base_amount')
                            ->label('Sale Amount')
                            ->disabled()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('commission_amount')
                            ->label('Commission')
                            ->disabled()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('rate')
                            ->label('Rate')
                            ->disabled()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('status')
                            ->disabled(),
                        Forms\Components\TextInput::make('period')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent_id')
                    ->label('Agent ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Sale Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'payable' => 'success',
                        'paid' => 'info',
                        'clawed_back' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('period')
                    ->sortable(),
                Tables\Columns\TextColumn::make('earned_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('earned_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'payable' => 'Payable',
                        'paid' => 'Paid',
                        'clawed_back' => 'Clawed Back',
                    ]),
                Tables\Filters\Filter::make('period')
                    ->form([
                        Forms\Components\TextInput::make('period')
                            ->placeholder('YYYY-MM'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['period'],
                            fn ($q) => $q->where('period', $data['period'])
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('markPayable')
                    ->label('Mark Payable')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Payable')
                    ->modalDescription('This will mark the earning as ready for payout. Continue?')
                    ->visible(fn (CommissionEarning $record) => $record->status === CommissionEarning::STATUS_PENDING)
                    ->action(function (CommissionEarning $record) {
                        $record->update(['status' => CommissionEarning::STATUS_PAYABLE]);
                    })
                    ->successNotificationTitle('Earning marked as payable'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markPayable')
                    ->label('Mark as Payable')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $records->each(fn ($record) => $record->update(['status' => CommissionEarning::STATUS_PAYABLE]));
                    })
                    ->successNotificationTitle('Earnings marked as payable'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionEarnings::route('/'),
        ];
    }
}
