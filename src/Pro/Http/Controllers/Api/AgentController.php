<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\Payout;
use SalesCommission\Services\TierEvaluator;
use SalesCommission\Pro\Http\Resources\CommissionEarningResource;
use SalesCommission\Pro\Http\Resources\PayoutResource;

class AgentController extends Controller
{
    /**
     * Get all earnings for an agent.
     */
    public function earnings(Request $request, string $agent): JsonResponse
    {
        $earnings = CommissionEarning::where('agent_id', $agent)
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->has('period'), fn ($q) => $q->where('period', $request->period))
            ->orderBy('earned_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CommissionEarningResource::collection($earnings),
            'meta' => [
                'current_page' => $earnings->currentPage(),
                'last_page' => $earnings->lastPage(),
                'per_page' => $earnings->perPage(),
                'total' => $earnings->total(),
            ],
        ]);
    }

    /**
     * Get total earnings for an agent.
     */
    public function total(Request $request, string $agent): JsonResponse
    {
        $query = CommissionEarning::where('agent_id', $agent);

        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        $totals = $query->selectRaw('
            SUM(commission_amount) as total_earned,
            SUM(CASE WHEN status = "paid" THEN commission_amount ELSE 0 END) as total_paid,
            SUM(CASE WHEN status = "pending" THEN commission_amount ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = "payable" THEN commission_amount ELSE 0 END) as total_payable,
            SUM(base_amount) as total_sales,
            COUNT(*) as total_transactions
        ')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'agent_id' => $agent,
                'period' => $request->period ?? 'all_time',
                'total_earned' => (float) ($totals->total_earned ?? 0),
                'total_paid' => (float) ($totals->total_paid ?? 0),
                'total_pending' => (float) ($totals->total_pending ?? 0),
                'total_payable' => (float) ($totals->total_payable ?? 0),
                'total_sales' => (float) ($totals->total_sales ?? 0),
                'total_transactions' => (int) ($totals->total_transactions ?? 0),
            ],
        ]);
    }

    /**
     * Get pending earnings for an agent.
     */
    public function pending(string $agent): JsonResponse
    {
        $pending = CommissionEarning::where('agent_id', $agent)
            ->whereIn('status', [CommissionEarning::STATUS_PENDING, CommissionEarning::STATUS_PAYABLE])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'agent_id' => $agent,
                'pending_amount' => $pending->where('status', CommissionEarning::STATUS_PENDING)->sum('commission_amount'),
                'payable_amount' => $pending->where('status', CommissionEarning::STATUS_PAYABLE)->sum('commission_amount'),
                'total_pending' => $pending->sum('commission_amount'),
                'earnings_count' => $pending->count(),
            ],
        ]);
    }

    /**
     * Get current tier for an agent.
     */
    public function currentTier(Request $request, string $agent): JsonResponse
    {
        $planSlug = $request->get('plan', config('sales-commission.default_plan'));
        
        // Calculate cumulative sales
        $cumulativeSales = CommissionEarning::where('agent_id', $agent)
            ->when($request->has('period'), fn ($q) => $q->where('period', $request->period))
            ->sum('base_amount');

        // Get plan and find tier
        $plan = \SalesCommission\Models\CommissionPlan::where('slug', $planSlug)
            ->orWhere('id', $planSlug)
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.',
            ], 404);
        }

        $tier = $plan->findTierForAmount($cumulativeSales);

        return response()->json([
            'success' => true,
            'data' => [
                'agent_id' => $agent,
                'cumulative_sales' => $cumulativeSales,
                'current_tier' => $tier ? [
                    'id' => $tier->id,
                    'name' => $tier->name,
                    'rate' => $tier->rate,
                    'min_threshold' => $tier->min_threshold,
                    'max_threshold' => $tier->max_threshold,
                ] : null,
                'next_tier' => $plan->tiers()
                    ->where('min_threshold', '>', $cumulativeSales)
                    ->orderBy('min_threshold')
                    ->first(),
            ],
        ]);
    }

    /**
     * Get payouts for an agent.
     */
    public function payouts(Request $request, string $agent): JsonResponse
    {
        // Get payout IDs from earnings
        $payoutIds = CommissionEarning::where('agent_id', $agent)
            ->whereNotNull('payout_id')
            ->distinct()
            ->pluck('payout_id');

        $payouts = Payout::whereIn('id', $payoutIds)
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => PayoutResource::collection($payouts),
            'meta' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }
}
