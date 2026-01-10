<?php

namespace SalesCommission\Pro\Filament\Resources\CommissionPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RulesRelationManager extends RelationManager
{
    protected static string $relationship = 'rules';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                        'tiered' => 'Tiered (use tier rate)',
                        'bonus_threshold' => 'Bonus Threshold',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->required()
                    ->helperText('Percentage rate or fixed amount'),
                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->default(1)
                    ->helperText('Lower numbers run first'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'success',
                        'fixed' => 'info',
                        'tiered' => 'warning',
                        'bonus_threshold' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('priority'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('priority')
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
