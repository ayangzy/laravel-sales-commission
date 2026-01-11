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
                    
                    // First, reset any orphaned earnings (payable but payout_id points to deleted payout)
                    $orphanedCount = $this->resetOrphanedEarnings($period);
                    
                    $payout = Payout::generate($period);
                    
                    if (!$payout || $payout->total_earnings_count === 0) {
                        $payableCount = \SalesCommission\Models\CommissionEarning::where('period', $period)
                            ->where('status', 'payable')
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
                                ->title('Threshold Not Met')
                                ->body("Found {$payableCount} payable earnings but minimum threshold not met.")
                                ->send();
                        }
                    } else {
                        $message = "Created payout with {$payout->total_earnings_count} earnings totaling \${$payout->total_amount}";
                        if ($orphanedCount > 0) {
                            $message .= " (reset {$orphanedCount} orphaned earnings)";
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Payout Generated')
                            ->body($message)
                            ->send();
                    }
                }),

            Actions\Action::make('resetOrphaned')
                ->label('Reset Orphaned')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Reset Orphaned Earnings')
                ->modalDescription('This will clear payout_id from payable earnings where the payout no longer exists. Continue?')
                ->action(function () {
                    $count = $this->resetOrphanedEarnings();
                    
                    if ($count > 0) {
                        Notification::make()
                            ->success()
                            ->title('Earnings Reset')
                            ->body("Reset {$count} orphaned earnings. They can now be included in new payouts.")
                            ->send();
                    } else {
                        Notification::make()
                            ->info()
                            ->title('No Orphaned Earnings')
                            ->body('All payable earnings are properly linked to existing payouts.')
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Reset orphaned earnings where payout_id points to a deleted/non-existent payout.
     */
    protected function resetOrphanedEarnings(?string $period = null): int
    {
        $query = \SalesCommission\Models\CommissionEarning::where('status', 'payable')
            ->whereNotNull('payout_id')
            ->whereDoesntHave('payout');
        
        if ($period) {
            $query->where('period', $period);
        }
        
        $count = $query->count();
        $query->update(['payout_id' => null]);
        
        return $count;
    }
}

