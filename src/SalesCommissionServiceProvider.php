<?php

namespace SalesCommission;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use SalesCommission\Exceptions\SalesCommissionExceptionHandler;
use SalesCommission\Services\CommissionCalculator;
use SalesCommission\Pro\LicenseManager;

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

        $this->app->singleton(LicenseManager::class, function ($app) {
            return new LicenseManager();
        });

        $this->app->alias(LicenseManager::class, 'commission.license');
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

        // Load views for Pro features
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sales-commission');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/sales-commission'),
        ], 'sales-commission-views');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->commands([
                Commands\ProcessPayouts::class,
                Commands\RecalculateTiers::class,
                Commands\GenerateLicenseKey::class,
            ]);
        }

        // Register Blade directives for Pro features
        $this->registerBladeDirectives();

        // Load Pro routes if licensed
        $this->bootProRoutes();

        // Register centralized exception handlers for all commission API routes
        SalesCommissionExceptionHandler::register();
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::if('pro', function () {
            return app(LicenseManager::class)->isValid();
        });
    }

    /**
     * Boot Pro routes if license is valid.
     */
    protected function bootProRoutes(): void
    {
        // Only load routes, Filament resources are handled by the plugin
        if (file_exists(__DIR__ . '/Pro/routes/api.php')) {
            if (app(LicenseManager::class)->isValid()) {
                $this->loadRoutesFrom(__DIR__ . '/Pro/routes/api.php');
            } else {
                // Register fallback route for unlicensed access
                $this->registerFallbackApiRoutes();
            }
        }
    }

    /**
     * Register fallback API routes when license is invalid.
     */
    protected function registerFallbackApiRoutes(): void
    {
        $this->app['router']->prefix('api/commissions')
            ->middleware(['api'])
            ->group(function ($router) {
                $router->any('{any?}', function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Commission API is not available. Please ensure you have a valid Pro license key configured.',
                        'error_code' => 'LICENSE_REQUIRED',
                        'help' => 'Add your license key to .env: SALES_COMMISSION_PRO_KEY=SCPRO-XXXX-XXXX-XXXX-XXXX',
                    ], 403);
                })->where('any', '.*');
            });
    }
}


