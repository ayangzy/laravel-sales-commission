# Laravel Sales Commission Pro Documentation

## Table of Contents

1. [Getting Your License Key](#getting-your-license-key)
2. [Installation & Setup](#installation--setup)
3. [Filament Admin Panel](#filament-admin-panel)
4. [REST API Reference](#rest-api-reference)
5. [API Authentication](#api-authentication)
6. [Troubleshooting](#troubleshooting)

---

## Getting Your License Key

### How to Purchase

1. Visit our website or Gumroad store
2. Select the Pro license
3. Complete payment
4. Receive your license key via email immediately

### License Key Format

Your license key will look like this:

```
SCPRO-A3F2-B8C1-D4E5-F6A7
```

### License Tiers

| Tier         | Price         | Features                           |
| ------------ | ------------- | ---------------------------------- |
| **Pro**      | $99 one-time  | Admin Panel, API, Priority Support |
| **Lifetime** | $199 one-time | Everything + Lifetime Updates      |

---

## Installation & Setup

### Step 1: Install the Package

```bash
composer require ayangzy/laravel-sales-commission
```

### Step 2: Add Your License Key

Add to your `.env` file:

```env
SALES_COMMISSION_PRO_KEY=SCPRO-XXXX-XXXX-XXXX-XXXX
```

### Step 3: Publish Config (if not already done)

```bash
php artisan vendor:publish --tag="sales-commission-config"
php artisan vendor:publish --tag="sales-commission-migrations"
php artisan migrate
```

### Step 4: Install Filament (for Admin Panel)

```bash
composer require filament/filament:"^3.0"
php artisan filament:install --panels
```

### Step 5: Register the Plugin

Edit `app/Providers/Filament/AdminPanelProvider.php`:

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

### Step 6: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Filament Admin Panel

Once set up, navigate to `/admin` to access the commission management dashboard.

### Dashboard

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

### Getting an API Token

Users can generate API tokens using Laravel Sanctum:

```php
// In your controller or via API
$token = $user->createToken('commission-api')->plainTextToken;
```

Or create an endpoint in your app for token generation.

---

### Commission Plans API

#### List All Plans

```http
GET /api/commissions/plans
```

**Query Parameters:**

- `active_only` (boolean): Filter active plans only
- `search` (string): Search by name
- `per_page` (integer): Items per page (default: 15)

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Standard Plan",
      "slug": "standard",
      "is_active": true,
      "is_default": true,
      "tiers": [...],
      "rules": [...]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

#### Create a Plan

```http
POST /api/commissions/plans
```

**Body:**

```json
{
  "name": "Enterprise Plan",
  "slug": "enterprise",
  "description": "High-value client plan",
  "is_active": true,
  "is_default": false
}
```

#### Get a Single Plan

```http
GET /api/commissions/plans/{id}
```

#### Update a Plan

```http
PUT /api/commissions/plans/{id}
```

#### Delete a Plan

```http
DELETE /api/commissions/plans/{id}
```

#### Activate/Deactivate Plan

```http
POST /api/commissions/plans/{id}/activate
POST /api/commissions/plans/{id}/deactivate
```

#### Set as Default

```http
POST /api/commissions/plans/{id}/set-default
```

---

### Commission Tiers API

#### List Tiers for a Plan

```http
GET /api/commissions/plans/{plan_id}/tiers
```

#### Create a Tier

```http
POST /api/commissions/plans/{plan_id}/tiers
```

**Body:**

```json
{
  "name": "Gold",
  "min_threshold": 50000,
  "max_threshold": null,
  "rate": 10,
  "bonus_amount": 500
}
```

#### Update/Delete Tier

```http
PUT /api/commissions/tiers/{id}
DELETE /api/commissions/tiers/{id}
```

---

### Commission Rules API

#### List Rules for a Plan

```http
GET /api/commissions/plans/{plan_id}/rules
```

#### Create a Rule

```http
POST /api/commissions/plans/{plan_id}/rules
```

**Body:**

```json
{
  "name": "Base Commission",
  "type": "percentage",
  "value": 10,
  "priority": 1,
  "is_active": true
}
```

**Rule Types:**

- `percentage`: Percentage of sale amount
- `fixed`: Fixed amount per sale
- `tiered`: Use tier rate instead
- `bonus_threshold`: Bonus when threshold is met

---

### Commission Earnings API

#### List All Earnings

```http
GET /api/commissions/earnings
```

**Query Parameters:**

- `status`: pending, payable, paid, clawed_back
- `period`: YYYY-MM format
- `agent_id`: Filter by agent
- `plan_id`: Filter by plan
- `from_date`: Start date
- `to_date`: End date
- `per_page`: Items per page

#### Get Earnings by Agent

```http
GET /api/commissions/earnings/by-agent/{agent_id}
```

**Response includes summary:**

```json
{
  "success": true,
  "data": [...],
  "summary": {
    "pending": 500.00,
    "payable": 1200.00,
    "paid": 8500.00,
    "clawed_back": 150.00,
    "total_earned": 10350.00
  }
}
```

#### Get Earnings by Period

```http
GET /api/commissions/earnings/by-period/{period}
```

#### Mark Earning as Payable

```http
POST /api/commissions/earnings/{id}/mark-payable
```

---

### Payouts API

#### List All Payouts

```http
GET /api/commissions/payouts
```

**Query Parameters:**

- `status`: pending_approval, approved, paid, failed
- `period`: YYYY-MM format
- `from_date`, `to_date`: Date range

#### Get Pending Payouts

```http
GET /api/commissions/payouts/pending
```

#### Generate Payout for Period

```http
POST /api/commissions/payouts/generate
```

**Body:**

```json
{
  "period": "2026-01"
}
```

#### Approve Payout

```http
POST /api/commissions/payouts/{id}/approve
```

#### Reject Payout

```http
POST /api/commissions/payouts/{id}/reject
```

**Body:**

```json
{
  "reason": "Incomplete documentation"
}
```

#### Process Payout (Mark as Paid)

```http
POST /api/commissions/payouts/{id}/process
```

**Body:**

```json
{
  "payment_reference": "TXN-12345",
  "payment_method": "bank_transfer"
}
```

---

### Clawbacks API

#### List All Clawbacks

```http
GET /api/commissions/clawbacks
```

#### Create Full Clawback

```http
POST /api/commissions/clawbacks
```

**Body:**

```json
{
  "earning_id": 123,
  "reason": "refund",
  "notes": "Customer requested refund"
}
```

**Reasons:** refund, chargeback, cancellation, manual

#### Create Partial Clawback

```http
POST /api/commissions/clawbacks/partial
```

**Body:**

```json
{
  "earning_id": 123,
  "refund_amount": 250.0,
  "reason": "refund",
  "notes": "Partial refund issued"
}
```

#### Clawback All for Commissionable

```http
POST /api/commissions/clawbacks/for-commissionable
```

**Body:**

```json
{
  "commissionable_type": "App\\Models\\Order",
  "commissionable_id": "456",
  "reason": "cancellation"
}
```

---

### Agent API

#### Get Agent Earnings

```http
GET /api/commissions/agents/{agent_id}/earnings
```

#### Get Agent Total

```http
GET /api/commissions/agents/{agent_id}/total
```

**Response:**

```json
{
  "success": true,
  "data": {
    "agent_id": "1",
    "period": "all_time",
    "total_earned": 15000.0,
    "total_paid": 12000.0,
    "total_pending": 1500.0,
    "total_payable": 1500.0,
    "total_sales": 150000.0,
    "total_transactions": 45
  }
}
```

#### Get Agent Pending Earnings

```http
GET /api/commissions/agents/{agent_id}/pending
```

#### Get Agent Current Tier

```http
GET /api/commissions/agents/{agent_id}/tier?plan=standard
```

#### Get Agent Payouts

```http
GET /api/commissions/agents/{agent_id}/payouts
```

---

### Commission Splits API

#### List All Splits

```http
GET /api/commissions/splits
```

#### Calculate and Create Splits

```http
POST /api/commissions/splits/calculate
```

**Body:**

```json
{
  "earning_id": 123,
  "splits": [
    {
      "agent_id": "1",
      "agent_type": "App\\Models\\User",
      "percentage": 60,
      "role": "Closer"
    },
    {
      "agent_id": "2",
      "agent_type": "App\\Models\\User",
      "percentage": 40,
      "role": "Opener"
    }
  ]
}
```

**Note:** Percentages must total 100%.

---

### Calculate Commission API

#### Calculate Single Commission

```http
POST /api/commissions/calculate
```

**Body:**

```json
{
  "amount": 1000.0,
  "agent_id": "1",
  "agent_type": "App\\Models\\User",
  "plan": "standard",
  "commissionable_type": "App\\Models\\Order",
  "commissionable_id": "123",
  "meta": {
    "order_source": "website"
  }
}
```

#### Calculate Batch

```http
POST /api/commissions/calculate/batch
```

**Body:**

```json
{
  "plan": "standard",
  "items": [
    { "amount": 1000, "agent_id": "1", "agent_type": "App\\Models\\User" },
    { "amount": 2000, "agent_id": "2", "agent_type": "App\\Models\\User" }
  ]
}
```

#### Preview Commission (Without Saving)

```http
POST /api/commissions/calculate/preview
```

**Body:**

```json
{
  "amount": 1000.0,
  "plan": "standard"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "base_amount": 1000.0,
    "plan": { "id": 1, "name": "Standard", "slug": "standard" },
    "tier": { "name": "Bronze", "rate": 5 },
    "estimated_rate": 5,
    "estimated_commission": 50.0,
    "note": "This is an estimate."
  }
}
```

---

### Statistics API

#### Get Overview

```http
GET /api/commissions/stats/overview?period=2026-01
```

**Response:**

```json
{
  "success": true,
  "data": {
    "current_period": "2026-01",
    "earnings_this_period": 25000.0,
    "earnings_last_period": 22000.0,
    "earnings_change_percent": 13.64,
    "pending_payouts": 5000.0,
    "paid_this_month": 18000.0,
    "clawbacks_this_month": 500.0,
    "active_plans": 3,
    "net_commissions_this_month": 24500.0
  }
}
```

#### Get Earnings Over Time

```http
GET /api/commissions/stats/earnings?months=12&status=paid
```

#### Get Payout Statistics

```http
GET /api/commissions/stats/payouts?months=12
```

#### Get Top Earners

```http
GET /api/commissions/stats/top-earners?limit=10&period=2026-01
```

#### Get Earnings by Plan

```http
GET /api/commissions/stats/by-plan?period=2026-01
```

#### Get Trends (Daily)

```http
GET /api/commissions/stats/trends?days=30
```

---

## API Authentication

### Setting Up Laravel Sanctum

1. Install Sanctum (usually included with Laravel):

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

2. Add the trait to your User model:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

3. Create a token endpoint in your app:

```php
// routes/api.php
Route::post('/tokens/create', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    return ['token' => $user->createToken($request->device_name)->plainTextToken];
});
```

4. Use the token in API requests:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     https://your-app.com/api/commissions/plans
```

---

## Troubleshooting

### License Key Not Working

1. Ensure key starts with `SCPRO-`
2. Key must be at least 24 characters
3. Clear cache: `php artisan config:clear && php artisan cache:clear`
4. Check `.env` has no extra spaces

### Admin Panel Not Showing

1. Verify Filament is installed
2. Check plugin is registered in AdminPanelProvider
3. Clear views: `php artisan view:clear`
4. Ensure license key is valid

### API Returns 401 Unauthorized

1. Check Bearer token is included
2. Verify token is valid and not expired
3. Ensure Sanctum middleware is configured
4. Check user has permission

### Memory Issues

1. Increase PHP memory: `php -d memory_limit=1G artisan serve`
2. Clear all caches: `php artisan optimize:clear`
3. Remove package and reinstall

---

## Support

- **Documentation**: https://github.com/ayangzy/laravel-sales-commission
- **Issues**: https://github.com/ayangzy/laravel-sales-commission/issues
- **Email**: ayangefelix8@gmail.com
