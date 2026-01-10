<?php

namespace SalesCommission\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use SalesCommission\SalesCommissionServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SalesCommissionServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use SQLite in memory for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Run migrations
        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000001_create_commission_plans_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000002_create_commission_tiers_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000003_create_commission_rules_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000004_create_payouts_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000005_create_commission_earnings_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000006_create_commission_splits_table.php';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/2024_01_01_000007_create_commission_clawbacks_table.php';
        $migration->up();
    }
}
