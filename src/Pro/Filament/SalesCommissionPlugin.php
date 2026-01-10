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
        // Check license key directly from config to avoid container issues
        $licenseKey = config('sales-commission.pro.license_key');
        
        if (empty($licenseKey) || !$this->isValidLicenseKey($licenseKey)) {
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

    /**
     * Simple license key validation.
     */
    protected function isValidLicenseKey(?string $key): bool
    {
        if (empty($key)) {
            return false;
        }

        // License format: SCPRO-XXXX-XXXX-XXXX-XXXX
        if (!str_starts_with($key, 'SCPRO-')) {
            return false;
        }

        if (strlen($key) < 24) {
            return false;
        }

        return true;
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

