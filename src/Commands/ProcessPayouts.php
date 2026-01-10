<?php

namespace SalesCommission\Commands;

use Illuminate\Console\Command;
use SalesCommission\Services\PayoutService;

class ProcessPayouts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'commission:process-payouts 
                            {--period= : The period to process (e.g., 2024-01)}
                            {--dry-run : Show what would be processed without actually processing}';

    /**
     * The console command description.
     */
    protected $description = 'Process pending commission payouts';

    /**
     * Execute the console command.
     */
    public function handle(PayoutService $payoutService): int
    {
        $period = $this->option('period') ?? $payoutService->getCurrentPeriod();
        $dryRun = $this->option('dry-run');

        $this->info("Processing payouts for period: {$period}");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made');
        }

        // Generate payout if needed
        $this->info('Generating payout...');

        if (!$dryRun) {
            $payout = $payoutService->generateForPeriod($period);

            $this->table(
                ['ID', 'Period', 'Total Amount', 'Earnings Count', 'Status'],
                [[
                    $payout->id,
                    $payout->period,
                    '$' . number_format($payout->total_amount, 2),
                    $payout->total_earnings_count,
                    $payout->status,
                ]]
            );

            if ($payout->total_earnings_count === 0) {
                $this->warn('No payable earnings found for this period.');
                return self::SUCCESS;
            }

            // Process approved payouts
            if ($payout->status === 'approved') {
                $this->info('Processing approved payout...');
                $processed = $payoutService->processApprovedPayouts();
                $this->info("Processed {$processed->count()} payout(s).");
            } else {
                $this->info("Payout created with status: {$payout->status}");
                $this->info('Approve the payout to process it.');
            }
        } else {
            $stats = $payoutService->getPayoutStats($period);
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Paid', '$' . number_format($stats['total_paid'], 2)],
                    ['Total Pending', '$' . number_format($stats['total_pending'], 2)],
                    ['Payout Count', $stats['payout_count']],
                ]
            );
        }

        return self::SUCCESS;
    }
}
