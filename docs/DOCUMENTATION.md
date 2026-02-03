# Laravel Sales Commission - Complete Documentation

**Version 2.0.0** | **Author: Ayangzy** | **February 2026**

---

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Core Concepts](#core-concepts)
5. [Models Reference](#models-reference)
6. [Services Reference](#services-reference)
7. [Usage Examples](#usage-examples)
8. [Events](#events)
9. [Artisan Commands](#artisan-commands)
10. [Testing](#testing)
11. [Filament Admin Panel](#filament-admin-panel)
12. [REST API Reference](#rest-api-reference)
13. [API Reference](#api-reference)
14. [Troubleshooting](#troubleshooting)

---

## Introduction

Laravel Sales Commission is a comprehensive, enterprise-grade commission calculation and management package for Laravel SaaS applications. It provides everything you need to build complex commission structures for sales teams, affiliate programs, and partner networks.

### Key Features

- **Flexible Commission Plans** - Create multiple plans with different rules
- **Multi-Tier Structures** - Bronze, Silver, Gold tier progression based on sales volume
- **Team Split Commissions** - Divide commissions among team members with role-based splits
- **Clawback Support** - Handle refunds and chargebacks gracefully with configurable grace periods
- **Payout Management** - Generate, approve, and process payouts with hold periods
- **Performance Bonuses** - Reward milestone achievements
- **Extensible Rule Engine** - Create custom commission rules
- **Period-Based Tracking** - Weekly, monthly, quarterly reporting

### Requirements

- PHP 8.2 or higher
- Laravel 10.x or 11.x
- SQLite, MySQL, PostgreSQL, or SQL Server

---

## Installation

### Step 1: Install via Composer

```bash
composer require ayangzy/laravel-sales-commission
```

### Step 2: Publish Configuration & Migrations

```bash
php artisan vendor:publish --tag="sales-commission-config"
php artisan vendor:publish --tag="sales-commission-migrations"
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

This creates the following tables:

- `commission_plans` - Commission plan definitions
- `commission_tiers` - Tier thresholds and rates
- `commission_rules` - Commission calculation rules
- `commission_earnings` - Earned commission records
- `commission_splits` - Team split records
- `commission_clawbacks` - Clawback records
- `payouts` - Payout batches

---

## Configuration

The configuration file is located at `config/sales-commission.php`:

### Agent Model

```php
'models' => [
    'agent' => 'App\\Models\\User',
],
```

Specifies which model represents commission agents (typically your User model).

### Default Commission Plan

```php
'default_plan' => null,
```

The default plan to use when none is specified. Can be a plan ID or slug.

### Clawback Settings

```php
'clawback' => [
    'enabled' => true,
    'grace_period_days' => 30,
    'auto_on_refund' => true,
],
```

| Option              | Description                                                           |
| ------------------- | --------------------------------------------------------------------- |
| `enabled`           | Enable/disable clawback functionality                                 |
| `grace_period_days` | Days after which commissions cannot be clawed back (null = unlimited) |
| `auto_on_refund`    | Automatically clawback when a sale is refunded                        |

### Payout Settings

```php
'payout' => [
    'min_threshold' => 50.00,
    'auto_approve' => false,
    'schedule' => 'monthly',
    'hold_period_days' => 14,
],
```

| Option             | Description                                       |
| ------------------ | ------------------------------------------------- |
| `min_threshold`    | Minimum balance required to generate a payout     |
| `auto_approve`     | Auto-approve payouts or require manual approval   |
| `schedule`         | Payout schedule: `weekly`, `bi-weekly`, `monthly` |
| `hold_period_days` | Days after earning before eligible for payout     |

### Currency

```php
'currency' => 'USD',
```

### Table Names

Customize database table names if needed:

```php
'tables' => [
    'commission_plans' => 'commission_plans',
    'commission_tiers' => 'commission_tiers',
    'commission_rules' => 'commission_rules',
    'commission_earnings' => 'commission_earnings',
    'commission_splits' => 'commission_splits',
    'commission_clawbacks' => 'commission_clawbacks',
    'payouts' => 'payouts',
],
```

### Event Broadcasting

```php
'events' => [
    'commission_earned' => true,
    'commission_clawed_back' => true,
    'payout_processed' => true,
    'tier_achieved' => true,
],
```

---

## Core Concepts

### Commission Plans

A Commission Plan is a container for tiers and rules. You can have multiple plans for different agent types:

- **Standard Plan** - For regular sales reps
- **Enterprise Plan** - For key account managers
- **Partner Plan** - For affiliate partners

### Commission Tiers

Tiers define progressive commission rates based on cumulative sales:

| Tier   | Sales Range       | Rate |
| ------ | ----------------- | ---- |
| Bronze | $0 - $10,000      | 5%   |
| Silver | $10,001 - $50,000 | 7.5% |
| Gold   | $50,001+          | 10%  |

### Commission Rules

Rules define how commissions are calculated:

| Type              | Description                     |
| ----------------- | ------------------------------- |
| `percentage`      | Percentage of sale amount       |
| `fixed`           | Fixed dollar amount per sale    |
| `tiered`          | Rate based on current tier      |
| `bonus_threshold` | Bonus when reaching a threshold |

### Earnings

Earnings are individual commission records linked to:

- The agent who earned it
- The commissionable item (order, subscription, etc.)
- The plan, tier, and rules applied

### Clawbacks

When a sale is refunded or cancelled, the corresponding commission can be clawed back (reversed).

### Payouts

Payouts are batches of earnings processed together for payment.

---

## Models Reference

### CommissionPlan

```php
use SalesCommission\Models\CommissionPlan;

$plan = CommissionPlan::create([
    'name' => 'Standard Sales Plan',
    'slug' => 'standard',
    'description' => 'Default commission plan for sales team',
    'is_active' => true,
    'is_default' => true,
]);
```

**Relationships:**

- `tiers()` - HasMany CommissionTier
- `rules()` - HasMany CommissionRule

**Scopes:**

- `active()` - Only active plans

**Methods:**

- `findTierForAmount(float $amount)` - Find the tier for a given sales amount

### CommissionTier

```php
$plan->tiers()->create([
    'name' => 'Gold',
    'min_threshold' => 50001,
    'max_threshold' => null, // No upper limit
    'rate' => 10,
    'bonus_amount' => 500, // Optional bonus for reaching tier
]);
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `plan_id` | ULID | Parent plan |
| `name` | string | Tier name |
| `min_threshold` | decimal | Minimum sales to qualify |
| `max_threshold` | decimal | Maximum sales (null = unlimited) |
| `rate` | decimal | Commission rate percentage |
| `bonus_amount` | decimal | One-time bonus for reaching tier |

### CommissionRule

```php
$plan->rules()->create([
    'name' => 'Base Commission',
    'type' => 'percentage',
    'value' => 10,
    'conditions' => [
        'product_category' => 'premium',
    ],
    'priority' => 1,
    'is_active' => true,
]);
```

**Rule Types:**

- `percentage` - Value is the percentage rate
- `fixed` - Value is the fixed amount
- `tiered` - Uses tier rate
- `bonus_threshold` - Value is the bonus amount

**Conditions:**
Rules can have conditional logic:

```php
'conditions' => [
    'product_category' => 'premium',
    'order_total' => ['>=', 1000],
    'customer_type' => ['in', ['enterprise', 'business']],
]
```

### CommissionEarning

```php
use SalesCommission\Models\CommissionEarning;

// Query earnings
$earnings = CommissionEarning::forAgent($user)
    ->forPeriod('2026-01')
    ->payable()
    ->get();

// Get totals
$total = CommissionEarning::forAgent($user)->sum('commission_amount');
```

**Status Values:**
| Status | Description |
|--------|-------------|
| `pending` | Awaiting hold period |
| `payable` | Eligible for payout |
| `paid` | Already paid out |
| `clawed_back` | Fully reversed |
| `cancelled` | Manually cancelled |

**Scopes:**

- `forAgent($agent)` - Filter by agent
- `forPeriod($period)` - Filter by period (e.g., '2026-01')
- `payable()` - Only payable earnings
- `pending()` - Only pending earnings

### CommissionSplit

For team sales where commission is divided:

```php
use SalesCommission\Models\CommissionSplit;

$split = CommissionSplit::create([
    'earning_id' => $earning->id,
    'agent_type' => User::class,
    'agent_id' => $user->id,
    'split_percentage' => 60,
    'split_amount' => 60.00,
    'role' => 'primary',
]);
```

### CommissionClawback

```php
use SalesCommission\Models\CommissionClawback;

// Reasons
CommissionClawback::REASON_REFUND;       // 'refund'
CommissionClawback::REASON_CHARGEBACK;   // 'chargeback'
CommissionClawback::REASON_CANCELLATION; // 'cancellation'
CommissionClawback::REASON_MANUAL;       // 'manual'
```

### Payout

```php
use SalesCommission\Models\Payout;

// Status values
Payout::STATUS_DRAFT;            // 'draft'
Payout::STATUS_PENDING_APPROVAL; // 'pending_approval'
Payout::STATUS_APPROVED;         // 'approved'
Payout::STATUS_PROCESSING;       // 'processing'
Payout::STATUS_PAID;             // 'paid'
Payout::STATUS_FAILED;           // 'failed'
Payout::STATUS_CANCELLED;        // 'cancelled'
```

---

## Services Reference

### CommissionCalculator

The main service for calculating commissions.

```php
use SalesCommission\Services\CommissionCalculator;

$calculator = app(CommissionCalculator::class);

// Basic calculation
$earning = $calculator->calculate($order);

// With specific agent
$earning = $calculator->calculate($order, $salesRep);

// With specific plan
$earning = $calculator
    ->forPlan('enterprise')
    ->calculate($order);

// With additional context
$earning = $calculator
    ->withContext(['product_category' => 'premium'])
    ->calculate($order);

// Batch calculation
$earnings = $calculator->calculateBatch($orders, $agent);
```

### PayoutService

Manages payout generation and processing.

```php
use SalesCommission\Services\PayoutService;

$payoutService = app(PayoutService::class);

// Generate payout for a period
$payout = $payoutService->generateForPeriod('2026-01');

// Generate for current period
$payout = $payoutService->generateForCurrentPeriod();

// Get pending amount for an agent
$amount = $payoutService->getPendingPayoutAmount($user);

// Get payout history
$history = $payoutService->getPayoutHistory($user, 10);

// Get statistics
$stats = $payoutService->getPayoutStats('2026-01');
// Returns: ['total_paid' => 0, 'total_pending' => 1500, 'payout_count' => 5]

// Process approved payouts
$processed = $payoutService->processApprovedPayouts();
```

### ClawbackService

Handles commission reversals.

```php
use SalesCommission\Services\ClawbackService;

$clawbackService = app(ClawbackService::class);

// Full clawback
$clawback = $clawbackService->clawback(
    $earning,
    CommissionClawback::REASON_REFUND,
    null, // Full amount
    'Customer requested refund',
    auth()->id()
);

// Partial clawback (proportional to refund amount)
$clawback = $clawbackService->partialClawback(
    $earning,
    50.00, // Refund amount
    CommissionClawback::REASON_REFUND,
    'Partial refund processed'
);

// Clawback all commissions for an order
$clawbacks = $clawbackService->clawbackForCommissionable(
    $order,
    CommissionClawback::REASON_CANCELLATION,
    'Order cancelled',
    auth()->id()
);
```

### SplitCalculator

For team sales with multiple agents.

```php
use SalesCommission\Facades\Commission;

$splits = Commission::split($order)
    ->between([
        $primaryRep => ['percentage' => 60, 'role' => 'primary'],
        $supportRep => ['percentage' => 25, 'role' => 'support'],
        $manager => ['percentage' => 15, 'role' => 'manager'],
    ])
    ->calculate();
```

### TierEvaluator

Evaluates and updates agent tiers.

```php
use SalesCommission\Services\TierEvaluator;

$evaluator = app(TierEvaluator::class);

// Get current tier for an agent
$tier = $evaluator->getCurrentTier($agent, $plan);

// Check if agent qualifies for tier upgrade
$newTier = $evaluator->evaluateForUpgrade($agent, $plan);
```

---

## Usage Examples

### Example 1: Basic E-commerce Setup

**Step 1: Add trait to User model**

```php
// app/Models/User.php
use SalesCommission\Traits\HasCommissions;

class User extends Authenticatable
{
    use HasCommissions;
}
```

**Step 2: Add trait to Order model**

```php
// app/Models/Order.php
use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;

class Order extends Model implements Commissionable
{
    public function getCommissionableAmount(): float
    {
        return $this->total - $this->tax - $this->shipping;
    }

    public function getCommissionAgent(): ?CommissionAgent
    {
        return $this->salesRep;
    }

    public function getCommissionDate(): Carbon
    {
        return $this->created_at;
    }

    public function getCommissionMeta(): array
    {
        return [
            'order_id' => $this->id,
            'customer_id' => $this->customer_id,
            'product_category' => $this->product_category,
        ];
    }
}
```

**Step 3: Create a commission plan**

```php
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionRule;

$plan = CommissionPlan::create([
    'name' => 'Standard Sales',
    'slug' => 'standard',
    'is_active' => true,
    'is_default' => true,
]);

$plan->tiers()->createMany([
    ['name' => 'Bronze', 'min_threshold' => 0, 'max_threshold' => 10000, 'rate' => 5],
    ['name' => 'Silver', 'min_threshold' => 10001, 'max_threshold' => 50000, 'rate' => 7.5],
    ['name' => 'Gold', 'min_threshold' => 50001, 'max_threshold' => null, 'rate' => 10],
]);

$plan->rules()->create([
    'name' => 'Standard Percentage',
    'type' => 'tiered',
    'is_active' => true,
]);
```

**Step 4: Calculate commission when order is placed**

```php
// In OrderController or OrderObserver
use SalesCommission\Facades\Commission;

public function store(Request $request)
{
    $order = Order::create($request->validated());

    // Calculate commission
    $earning = Commission::calculate($order);

    return response()->json([
        'order' => $order,
        'commission' => $earning->commission_amount,
    ]);
}
```

### Example 2: SaaS Subscription with Clawback

```php
// When subscription is cancelled with refund
use SalesCommission\Services\ClawbackService;

public function cancelSubscription(Subscription $subscription, bool $refund = false)
{
    $subscription->cancel();

    if ($refund) {
        $clawbackService = app(ClawbackService::class);

        $clawbacks = $clawbackService->clawbackForCommissionable(
            $subscription,
            CommissionClawback::REASON_CANCELLATION,
            'Subscription cancelled with refund'
        );
    }
}
```

### Example 3: Team Sales Split

```php
use SalesCommission\Facades\Commission;

// A deal closed by a team
$deal = Deal::find(1);

$splits = Commission::split($deal)
    ->between([
        $accountExecutive => ['percentage' => 50, 'role' => 'closer'],
        $sdr => ['percentage' => 20, 'role' => 'sourcer'],
        $solutionsEngineer => ['percentage' => 20, 'role' => 'technical'],
        $teamLead => ['percentage' => 10, 'role' => 'manager'],
    ])
    ->calculate();

// Each team member now has their split recorded
```

### Example 4: Monthly Payout Processing

```php
// In a scheduled command or controller
use SalesCommission\Services\PayoutService;

public function processMonthlyPayouts()
{
    $payoutService = app(PayoutService::class);

    // Generate payout for previous month
    $period = now()->subMonth()->format('Y-m');
    $payout = $payoutService->generateForPeriod($period);

    // Auto-approve if configured, otherwise requires manual approval
    if (!config('sales-commission.payout.auto_approve')) {
        // Notify finance team for approval
        Mail::to('finance@company.com')->send(new PayoutPendingApproval($payout));
    }

    return $payout;
}
```

---

## Events

The package dispatches events for key actions:

### CommissionEarned

Dispatched when a commission is calculated and saved.

```php
use SalesCommission\Events\CommissionEarned;

// In EventServiceProvider
protected $listen = [
    CommissionEarned::class => [
        NotifySalesRep::class,
        UpdateSalesLeaderboard::class,
    ],
];

// In your listener
public function handle(CommissionEarned $event)
{
    $earning = $event->earning;
    $agent = $event->agent;

    // Send notification
    $agent->notify(new CommissionEarnedNotification($earning));
}
```

### CommissionClawedBack

Dispatched when a commission is clawed back.

```php
use SalesCommission\Events\CommissionClawedBack;

public function handle(CommissionClawedBack $event)
{
    $clawback = $event->clawback;

    // Notify finance team
    Log::info("Commission clawed back: {$clawback->amount}");
}
```

### PayoutProcessed

Dispatched when a payout is marked as paid.

```php
use SalesCommission\Events\PayoutProcessed;

public function handle(PayoutProcessed $event)
{
    $payout = $event->payout;

    // Send confirmation emails to all agents in payout
}
```

### TierAchieved

Dispatched when an agent reaches a new tier.

```php
use SalesCommission\Events\TierAchieved;

public function handle(TierAchieved $event)
{
    $agent = $event->agent;
    $tier = $event->tier;

    // Celebrate!
    $agent->notify(new TierUpgradeNotification($tier));
}
```

---

## Artisan Commands

### Process Payouts

```bash
# Process payouts for a specific period
php artisan commission:process-payouts --period=2026-01

# Dry run (show what would be processed)
php artisan commission:process-payouts --period=2026-01 --dry-run

# Process current period
php artisan commission:process-payouts
```

### Recalculate Tiers

```bash
# Recalculate all agent tiers
php artisan commission:recalculate-tiers --all

# Recalculate for a specific plan
php artisan commission:recalculate-tiers --plan=standard
```

---

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Or directly with PHPUnit
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature

# Run with coverage (requires Xdebug)
composer test-coverage
```

### Test Coverage

The package includes tests for:

- Commission Plans and Tiers
- Commission Rules (Percentage, Flat, Tiered)
- Commission Calculator
- Payout Service
- Clawback Service
- Split Calculator

---

## Filament Admin Panel

The package includes a beautiful admin panel powered by Filament.

### Installation

1. Install Filament (if not already installed):

```bash
composer require filament/filament:"^3.0"
php artisan filament:install --panels
```

2. Register the plugin in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use SalesCommission\Pro\Filament\SalesCommissionPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        // ... other config ...
        ->plugins([
            SalesCommissionPlugin::make(),
        ]);
}
```

3. Clear cache:

```bash
php artisan config:clear
php artisan cache:clear
```

### Dashboard

Navigate to `/admin` to access the commission management dashboard.

The **Commission Dashboard** provides an overview of:

- **Earnings This Month** - Total commissions earned in current period
- **Pending Payouts** - Amount awaiting approval/processing
- **Paid This Month** - Successfully processed payouts
- **Clawbacks This Month** - Reversed commissions
- **Active Plans** - Number of active commission plans

### Managing Commission Plans

Navigate to **Commissions → Commission Plans**

#### Create a New Plan

1. Click **New Commission Plan**
2. Fill in:
   - **Name**: e.g., "Standard Sales Plan"
   - **Slug**: e.g., "standard" (used in code)
   - **Description**: Optional description
   - **Active**: Toggle on
   - **Default**: Set as default plan

#### Adding Tiers to a Plan

1. Edit a plan
2. Go to **Tiers** tab
3. Click **Create Tier**
4. Set:
   - **Name**: e.g., "Bronze", "Silver", "Gold"
   - **Min Threshold**: Minimum sales amount for this tier
   - **Max Threshold**: Maximum (leave empty for unlimited)
   - **Rate**: Commission percentage (e.g., 5, 7.5, 10)
   - **Bonus Amount**: Optional one-time bonus for reaching tier

#### Adding Rules to a Plan

1. Edit a plan
2. Go to **Rules** tab
3. Click **Create Rule**
4. Set:
   - **Name**: e.g., "Base Commission"
   - **Type**: percentage, fixed, tiered, or bonus_threshold
   - **Value**: The rate or amount
   - **Priority**: Lower numbers run first
   - **Active**: Toggle on

### Managing Earnings

Navigate to **Commissions → Earnings**

- View all commission earnings
- Filter by status: Pending, Payable, Paid, Clawed Back
- Filter by period (YYYY-MM format)
- View earning details

### Managing Payouts

Navigate to **Commissions → Payouts**

- View all payouts
- **Approve**: Click approve button on pending payouts
- **Process**: Mark approved payouts as paid
- View payout details and associated earnings

### Viewing Clawbacks

Navigate to **Commissions → Clawbacks**

- View all commission clawbacks
- Filter by reason: Refund, Chargeback, Cancellation, Manual
- See clawback amounts and dates

---

## REST API Reference

All API endpoints are prefixed with `/api/commissions` and require authentication.

### Authentication

The API uses **Laravel Sanctum** for authentication. Include your API token in requests:

```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
     -H "Accept: application/json" \
     https://your-app.com/api/commissions/plans
```

#### Getting an API Token

Users can generate API tokens using Laravel Sanctum:

```php
// In your controller or via API
$token = $user->createToken('commission-api')->plainTextToken;
```

### Commission Plans API

| Method | Endpoint                                  | Description       |
| ------ | ----------------------------------------- | ----------------- |
| GET    | `/api/commissions/plans`                  | List all plans    |
| POST   | `/api/commissions/plans`                  | Create a plan     |
| GET    | `/api/commissions/plans/{id}`             | Get a single plan |
| PUT    | `/api/commissions/plans/{id}`             | Update a plan     |
| DELETE | `/api/commissions/plans/{id}`             | Delete a plan     |
| POST   | `/api/commissions/plans/{id}/activate`    | Activate plan     |
| POST   | `/api/commissions/plans/{id}/deactivate`  | Deactivate plan   |
| POST   | `/api/commissions/plans/{id}/set-default` | Set as default    |

### Commission Tiers API

| Method | Endpoint                                 | Description           |
| ------ | ---------------------------------------- | --------------------- |
| GET    | `/api/commissions/plans/{plan_id}/tiers` | List tiers for a plan |
| POST   | `/api/commissions/plans/{plan_id}/tiers` | Create a tier         |
| PUT    | `/api/commissions/tiers/{id}`            | Update a tier         |
| DELETE | `/api/commissions/tiers/{id}`            | Delete a tier         |

### Commission Earnings API

| Method | Endpoint                                        | Description        |
| ------ | ----------------------------------------------- | ------------------ |
| GET    | `/api/commissions/earnings`                     | List all earnings  |
| GET    | `/api/commissions/earnings/{id}`                | Get single earning |
| GET    | `/api/commissions/earnings/by-agent/{agent_id}` | Earnings by agent  |
| GET    | `/api/commissions/earnings/by-period/{period}`  | Earnings by period |
| POST   | `/api/commissions/earnings/{id}/mark-payable`   | Mark as payable    |

### Payouts API

| Method | Endpoint                                | Description                |
| ------ | --------------------------------------- | -------------------------- |
| GET    | `/api/commissions/payouts`              | List all payouts           |
| GET    | `/api/commissions/payouts/pending`      | Get pending payouts        |
| POST   | `/api/commissions/payouts/generate`     | Generate payout for period |
| POST   | `/api/commissions/payouts/{id}/approve` | Approve payout             |
| POST   | `/api/commissions/payouts/{id}/reject`  | Reject payout              |
| POST   | `/api/commissions/payouts/{id}/process` | Process payout             |

### Clawbacks API

| Method | Endpoint                                        | Description                 |
| ------ | ----------------------------------------------- | --------------------------- |
| GET    | `/api/commissions/clawbacks`                    | List all clawbacks          |
| GET    | `/api/commissions/clawbacks/{id}`               | Get single clawback         |
| POST   | `/api/commissions/clawbacks`                    | Create full clawback        |
| POST   | `/api/commissions/clawbacks/partial`            | Create partial clawback     |
| POST   | `/api/commissions/clawbacks/for-commissionable` | Clawback for commissionable |

### Agent API

| Method | Endpoint                                      | Description          |
| ------ | --------------------------------------------- | -------------------- |
| GET    | `/api/commissions/agents/{agent_id}/earnings` | Get agent earnings   |
| GET    | `/api/commissions/agents/{agent_id}/total`    | Get agent total      |
| GET    | `/api/commissions/agents/{agent_id}/pending`  | Get pending earnings |
| GET    | `/api/commissions/agents/{agent_id}/tier`     | Get current tier     |
| GET    | `/api/commissions/agents/{agent_id}/payouts`  | Get agent payouts    |

### Calculate Commission API

| Method | Endpoint                             | Description                 |
| ------ | ------------------------------------ | --------------------------- |
| POST   | `/api/commissions/calculate`         | Calculate single commission |
| POST   | `/api/commissions/calculate/batch`   | Calculate batch             |
| POST   | `/api/commissions/calculate/preview` | Preview without saving      |

### Statistics API

| Method | Endpoint                             | Description        |
| ------ | ------------------------------------ | ------------------ |
| GET    | `/api/commissions/stats/overview`    | Get overview stats |
| GET    | `/api/commissions/stats/earnings`    | Earnings over time |
| GET    | `/api/commissions/stats/payouts`     | Payout statistics  |
| GET    | `/api/commissions/stats/top-earners` | Top earners        |
| GET    | `/api/commissions/stats/by-plan`     | Earnings by plan   |
| GET    | `/api/commissions/stats/trends`      | Daily trends       |

### API Documentation

Interactive API documentation is available at `/api/commissions/docs`.

---

## API Reference

### Facade Methods

```php
use SalesCommission\Facades\Commission;

// Set plan for calculation
Commission::forPlan($planOrIdOrSlug);

// Add context for rule evaluation
Commission::withContext(array $context);

// Calculate single commission
Commission::calculate($commissionable, $agent = null);

// Calculate multiple commissions
Commission::calculateBatch(iterable $commissionables, $agent = null);

// Get total earnings for agent
Commission::getTotalEarnings($agent, ?string $period = null);

// Get pending earnings
Commission::getPendingEarnings($agent);

// Get current tier
Commission::getCurrentTier($agent, $plan = null);

// Create split calculator
Commission::split($commissionable);
```

### Database Schema

#### commission_plans

| Column      | Type      | Description                      |
| ----------- | --------- | -------------------------------- |
| id          | ULID      | Primary key                      |
| name        | string    | Plan name                        |
| slug        | string    | URL-friendly identifier          |
| description | text      | Plan description                 |
| is_active   | boolean   | Whether plan is active           |
| is_default  | boolean   | Whether this is the default plan |
| created_at  | timestamp | Creation timestamp               |
| updated_at  | timestamp | Update timestamp                 |

#### commission_earnings

| Column              | Type      | Description                 |
| ------------------- | --------- | --------------------------- |
| id                  | ULID      | Primary key                 |
| agent_type          | string    | Agent model class           |
| agent_id            | ULID      | Agent ID                    |
| commissionable_type | string    | Commissionable model class  |
| commissionable_id   | ULID      | Commissionable ID           |
| plan_id             | ULID      | Commission plan used        |
| tier_id             | ULID      | Tier when earned            |
| payout_id           | ULID      | Payout batch (nullable)     |
| base_amount         | decimal   | Sale amount                 |
| commission_amount   | decimal   | Calculated commission       |
| rate                | decimal   | Applied rate                |
| rate_type           | string    | Rate type (percentage/flat) |
| status              | string    | Current status              |
| period              | string    | Earning period (Y-m)        |
| earned_at           | timestamp | When earned                 |
| paid_at             | timestamp | When paid (nullable)        |
| metadata            | json      | Additional data             |

---

## Troubleshooting

### Admin Panel Not Showing

1. Verify Filament is installed: `composer show filament/filament`
2. Check plugin is registered in AdminPanelProvider
3. Clear views: `php artisan view:clear`

### API Returns 401 Unauthorized

1. Check Bearer token is included in header
2. Verify token is valid and not expired
3. Ensure Sanctum middleware is configured
4. Check user has permission

### Memory Issues

1. Increase PHP memory: `php -d memory_limit=1G artisan serve`
2. Clear all caches: `php artisan optimize:clear`

---

## Support

- **Documentation**: https://github.com/ayangzy/laravel-sales-commission
- **Issues**: https://github.com/ayangzy/laravel-sales-commission/issues
- **Email**: ayangefelix8@gmail.com

## License

The MIT License (MIT). See LICENSE.md for more information.

---

_Documentation generated for Laravel Sales Commission v2.0.0_
