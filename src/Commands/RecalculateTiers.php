<?php

namespace SalesCommission\Commands;

use Illuminate\Console\Command;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Services\TierEvaluator;

class RecalculateTiers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'commission:recalculate-tiers 
                            {--plan= : Specific plan ID or slug to recalculate}
                            {--all : Recalculate for all plans}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate commission tiers for all agents';

    /**
     * Execute the console command.
     */
    public function handle(TierEvaluator $tierEvaluator): int
    {
        $planOption = $this->option('plan');
        $all = $this->option('all');

        if (!$planOption && !$all) {
            $this->error('Please specify --plan=<plan_id|slug> or --all');
            return self::FAILURE;
        }

        $plans = collect();

        if ($all) {
            $plans = CommissionPlan::active()->get();
            $this->info("Recalculating tiers for {$plans->count()} active plan(s)...");
        } else {
            $plan = CommissionPlan::where('id', $planOption)
                ->orWhere('slug', $planOption)
                ->first();

            if (!$plan) {
                $this->error("Plan not found: {$planOption}");
                return self::FAILURE;
            }

            $plans->push($plan);
            $this->info("Recalculating tiers for plan: {$plan->name}");
        }

        $totalAgents = 0;

        foreach ($plans as $plan) {
            $count = $tierEvaluator->recalculateForPlan($plan);
            $totalAgents += $count;
            $this->info("  - {$plan->name}: {$count} agent(s) updated");
        }

        $this->info("Total: {$totalAgents} agent(s) recalculated");

        return self::SUCCESS;
    }
}
