<?php

namespace SalesCommission\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SalesCommission\Models\CommissionEarning calculate($commissionable, $agent = null)
 * @method static \SalesCommission\Services\CommissionCalculator forPlan(string|int $plan)
 * @method static array calculateBatch(iterable $commissionables, $agent = null)
 * @method static \SalesCommission\Services\SplitCalculator split($commissionable)
 * @method static \SalesCommission\Models\CommissionClawback clawback(\SalesCommission\Models\CommissionEarning $earning, string $reason, ?float $amount = null)
 * @method static float getTotalEarnings($agent, ?string $period = null)
 * @method static float getPendingEarnings($agent)
 * @method static string getCurrentTier($agent, $plan = null)
 *
 * @see \SalesCommission\Services\CommissionCalculator
 */
class Commission extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'commission';
    }
}
