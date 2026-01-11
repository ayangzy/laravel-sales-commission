<?php

namespace SalesCommission\Pro\Filament\Resources\PayoutResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use SalesCommission\Models\Payout;
use SalesCommission\Pro\Filament\Resources\PayoutResource;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generatePayout')
                ->label('Generate Payout')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('period')
                        ->label('Period')
                        ->placeholder('YYYY-MM')
                        ->default(now()->format('Y-m'))
                        ->required()
                        ->helperText('Enter the period for payout generation (e.g., 2026-01)'),
                ])
                ->action(function (array $data) {
                    $payout = Payout::generate($data['period']);
                    
                    if ($payout->total_earnings_count === 0) {
                        Notification::make()
                            ->warning()
                            ->title('No Earnings Found')
                            ->body('No payable earnings found for period ' . $data['period'])
                            ->send();
                    } else {
                        Notification::make()
                            ->success()
                            ->title('Payout Generated')
                            ->body("Created payout with {$payout->total_earnings_count} earnings totaling \${$payout->total_amount}")
                            ->send();
                    }
                }),
        ];
    }
}

