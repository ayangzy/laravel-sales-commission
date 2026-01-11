<?php

namespace SalesCommission\Pro\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use SalesCommission\Models\Payout;
use SalesCommission\Pro\Filament\Resources\PayoutResource\Pages;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Commissions';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Details')
                    ->schema([
                        Forms\Components\TextInput::make('period')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->disabled()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('total_earnings_count')
                            ->label('Earnings Count')
                            ->disabled(),
                        Forms\Components\TextInput::make('status')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency ?? config('sales-commission.currency', 'USD'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_earnings_count')
                    ->label('Earnings'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'approved' => 'info',
                        'processing' => 'primary',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Payout $record) => $record->status === Payout::STATUS_PENDING_APPROVAL)
                    ->action(function (Payout $record) {
                        $record->approve(auth()->id());
                        Notification::make()
                            ->success()
                            ->title('Payout Approved')
                            ->send();
                    }),
                Tables\Actions\Action::make('process')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn (Payout $record) => $record->status === Payout::STATUS_APPROVED)
                    ->action(function (Payout $record) {
                        $record->markAsPaid(['processed_by' => auth()->id()]);
                        Notification::make()
                            ->success()
                            ->title('Payout Processed')
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
        ];
    }
}
