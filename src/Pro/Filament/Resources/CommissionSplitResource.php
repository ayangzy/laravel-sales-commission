<?php

namespace SalesCommission\Pro\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use SalesCommission\Models\CommissionSplit;
use SalesCommission\Pro\Filament\Resources\CommissionSplitResource\Pages;

class CommissionSplitResource extends Resource
{
    protected static ?string $model = CommissionSplit::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Commissions';

    protected static ?string $navigationLabel = 'Team Splits';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Split Details')
                    ->schema([
                        Forms\Components\TextInput::make('earning_id')
                            ->label('Earning ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('agent_id')
                            ->label('Agent ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('split_percentage')
                            ->label('Split %')
                            ->disabled()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('split_amount')
                            ->label('Amount')
                            ->disabled()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('role')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('earning.id')
                    ->label('Earning')
                    ->limit(8)
                    ->searchable()
                    ->copyable()
                    ->tooltip(fn ($record) => $record->earning_id),

                Tables\Columns\TextColumn::make('agent_id')
                    ->label('Agent')
                    ->formatStateUsing(fn ($state) => "Agent #{$state}")
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'primary' => 'success',
                        'support' => 'info',
                        'manager' => 'warning',
                        'override' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('split_percentage')
                    ->label('Split %')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('split_amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('earning.base_amount')
                    ->label('Sale Total')
                    ->money('USD')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'primary' => 'Primary',
                        'support' => 'Support',
                        'manager' => 'Manager',
                        'override' => 'Override',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No Team Splits')
            ->emptyStateDescription('Team splits are created when commissions are split between multiple agents.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionSplits::route('/'),
        ];
    }
}
