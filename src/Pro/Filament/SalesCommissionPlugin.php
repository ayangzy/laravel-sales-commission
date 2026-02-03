<?php

namespace SalesCommission\Pro\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class SalesCommissionPlugin implements Plugin
{
    public function getId(): string
    {
        return 'sales-commission';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                Resources\CommissionPlanResource::class,
                Resources\CommissionEarningResource::class,
                Resources\PayoutResource::class,
                Resources\ClawbackResource::class,
                Resources\CommissionSplitResource::class,
                Resources\AgentResource::class,
            ])
            ->pages([
                Pages\CommissionDashboard::class,
            ])
            ->widgets([
                Widgets\CommissionStatsWidget::class,
                Widgets\EarningsTrendWidget::class,
                Widgets\TopEarnersWidget::class,
                Widgets\TierDistributionWidget::class,
                Widgets\RecentActivityWidget::class,
                Widgets\BonusAwardsWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new static();
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament('sales-commission');

        return $plugin;
    }
}
