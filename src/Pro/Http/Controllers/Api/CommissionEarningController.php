<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Pro\Http\Resources\CommissionEarningResource;

class CommissionEarningController extends Controller
{
    /**
     * List all earnings with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $earnings = CommissionEarning::query()
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->has('period'), fn ($q) => $q->where('period', $request->period))
            ->when($request->has('agent_id'), fn ($q) => $q->where('agent_id', $request->agent_id))
            ->when($request->has('plan_id'), fn ($q) => $q->where('plan_id', $request->plan_id))
            ->when($request->has('from_date'), fn ($q) => $q->whereDate('earned_at', '>=', $request->from_date))
            ->when($request->has('to_date'), fn ($q) => $q->whereDate('earned_at', '<=', $request->to_date))
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
                'total_amount' => CommissionEarning::query()
                    ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
                    ->when($request->has('period'), fn ($q) => $q->where('period', $request->period))
                    ->sum('commission_amount'),
            ],
        ]);
    }

    /**
     * Get a specific earning.
     */
    public function show(CommissionEarning $earning): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CommissionEarningResource($earning->load(['plan', 'payout', 'clawbacks'])),
        ]);
    }

    /**
     * Mark an earning as payable.
     */
    public function markPayable(CommissionEarning $earning): JsonResponse
    {
        if ($earning->status !== CommissionEarning::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending earnings can be marked as payable.',
            ], 422);
        }

        $earning->update(['status' => CommissionEarning::STATUS_PAYABLE]);

        return response()->json([
            'success' => true,
            'message' => 'Earning marked as payable.',
            'data' => new CommissionEarningResource($earning),
        ]);
    }

    /**
     * Get earnings by agent.
     */
    public function byAgent(Request $request, string $agent): JsonResponse
    {
        $earnings = CommissionEarning::where('agent_id', $agent)
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->has('period'), fn ($q) => $q->where('period', $request->period))
            ->orderBy('earned_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        $totals = CommissionEarning::where('agent_id', $agent)
            ->selectRaw('status, SUM(commission_amount) as total, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return response()->json([
            'success' => true,
            'data' => CommissionEarningResource::collection($earnings),
            'summary' => [
                'pending' => $totals->get('pending')?->total ?? 0,
                'payable' => $totals->get('payable')?->total ?? 0,
                'paid' => $totals->get('paid')?->total ?? 0,
                'clawed_back' => $totals->get('clawed_back')?->total ?? 0,
                'total_earned' => $totals->sum('total'),
            ],
            'meta' => [
                'current_page' => $earnings->currentPage(),
                'last_page' => $earnings->lastPage(),
                'per_page' => $earnings->perPage(),
                'total' => $earnings->total(),
            ],
        ]);
    }

    /**
     * Get earnings by period.
     */
    public function byPeriod(Request $request, string $period): JsonResponse
    {
        $earnings = CommissionEarning::where('period', $period)
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('earned_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        $summary = CommissionEarning::where('period', $period)
            ->selectRaw('status, SUM(commission_amount) as total, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CommissionEarningResource::collection($earnings),
            'summary' => [
                'total_amount' => $summary->sum('total'),
                'total_count' => $summary->sum('count'),
                'by_status' => $summary->keyBy('status'),
            ],
            'meta' => [
                'current_page' => $earnings->currentPage(),
                'last_page' => $earnings->lastPage(),
                'per_page' => $earnings->perPage(),
                'total' => $earnings->total(),
            ],
        ]);
    }
}
