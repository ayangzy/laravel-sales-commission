<?php

namespace SalesCommission;

use Illuminate\Support\ServiceProvider;
use SalesCommission\Services\CommissionCalculator;

class SalesCommissionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sales-commission.php',
            'sales-commission'
        );

        $this->app->singleton('commission', function ($app) {
            return new CommissionCalculator();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/sales-commission.php' => config_path('sales-commission.php'),
        ], 'sales-commission-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'sales-commission-migrations');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->commands([
                Commands\ProcessPayouts::class,
                Commands\RecalculateTiers::class,
            ]);
        }
    }
}
