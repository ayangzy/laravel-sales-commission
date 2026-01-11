<?php

namespace SalesCommission\Pro\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionTier;

class TierDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Agent Tier Distribution';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        // Get default plan tiers
        $defaultPlan = CommissionPlan::where('is_default', true)->first();
        
        if (!$defaultPlan) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#9CA3AF']]],
                'labels' => ['No Plan Configured'],
            ];
        }

        $tiers = $defaultPlan->tiers()->orderBy('min_threshold')->get();
        
        if ($tiers->isEmpty()) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#9CA3AF']]],
                'labels' => ['No Tiers Configured'],
            ];
        }

        $labels = [];
        $counts = [];
        $colors = [
            '#CD7F32', // Bronze
            '#C0C0C0', // Silver
            '#FFD700', // Gold
            '#B9F2FF', // Platinum
            '#E5E4E2', // Diamond
            '#4F46E5', // Other
        ];

        // Get unique agents and their total earnings
        $agentTotals = CommissionEarning::where('plan_id', $defaultPlan->id)
            ->whereNotIn('status', [CommissionEarning::STATUS_CLAWED_BACK])
            ->selectRaw('agent_id, agent_type, SUM(commission_amount) as total')
            ->groupBy('agent_id', 'agent_type')
            ->get();

        foreach ($tiers as $index => $tier) {
            $labels[] = $tier->name;
            
            $count = $agentTotals->filter(function ($agent) use ($tier, $tiers, $index) {
                $total = $agent->total;
                $min = $tier->min_threshold;
                $max = $tier->max_threshold;
                
                // Check if agent falls in this tier
                if ($max === null) {
                    return $total >= $min;
                }
                
                return $total >= $min && $total <= $max;
            })->count();
            
            $counts[] = $count;
        }

        // If all zeros, show placeholder
        if (array_sum($counts) === 0) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#9CA3AF']]],
                'labels' => ['No Agents Yet'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $counts,
                    'backgroundColor' => array_slice($colors, 0, count($labels)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
