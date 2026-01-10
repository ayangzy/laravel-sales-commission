<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agent Model
    |--------------------------------------------------------------------------
    |
    | The model that represents commission agents (typically your User model).
    | This model should use the HasCommissions trait.
    |
    */
    'models' => [
        'agent' => 'App\\Models\\User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Commission Plan
    |--------------------------------------------------------------------------
    |
    | The default commission plan to use when none is specified.
    | This can be a plan ID or slug.
    |
    */
    'default_plan' => null,

    /*
    |--------------------------------------------------------------------------
    | Clawback Settings
    |--------------------------------------------------------------------------
    |
    | Configure how commission clawbacks are handled when sales are refunded
    | or cancelled.
    |
    */
    'clawback' => [
        // Enable or disable clawback functionality
        'enabled' => true,

        // Number of days after which commissions cannot be clawed back
        // Set to null for no grace period limit
        'grace_period_days' => 30,

        // Automatically clawback when a sale is refunded
        'auto_on_refund' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payout Settings
    |--------------------------------------------------------------------------
    |
    | Configure payout processing behavior.
    |
    */
    'payout' => [
        // Minimum commission balance required to generate a payout
        'min_threshold' => 50.00,

        // Auto-approve payouts or require manual approval
        'auto_approve' => false,

        // Default payout schedule: weekly, bi-weekly, monthly
        'schedule' => 'monthly',

        // Number of days after earning before commission is eligible for payout
        'hold_period_days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default currency for commission calculations.
    |
    */
    'currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the database table names used by the package.
    |
    */
    'tables' => [
        'commission_plans' => 'commission_plans',
        'commission_tiers' => 'commission_tiers',
        'commission_rules' => 'commission_rules',
        'commission_earnings' => 'commission_earnings',
        'commission_splits' => 'commission_splits',
        'commission_clawbacks' => 'commission_clawbacks',
        'payouts' => 'payouts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Broadcasting
    |--------------------------------------------------------------------------
    |
    | Configure which events should be dispatched.
    |
    */
    'events' => [
        'commission_earned' => true,
        'commission_clawed_back' => true,
        'payout_processed' => true,
        'tier_achieved' => true,
    ],
];
