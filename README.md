# Laravel Sales Commission

[![Tests](https://github.com/ayangzy/laravel-sales-commission/actions/workflows/tests.yml/badge.svg)](https://github.com/ayangzy/laravel-sales-commission/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/ayangzy/laravel-sales-commission.svg?style=flat-square)](https://packagist.org/packages/ayangzy/laravel-sales-commission)
[![Total Downloads](https://img.shields.io/packagist/dt/ayangzy/laravel-sales-commission.svg?style=flat-square)](https://packagist.org/packages/ayangzy/laravel-sales-commission)
[![License](https://img.shields.io/packagist/l/ayangzy/laravel-sales-commission.svg?style=flat-square)](LICENSE.md)

## What is Laravel Sales Commission?

**Laravel Sales Commission** is a comprehensive, enterprise-grade commission calculation and management package designed specifically for Laravel SaaS applications. It provides a complete solution for businesses that need to track, calculate, and pay commissions to sales representatives, affiliates, partners, or any type of agent.

Unlike simple percentage calculators, this package handles the **entire commission lifecycle** — from initial calculation through clawbacks (refunds) to final payout processing. It's built to handle real-world complexity including team-based sales, tiered commission structures, and flexible payout schedules.

Whether you're building an e-commerce platform with sales reps, a SaaS with an affiliate program, or a network marketing application, this package provides the foundation you need without reinventing the wheel.

---

## Key Features

### 🎯 Flexible Commission Plans

Create unlimited commission plans tailored to different agent types, products, or sales channels. Each plan can have its own rules, tiers, and payout settings. Perfect for businesses with diverse sales structures — one plan for in-house sales reps, another for external affiliates.

### 📊 Multi-Tier Commission Structures

Implement progressive commission rates that reward high performers. Agents automatically move through tiers (e.g., Bronze → Silver → Gold) as their cumulative sales increase, unlocking higher commission percentages. This incentivizes continuous performance improvement.

### 👥 Team Split Commissions

Handle complex team sales where multiple agents contribute to a deal. Split commissions by percentage with role tracking (primary closer, supporting rep, manager override). Ensures fair attribution when sales involve collaboration.

### 🔄 Clawback Support

Protect your business when sales are refunded or charged back. The clawback system reverses commissions proportionally with configurable grace periods. Supports full clawbacks, partial clawbacks, and automatic clawbacks on refund events.

### 💰 Payout Management

Complete payout lifecycle management — generate payout batches, require approval workflows, process payments, and track payment status. Supports configurable payout schedules (weekly, bi-weekly, monthly) and minimum payout thresholds.

### 📈 Performance Bonuses

Reward agents for hitting milestones with bonus rules. Configure one-time or recurring bonuses when agents reach sales targets, close their first deal, or achieve tier upgrades.

### 🎛️ Extensible Rule Engine

The modular rule system supports percentage-based, flat-rate, tiered, and conditional commissions out of the box. Need custom logic? Extend the base rule class to implement any commission formula your business requires.

### 📅 Period-Based Tracking

All earnings are tracked by period (week, month, quarter) enabling accurate reporting, forecasting, and historical analysis. Easily query earnings for any time range.

### ⚡ Event-Driven Architecture

Hooks into Laravel's event system. Listen to `CommissionEarned`, `CommissionClawedBack`, `PayoutProcessed`, and `TierAchieved` events to trigger notifications, update leaderboards, or integrate with external systems.

---

## Use Cases

### Who Needs This Package?

| Platform Type                    | Why You Need It                                                       |
| -------------------------------- | --------------------------------------------------------------------- |
| **E-commerce with Sales Reps**   | Track commissions per order, handle returns/refunds, pay reps monthly |
| **SaaS with Affiliate Programs** | Recurring commissions on subscriptions, clawback on cancellations     |
| **Marketplace Platforms**        | Split commissions between sellers, platform, and referrers            |
| **Insurance & Real Estate**      | Complex tiered structures, team overrides, compliance-ready           |
| **Network Marketing / MLM**      | Multi-level commission trees, performance tiers, bonus structures     |
| **Recruitment Agencies**         | Placement fees, split commissions, clawback on early termination      |
| **Freelance Platforms**          | Platform fees, referral bonuses, milestone payments                   |

---

## How It's Different

| Feature                       | Laravel Sales Commission                  | Other Packages   |
| ----------------------------- | ----------------------------------------- | ---------------- |
| **Full Lifecycle Management** | Calculation → Clawback → Payout           | Calculation only |
| **Team Split Commissions**    | ✅ Built-in with roles                    | ❌ Not supported |
| **Clawback System**           | ✅ Full, partial, grace periods           | ❌ Not supported |
| **Payout Processing**         | ✅ Generate, approve, process             | ❌ Not supported |
| **Multi-Tier Structures**     | ✅ Automatic tier progression             | ⚠️ Basic tiers   |
| **Conditional Rules**         | ✅ Any condition (category, amount, etc.) | ⚠️ Limited       |
| **Event System**              | ✅ 4 lifecycle events                     | ⚠️ Minimal       |
| **Period Tracking**           | ✅ Weekly/Monthly/Quarterly               | ❌ Not supported |
| **Production Ready**          | ✅ 31 tests, CI/CD, documented            | ⚠️ Varies        |

---

## Requirements

- PHP 8.2+
- Laravel 10.x or 11.x

## Installation

```bash
composer require ayangzy/laravel-sales-commission
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag="sales-commission-config"
php artisan vendor:publish --tag="sales-commission-migrations"
php artisan migrate
```

## Quick Start

### 1. Add Traits to Your Models

**User/Agent Model:**

```php
use SalesCommission\Traits\HasCommissions;

class User extends Model
{
    use HasCommissions;
}
```

**Order/Sale Model:**

```php
use SalesCommission\Traits\Commissionable;

class Order extends Model
{
    use Commissionable;

    public function getCommissionableAmount(): float
    {
        return $this->total;
    }

    public function getCommissionAgent(): ?Model
    {
        return $this->salesRep;
    }
}
```

### 2. Create a Commission Plan

```php
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionTier;
use SalesCommission\Models\CommissionRule;

// Create a plan
$plan = CommissionPlan::create([
    'name' => 'Standard Sales Plan',
    'slug' => 'standard',
    'is_active' => true,
    'is_default' => true,
]);

// Add tiers
$plan->tiers()->createMany([
    ['name' => 'Bronze', 'min_threshold' => 0, 'max_threshold' => 10000, 'rate' => 5],
    ['name' => 'Silver', 'min_threshold' => 10001, 'max_threshold' => 50000, 'rate' => 7.5],
    ['name' => 'Gold', 'min_threshold' => 50001, 'max_threshold' => null, 'rate' => 10],
]);

// Add a rule
$plan->rules()->create([
    'name' => 'Standard Commission',
    'type' => 'percentage',
    'value' => 10, // 10%
    'is_active' => true,
]);
```

### 3. Calculate Commissions

```php
use SalesCommission\Facades\Commission;

// Calculate commission for a sale
$earning = Commission::calculate($order);

// Or with a specific agent
$earning = Commission::calculate($order, $salesRep);

// Use a specific plan
$earning = Commission::forPlan('enterprise')
    ->calculate($order);

// Bulk calculation
$earnings = Commission::calculateBatch($orders);
```

### 4. Team Split Commissions

```php
Commission::split($order)
    ->between([
        $primaryRep => ['percentage' => 60, 'role' => 'primary'],
        $supportRep => ['percentage' => 25, 'role' => 'support'],
        $manager => ['percentage' => 15, 'role' => 'manager'],
    ])
    ->calculate();
```

### 5. Handle Clawbacks

```php
use SalesCommission\Services\ClawbackService;

$clawbackService = app(ClawbackService::class);

// Full clawback
$clawback = $clawbackService->clawback($earning, 'refund');

// Partial clawback
$clawback = $clawbackService->partialClawback($earning, 50.00, 'partial_refund');

// Clawback all commissions for an order
$clawbacks = $clawbackService->clawbackForCommissionable($order, 'chargeback');
```

### 6. Process Payouts

```php
use SalesCommission\Models\Payout;

// Generate payout for current period
$payout = Payout::generate('2026-01');

// Approve and process
$payout->approve(auth()->id());
$payout->markAsPaid([
    'reference' => 'PAY-12345',
    'method' => 'stripe',
]);
```

Or use the artisan command:

```bash
php artisan commission:process-payouts --period=2026-01
```

## Events

Listen to commission events in your application:

```php
// EventServiceProvider
protected $listen = [
    \SalesCommission\Events\CommissionEarned::class => [
        NotifySalesRep::class,
        UpdateLeaderboard::class,
    ],
    \SalesCommission\Events\CommissionClawedBack::class => [
        NotifyFinanceTeam::class,
    ],
    \SalesCommission\Events\PayoutProcessed::class => [
        SendPayoutConfirmation::class,
    ],
    \SalesCommission\Events\TierAchieved::class => [
        CelebrateTierUpgrade::class,
    ],
];
```

## Artisan Commands

```bash
# Process payouts
php artisan commission:process-payouts --period=2024-01
php artisan commission:process-payouts --dry-run

# Recalculate tiers
php artisan commission:recalculate-tiers --all
php artisan commission:recalculate-tiers --plan=standard
```

## Configuration

See `config/sales-commission.php` for all options:

```php
return [
    'models' => [
        'agent' => App\Models\User::class,
    ],

    'clawback' => [
        'enabled' => true,
        'grace_period_days' => 30,
        'auto_on_refund' => true,
    ],

    'payout' => [
        'min_threshold' => 50.00,
        'auto_approve' => false,
        'schedule' => 'monthly',
        'hold_period_days' => 14,
    ],

    'currency' => 'USD',
];
```

## Custom Rules

Create custom commission rules:

```php
use SalesCommission\Rules\BaseRule;
use SalesCommission\Contracts\Commissionable;
use SalesCommission\Contracts\CommissionAgent;

class FirstSaleBonusRule extends BaseRule
{
    public function getType(): string
    {
        return 'first_sale_bonus';
    }

    public function calculate(Commissionable $commissionable, CommissionAgent $agent, array $context = []): float
    {
        // Check if this is the agent's first sale
        if ($agent->commissionEarnings()->count() === 0) {
            return 100.00; // $100 bonus
        }

        return 0;
    }
}
```

## Testing

```bash
composer test
```

## Admin Dashboard (Filament)

This package includes a beautiful admin panel powered by Filament:

```php
// In your Filament PanelProvider
use SalesCommission\Pro\Filament\SalesCommissionPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            SalesCommissionPlugin::make(),
        ]);
}
```

Features include:

- View all commission plans and tiers
- Track earnings by agent, period, status
- Approve and process payouts
- Generate reports
- Manage clawbacks

## API Endpoints

RESTful API endpoints are available at `/api/commissions`:

- `GET/POST /api/commissions/plans` - Manage commission plans
- `GET /api/commissions/earnings` - View earnings
- `POST /api/commissions/calculate` - Calculate commissions
- `GET/POST /api/commissions/payouts` - Manage payouts
- `GET /api/commissions/stats/overview` - Get stats

API documentation available at `/api/commissions/docs`.

## Support the Project

If this package helps you, please consider giving it a ⭐ on GitHub - it helps others discover the project!

## Author

**Ayangzy** - [GitHub](https://github.com/ayangzy)

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
