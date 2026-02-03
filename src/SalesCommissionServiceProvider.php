<?php

namespace SalesCommission;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use SalesCommission\Exceptions\SalesCommissionExceptionHandler;
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

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sales-commission');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/sales-commission'),
        ], 'sales-commission-views');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->commands([
                Commands\ProcessPayouts::class,
                Commands\RecalculateTiers::class,
            ]);
        }

        // Register Blade directives
        $this->registerBladeDirectives();

        // Load API routes
        $this->bootApiRoutes();

        // Register centralized exception handlers for all commission API routes
        SalesCommissionExceptionHandler::register();
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // @pro directive always returns true (all features are now free)
        Blade::if('pro', function () {
            return true;
        });
    }

    /**
     * Boot API routes.
     */
    protected function bootApiRoutes(): void
    {
        if (file_exists(__DIR__ . '/Pro/routes/api.php')) {
            $this->loadRoutesFrom(__DIR__ . '/Pro/routes/api.php');
        }
    }
}


