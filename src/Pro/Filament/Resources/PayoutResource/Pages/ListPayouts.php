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
                    $period = $data['period'];
                    $payout = Payout::generate($period);
                    
                    if (!$payout) {
                        $payableCount = \SalesCommission\Models\CommissionEarning::where('period', $period)
                            ->where('status', 'payable')
                            ->whereNull('payout_id')
                            ->count();
                        
                        if ($payableCount === 0) {
                            Notification::make()
                                ->warning()
                                ->title('No Payable Earnings')
                                ->body("No payable earnings found for {$period}. Mark earnings as 'payable' first.")
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Hold Period')
                                ->body("Found {$payableCount} payable earnings but they may be blocked by hold period.")
                                ->send();
                        }
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
