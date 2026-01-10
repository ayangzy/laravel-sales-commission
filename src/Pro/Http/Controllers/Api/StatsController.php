<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\Payout;
use SalesCommission\Models\CommissionClawback;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get overall commission statistics.
     */
    public function overview(Request $request): JsonResponse
    {
        $period = $request->get('period', now()->format('Y-m'));

        $currentEarnings = CommissionEarning::where('period', $period)->sum('commission_amount');
        $lastPeriod = now()->subMonth()->format('Y-m');
        $lastEarnings = CommissionEarning::where('period', $lastPeriod)->sum('commission_amount');

        $pendingPayouts = Payout::whereIn('status', [Payout::STATUS_PENDING_APPROVAL, Payout::STATUS_APPROVED])
            ->sum('total_amount');

        $paidThisMonth = Payout::where('status', Payout::STATUS_PAID)
            ->whereMonth('processed_at', now()->month)
            ->whereYear('processed_at', now()->year)
            ->sum('total_amount');

        $clawbacksThisMonth = CommissionClawback::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $activePlans = CommissionPlan::where('is_active', true)->count();

        $changePercent = $lastEarnings > 0 
            ? (($currentEarnings - $lastEarnings) / $lastEarnings) * 100 
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'current_period' => $period,
                'earnings_this_period' => round($currentEarnings, 2),
                'earnings_last_period' => round($lastEarnings, 2),
                'earnings_change_percent' => round($changePercent, 2),
                'pending_payouts' => round($pendingPayouts, 2),
                'paid_this_month' => round($paidThisMonth, 2),
                'clawbacks_this_month' => round($clawbacksThisMonth, 2),
                'active_plans' => $activePlans,
                'net_commissions_this_month' => round($currentEarnings - $clawbacksThisMonth, 2),
            ],
        ]);
    }

    /**
     * Get earnings statistics over time.
     */
    public function earnings(Request $request): JsonResponse
    {
        $months = $request->integer('months', 12);
        $status = $request->get('status');

        $earnings = CommissionEarning::query()
            ->selectRaw('period, SUM(commission_amount) as total, COUNT(*) as count')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit($months)
            ->get();

        $totalAll = CommissionEarning::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->sum('commission_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'periods' => $earnings->reverse()->values(),
                'total_all_time' => round($totalAll, 2),
                'average_per_period' => $earnings->count() > 0 
                    ? round($earnings->avg('total'), 2) 
                    : 0,
            ],
        ]);
    }

    /**
     * Get payout statistics.
     */
    public function payouts(Request $request): JsonResponse
    {
        $months = $request->integer('months', 12);

        $payouts = Payout::query()
            ->selectRaw('
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                status,
                SUM(total_amount) as total,
                COUNT(*) as count
            ')
            ->groupBy('year', 'month', 'status')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit($months * 4)
            ->get();

        $statusSummary = Payout::selectRaw('status, SUM(total_amount) as total, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return response()->json([
            'success' => true,
            'data' => [
                'by_period' => $payouts,
                'by_status' => [
                    'pending_approval' => [
                        'amount' => round($statusSummary->get('pending_approval')?->total ?? 0, 2),
                        'count' => $statusSummary->get('pending_approval')?->count ?? 0,
                    ],
                    'approved' => [
                        'amount' => round($statusSummary->get('approved')?->total ?? 0, 2),
                        'count' => $statusSummary->get('approved')?->count ?? 0,
                    ],
                    'paid' => [
                        'amount' => round($statusSummary->get('paid')?->total ?? 0, 2),
                        'count' => $statusSummary->get('paid')?->count ?? 0,
                    ],
                    'failed' => [
                        'amount' => round($statusSummary->get('failed')?->total ?? 0, 2),
                        'count' => $statusSummary->get('failed')?->count ?? 0,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get top earning agents.
     */
    public function topEarners(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $period = $request->get('period');

        $query = CommissionEarning::query()
            ->selectRaw('agent_id, agent_type, SUM(commission_amount) as total_earned, COUNT(*) as transactions')
            ->when($period, fn ($q) => $q->where('period', $period))
            ->groupBy('agent_id', 'agent_type')
            ->orderBy('total_earned', 'desc')
            ->limit($limit);

        $topEarners = $query->get();

        return response()->json([
            'success' => true,
            'data' => $topEarners->map(fn ($earner) => [
                'agent_id' => $earner->agent_id,
                'agent_type' => $earner->agent_type,
                'total_earned' => round($earner->total_earned, 2),
                'transactions' => $earner->transactions,
                'rank' => $topEarners->search($earner) + 1,
            ]),
        ]);
    }

    /**
     * Get earnings by plan.
     */
    public function byPlan(Request $request): JsonResponse
    {
        $period = $request->get('period');

        $byPlan = CommissionEarning::query()
            ->selectRaw('plan_id, SUM(commission_amount) as total, SUM(base_amount) as sales, COUNT(*) as count')
            ->when($period, fn ($q) => $q->where('period', $period))
            ->groupBy('plan_id')
            ->get();

        $plans = CommissionPlan::whereIn('id', $byPlan->pluck('plan_id'))->get()->keyBy('id');

        return response()->json([
            'success' => true,
            'data' => $byPlan->map(fn ($item) => [
                'plan_id' => $item->plan_id,
                'plan_name' => $plans->get($item->plan_id)?->name ?? 'Unknown',
                'plan_slug' => $plans->get($item->plan_id)?->slug ?? null,
                'total_commission' => round($item->total, 2),
                'total_sales' => round($item->sales, 2),
                'effective_rate' => $item->sales > 0 
                    ? round(($item->total / $item->sales) * 100, 2) 
                    : 0,
                'transaction_count' => $item->count,
            ]),
        ]);
    }

    /**
     * Get earnings trends.
     */
    public function trends(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);

        $dailyEarnings = CommissionEarning::query()
            ->selectRaw('DATE(earned_at) as date, SUM(commission_amount) as total, COUNT(*) as count')
            ->where('earned_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dailyPayouts = Payout::query()
            ->selectRaw('DATE(processed_at) as date, SUM(total_amount) as total')
            ->where('status', Payout::STATUS_PAID)
            ->where('processed_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dailyClawbacks = CommissionClawback::query()
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'daily_data' => $dailyEarnings->map(fn ($day) => [
                    'date' => $day->date,
                    'earnings' => round($day->total, 2),
                    'transactions' => $day->count,
                    'payouts' => round($dailyPayouts->get($day->date)?->total ?? 0, 2),
                    'clawbacks' => round($dailyClawbacks->get($day->date)?->total ?? 0, 2),
                ]),
                'totals' => [
                    'earnings' => round($dailyEarnings->sum('total'), 2),
                    'transactions' => $dailyEarnings->sum('count'),
                    'payouts' => round($dailyPayouts->sum('total'), 2),
                    'clawbacks' => round($dailyClawbacks->sum('total'), 2),
                ],
            ],
        ]);
    }
}
