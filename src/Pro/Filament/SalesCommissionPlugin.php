<?php

namespace SalesCommission\Pro\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use SalesCommission\Pro\LicenseManager;

class SalesCommissionPlugin implements Plugin
{
    public function getId(): string
    {
        return 'sales-commission';
    }

    public function register(Panel $panel): void
    {
        if (!app(LicenseManager::class)->isValid()) {
            return;
        }

        $panel
            ->resources([
                Resources\CommissionPlanResource::class,
                Resources\CommissionEarningResource::class,
                Resources\PayoutResource::class,
                Resources\ClawbackResource::class,
            ])
            ->pages([
                Pages\CommissionDashboard::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
