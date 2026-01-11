<?php

namespace SalesCommission\Pro\Filament\Resources\CommissionPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use SalesCommission\Support\CurrencyHelper;

class TiersRelationManager extends RelationManager
{
    protected static string $relationship = 'tiers';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('min_threshold')
                    ->label('Min Threshold')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('max_threshold')
                    ->label('Max Threshold')
                    ->numeric()
                    ->nullable()
                    ->helperText('Leave empty for unlimited'),
                Forms\Components\TextInput::make('rate')
                    ->label('Commission Rate (%)')
                    ->numeric()
                    ->required()
                    ->suffix('%'),
                Forms\Components\TextInput::make('bonus_amount')
                    ->label('Tier Bonus')
                    ->numeric()
                    ->nullable()
                    ->prefix(CurrencyHelper::getConfiguredSymbol())
                    ->helperText('One-time bonus for reaching this tier'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('min_threshold')
                    ->label('Min')
                    ->formatStateUsing(fn ($state) => CurrencyHelper::format((float) $state)),
                Tables\Columns\TextColumn::make('max_threshold')
                    ->label('Max')
                    ->formatStateUsing(fn ($state) => $state ? CurrencyHelper::format((float) $state) : null)
                    ->placeholder('∞'),
                Tables\Columns\TextColumn::make('rate')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('bonus_amount')
                    ->label('Bonus')
                    ->formatStateUsing(fn ($state) => $state ? CurrencyHelper::format((float) $state) : null)
                    ->placeholder('-'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
