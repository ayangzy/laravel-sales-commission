# Laravel Sales Commission - Manual Test Flow

This guide walks you through testing all features of the package step by step.

---

## Prerequisites

Before testing, ensure you have:

1. A Laravel 10.x or 11.x application
2. The package installed and configured
3. Migrations run

```bash
# Install in your Laravel app
composer require ayangzy/laravel-sales-commission

# Publish and run migrations
php artisan vendor:publish --tag="sales-commission-config"
php artisan vendor:publish --tag="sales-commission-migrations"
php artisan migrate
```

---

## Test Flow 1: Commission Plans & Tiers

### Step 1.1: Create a Commission Plan

Open **Tinker** and create a plan:

```bash
php artisan tinker
```

```php
use SalesCommission\Models\CommissionPlan;

$plan = CommissionPlan::create([
    'name' => 'Standard Sales Plan',
    'slug' => 'standard',
    'description' => 'Default plan for sales team',
    'is_active' => true,
    'is_default' => true,
]);

echo "Created plan: {$plan->name} (ID: {$plan->id})";
```

**Expected:** Plan created with ULID ID.

### Step 1.2: Add Commission Tiers

```php
$plan->tiers()->createMany([
    ['name' => 'Bronze', 'min_threshold' => 0, 'max_threshold' => 10000, 'rate' => 5],
    ['name' => 'Silver', 'min_threshold' => 10001, 'max_threshold' => 50000, 'rate' => 7.5],
    ['name' => 'Gold', 'min_threshold' => 50001, 'max_threshold' => null, 'rate' => 10],
]);

echo "Tiers: " . $plan->tiers->pluck('name')->join(', ');
```

**Expected:** 3 tiers created (Bronze, Silver, Gold).

### Step 1.3: Test Tier Lookup

```php
$bronzeTier = $plan->findTierForAmount(5000);
echo "Tier for $5,000: {$bronzeTier->name} ({$bronzeTier->rate}%)";

$silverTier = $plan->findTierForAmount(25000);
echo "Tier for $25,000: {$silverTier->name} ({$silverTier->rate}%)";

$goldTier = $plan->findTierForAmount(100000);
echo "Tier for $100,000: {$goldTier->name} ({$goldTier->rate}%)";
```

**Expected:**

- $5,000 → Bronze (5%)
- $25,000 → Silver (7.5%)
- $100,000 → Gold (10%)

---

## Test Flow 2: Commission Rules

### Step 2.1: Create a Percentage Rule

```php
use SalesCommission\Models\CommissionRule;

$plan = CommissionPlan::where('slug', 'standard')->first();

$rule = CommissionRule::create([
    'plan_id' => $plan->id,
    'name' => 'Base Commission',
    'type' => 'percentage',
    'value' => 10,
    'priority' => 1,
    'is_active' => true,
]);

echo "Created rule: {$rule->name}";
```

### Step 2.2: Create a Conditional Rule

```php
$bonusRule = CommissionRule::create([
    'plan_id' => $plan->id,
    'name' => 'Premium Product Bonus',
    'type' => 'fixed',
    'value' => 50,
    'conditions' => [
        'product_category' => 'premium',
    ],
    'priority' => 2,
    'is_active' => true,
]);

echo "Created conditional rule: {$bonusRule->name}";
```

### Step 2.3: Test Rule Conditions

```php
// Test condition matching
$context = ['product_category' => 'premium'];
echo "Matches premium: " . ($bonusRule->matchesConditions($context) ? 'YES' : 'NO');

$context2 = ['product_category' => 'standard'];
echo "Matches standard: " . ($bonusRule->matchesConditions($context2) ? 'YES' : 'NO');
```

**Expected:**

- Premium context → YES
- Standard context → NO

---

## Test Flow 3: Calculate Commissions

### Step 3.1: Setup Test Models

First, add the trait to your User model:

```php
// app/Models/User.php
use SalesCommission\Traits\HasCommissions;

class User extends Authenticatable
{
    use HasCommissions;
}
```

Create a simple commissionable model (or use an existing Order model):

```php
// For testing, create a simple class
$order = new class {
    public $id = 1;
    public $total = 1000;

    public function getCommissionableAmount() { return $this->total; }
    public function getCommissionAgent() { return null; }
    public function getCommissionDate() { return now(); }
    public function getCommissionMeta() { return ['order_id' => $this->id]; }
    public function getKey() { return $this->id; }
    public function getMorphClass() { return 'Order'; }
};
```

### Step 3.2: Calculate Basic Commission

```php
use SalesCommission\Services\CommissionCalculator;
use App\Models\User;

$calculator = app(CommissionCalculator::class);
$plan = CommissionPlan::where('slug', 'standard')->first();
$agent = User::first(); // Or create a test user

// Create a mock commissionable
$earning = $calculator
    ->forPlan($plan)
    ->calculate($order, $agent);

echo "Base Amount: $" . $earning->base_amount;
echo "Commission: $" . $earning->commission_amount;
echo "Rate: " . $earning->rate . "%";
echo "Status: " . $earning->status;
```

**Expected:**

- Base Amount: $1,000
- Commission: $100 (10% of $1,000)
- Status: pending

### Step 3.3: Calculate Batch Commissions

```php
$orders = [
    (object)['id' => 1, 'total' => 1000],
    (object)['id' => 2, 'total' => 2000],
    (object)['id' => 3, 'total' => 5000],
];

// Wrap in commissionable interface...
$earnings = $calculator
    ->forPlan($plan)
    ->calculateBatch($orders, $agent);

echo "Processed: " . count($earnings) . " commissions";
echo "Total: $" . collect($earnings)->sum('commission_amount');
```

---

## Test Flow 4: Team Split Commissions

### Step 4.1: Create Team Members

```php
use App\Models\User;

$primaryRep = User::create(['name' => 'Primary Rep', 'email' => 'primary@test.com', 'password' => bcrypt('password')]);
$supportRep = User::create(['name' => 'Support Rep', 'email' => 'support@test.com', 'password' => bcrypt('password')]);
$manager = User::create(['name' => 'Manager', 'email' => 'manager@test.com', 'password' => bcrypt('password')]);
```

### Step 4.2: Calculate Split Commission

```php
use SalesCommission\Facades\Commission;

$splits = Commission::forPlan($plan)
    ->split($order)
    ->between([
        $primaryRep => ['percentage' => 60, 'role' => 'primary'],
        $supportRep => ['percentage' => 25, 'role' => 'support'],
        $manager => ['percentage' => 15, 'role' => 'manager'],
    ])
    ->calculate();

foreach ($splits as $split) {
    echo "{$split->role}: $" . $split->split_amount . " ({$split->split_percentage}%)";
}
```

**Expected (for $100 total commission):**

- Primary: $60 (60%)
- Support: $25 (25%)
- Manager: $15 (15%)

### Step 4.3: Test Invalid Split (Should Fail)

```php
try {
    $splits = Commission::forPlan($plan)
        ->split($order)
        ->between([
            $primaryRep => 50,
            $supportRep => 30,
            // Only 80% - should throw exception
        ])
        ->calculate();
} catch (\InvalidArgumentException $e) {
    echo "Caught expected error: " . $e->getMessage();
}
```

**Expected:** Exception with "Split percentages must total 100%"

---

## Test Flow 5: Clawbacks

### Step 5.1: Create an Earning to Clawback

```php
use SalesCommission\Models\CommissionEarning;

$earning = CommissionEarning::create([
    'agent_type' => User::class,
    'agent_id' => $primaryRep->id,
    'commissionable_type' => 'Order',
    'commissionable_id' => 1,
    'base_amount' => 1000,
    'commission_amount' => 100,
    'rate' => 10,
    'rate_type' => 'percentage',
    'status' => CommissionEarning::STATUS_PENDING,
    'period' => now()->format('Y-m'),
    'earned_at' => now(),
]);

echo "Created earning: $" . $earning->commission_amount;
```

### Step 5.2: Full Clawback

```php
use SalesCommission\Services\ClawbackService;
use SalesCommission\Models\CommissionClawback;

$clawbackService = app(ClawbackService::class);

$clawback = $clawbackService->clawback(
    $earning,
    CommissionClawback::REASON_REFUND,
    null, // Full amount
    'Customer requested refund'
);

echo "Clawed back: $" . $clawback->amount;
echo "Reason: " . $clawback->reason;

// Check earning status
$earning->refresh();
echo "Earning status: " . $earning->status;
```

**Expected:**

- Clawed back: $100
- Earning status: clawed_back

### Step 5.3: Partial Clawback

```php
// Create new earning
$earning2 = CommissionEarning::create([
    'agent_type' => User::class,
    'agent_id' => $primaryRep->id,
    'commissionable_type' => 'Order',
    'commissionable_id' => 2,
    'base_amount' => 1000,
    'commission_amount' => 100,
    'rate' => 10,
    'rate_type' => 'percentage',
    'status' => CommissionEarning::STATUS_PENDING,
    'period' => now()->format('Y-m'),
    'earned_at' => now(),
]);

// Partial refund of $500 (50% of order)
$clawback = $clawbackService->partialClawback(
    $earning2,
    500, // Refund amount (not commission amount)
    CommissionClawback::REASON_REFUND
);

echo "Partial clawback: $" . $clawback->amount;
// Expected: $50 (50% of $100 commission)
```

### Step 5.4: Test Grace Period

```php
// Create old earning (beyond grace period)
$oldEarning = CommissionEarning::create([
    'agent_type' => User::class,
    'agent_id' => $primaryRep->id,
    'commissionable_type' => 'Order',
    'commissionable_id' => 3,
    'base_amount' => 1000,
    'commission_amount' => 100,
    'rate' => 10,
    'rate_type' => 'percentage',
    'status' => CommissionEarning::STATUS_PAID,
    'period' => now()->subMonths(2)->format('Y-m'),
    'earned_at' => now()->subMonths(2), // 2 months ago
]);

$result = $clawbackService->clawback($oldEarning, CommissionClawback::REASON_REFUND);

echo "Result: " . ($result ? "Clawed back" : "Blocked by grace period");
```

**Expected:** Blocked by grace period (if grace_period_days < 60)

---

## Test Flow 6: Payout Processing

### Step 6.1: Create Payable Earnings

```php
use SalesCommission\Models\CommissionEarning;

// Create multiple earnings as payable
$period = now()->format('Y-m');

for ($i = 1; $i <= 5; $i++) {
    CommissionEarning::create([
        'agent_type' => User::class,
        'agent_id' => $primaryRep->id,
        'commissionable_type' => 'Order',
        'commissionable_id' => 100 + $i,
        'base_amount' => 1000,
        'commission_amount' => 100,
        'rate' => 10,
        'rate_type' => 'percentage',
        'status' => CommissionEarning::STATUS_PAYABLE,
        'period' => $period,
        'earned_at' => now(),
    ]);
}

echo "Created 5 payable earnings totaling $500";
```

### Step 6.2: Generate Payout

```php
use SalesCommission\Services\PayoutService;

$payoutService = app(PayoutService::class);

$payout = $payoutService->generateForPeriod($period);

echo "Payout ID: " . $payout->id;
echo "Period: " . $payout->period;
echo "Total Amount: $" . $payout->total_amount;
echo "Earnings Count: " . $payout->total_earnings_count;
echo "Status: " . $payout->status;
```

**Expected:**

- Total Amount: $500
- Earnings Count: 5
- Status: pending_approval

### Step 6.3: Approve Payout

```php
$payout->approve(auth()->id() ?? 1);

echo "Status after approval: " . $payout->status;
echo "Approved at: " . $payout->approved_at;
```

**Expected:** Status = approved

### Step 6.4: Process Payout

```php
$processed = $payoutService->processApprovedPayouts();

echo "Processed payouts: " . $processed->count();

$payout->refresh();
echo "Final status: " . $payout->status;
echo "Processed at: " . $payout->processed_at;
```

**Expected:**

- Final status: paid
- All linked earnings marked as paid

### Step 6.5: Verify Earnings Updated

```php
$paidEarnings = CommissionEarning::where('payout_id', $payout->id)->get();

foreach ($paidEarnings as $earning) {
    echo "Earning {$earning->id}: {$earning->status} - Paid at: {$earning->paid_at}";
}
```

---

## Test Flow 7: Payout Statistics

```php
$stats = $payoutService->getPayoutStats($period);

echo "Total Paid: $" . $stats['total_paid'];
echo "Total Pending: $" . $stats['total_pending'];
echo "Payout Count: " . $stats['payout_count'];
```

---

## Test Flow 8: Agent Earnings & History

### Step 8.1: Get Agent Total Earnings

```php
use SalesCommission\Facades\Commission;

$total = Commission::getTotalEarnings($primaryRep);
echo "Total earnings (all time): $" . $total;

$monthlyTotal = Commission::getTotalEarnings($primaryRep, $period);
echo "Total earnings (this month): $" . $monthlyTotal;
```

### Step 8.2: Get Pending Earnings

```php
$pending = Commission::getPendingEarnings($primaryRep);
echo "Pending earnings: $" . $pending;
```

### Step 8.3: Using HasCommissions Trait

```php
// If User uses HasCommissions trait
$user = User::find($primaryRep->id);

echo "Commission earnings: " . $user->commissionEarnings()->count();
echo "Total earned: $" . $user->commissionEarnings()->sum('commission_amount');
```

---

## Test Flow 9: Events

### Step 9.1: Setup Event Listeners

Add to `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \SalesCommission\Events\CommissionEarned::class => [
        \App\Listeners\LogCommissionEarned::class,
    ],
    \SalesCommission\Events\PayoutProcessed::class => [
        \App\Listeners\LogPayoutProcessed::class,
    ],
];
```

### Step 9.2: Create Test Listener

```php
// app/Listeners/LogCommissionEarned.php
namespace App\Listeners;

use SalesCommission\Events\CommissionEarned;
use Illuminate\Support\Facades\Log;

class LogCommissionEarned
{
    public function handle(CommissionEarned $event)
    {
        Log::info("Commission earned", [
            'earning_id' => $event->earning->id,
            'amount' => $event->earning->commission_amount,
        ]);
    }
}
```

### Step 9.3: Trigger Event

```php
// Calculate a commission and check logs
$earning = Commission::forPlan($plan)->calculate($order, $agent);

// Check storage/logs/laravel.log for the event
```

---

## Test Flow 10: Database Verification

### Step 10.1: Check All Tables

```php
use Illuminate\Support\Facades\DB;

$tables = [
    'commission_plans',
    'commission_tiers',
    'commission_rules',
    'commission_earnings',
    'commission_splits',
    'commission_clawbacks',
    'payouts',
];

foreach ($tables as $table) {
    $count = DB::table($table)->count();
    echo "{$table}: {$count} records";
}
```

---

## Cleanup (Optional)

To reset test data:

```php
use Illuminate\Support\Facades\DB;

DB::table('commission_splits')->truncate();
DB::table('commission_clawbacks')->truncate();
DB::table('commission_earnings')->truncate();
DB::table('payouts')->truncate();
DB::table('commission_rules')->truncate();
DB::table('commission_tiers')->truncate();
DB::table('commission_plans')->truncate();

echo "All commission data cleared!";
```

---

## Summary Checklist

| Feature              | Test                                | Status |
| -------------------- | ----------------------------------- | ------ |
| Commission Plans     | Create, activate, set default       | ☐      |
| Commission Tiers     | Create tiers, test threshold lookup | ☐      |
| Commission Rules     | Percentage, fixed, conditions       | ☐      |
| Calculate Commission | Single, batch, with context         | ☐      |
| Team Splits          | 60/25/15 split, validation          | ☐      |
| Clawbacks            | Full, partial, grace period         | ☐      |
| Payouts              | Generate, approve, process          | ☐      |
| Payout Stats         | Total paid, pending, count          | ☐      |
| Agent Queries        | Earnings, pending, history          | ☐      |
| Events               | CommissionEarned, PayoutProcessed   | ☐      |

---

_Happy Testing! 🎉_
