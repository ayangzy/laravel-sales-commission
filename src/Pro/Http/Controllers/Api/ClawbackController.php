<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\CommissionClawback;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Services\ClawbackService;
use SalesCommission\Pro\Http\Resources\ClawbackResource;

class ClawbackController extends Controller
{
    protected ClawbackService $clawbackService;

    public function __construct(ClawbackService $clawbackService)
    {
        $this->clawbackService = $clawbackService;
    }

    /**
     * List all clawbacks.
     */
    public function index(Request $request): JsonResponse
    {
        $clawbacks = CommissionClawback::query()
            ->when($request->has('reason'), fn ($q) => $q->where('reason', $request->reason))
            ->when($request->has('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->has('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->with('earning')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => ClawbackResource::collection($clawbacks),
            'meta' => [
                'current_page' => $clawbacks->currentPage(),
                'last_page' => $clawbacks->lastPage(),
                'per_page' => $clawbacks->perPage(),
                'total' => $clawbacks->total(),
                'total_amount' => CommissionClawback::sum('amount'),
            ],
        ]);
    }

    /**
     * Get a specific clawback.
     */
    public function show(CommissionClawback $clawback): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ClawbackResource($clawback->load('earning')),
        ]);
    }

    /**
     * Create a full clawback for an earning.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'earning_id' => 'required|exists:commission_earnings,id',
            'reason' => 'required|string|in:refund,chargeback,cancellation,manual',
            'notes' => 'nullable|string|max:500',
        ]);

        $earning = CommissionEarning::findOrFail($validated['earning_id']);

        $clawback = $this->clawbackService->clawback(
            $earning,
            $validated['reason'],
            null,
            $validated['notes'] ?? null
        );

        if (!$clawback) {
            return response()->json([
                'success' => false,
                'message' => 'Clawback could not be processed. Check grace period settings.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clawback created successfully.',
            'data' => new ClawbackResource($clawback),
        ], 201);
    }

    /**
     * Create a partial clawback.
     */
    public function storePartial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'earning_id' => 'required|exists:commission_earnings,id',
            'refund_amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|in:refund,chargeback,cancellation,manual',
            'notes' => 'nullable|string|max:500',
        ]);

        $earning = CommissionEarning::findOrFail($validated['earning_id']);

        $clawback = $this->clawbackService->partialClawback(
            $earning,
            $validated['refund_amount'],
            $validated['reason'] ?? CommissionClawback::REASON_REFUND,
            $validated['notes'] ?? null
        );

        if (!$clawback) {
            return response()->json([
                'success' => false,
                'message' => 'Partial clawback could not be processed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Partial clawback created successfully.',
            'data' => new ClawbackResource($clawback),
        ], 201);
    }

    /**
     * Clawback all earnings for a commissionable item.
     */
    public function forCommissionable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commissionable_type' => 'required|string',
            'commissionable_id' => 'required|string',
            'reason' => 'nullable|string|in:refund,chargeback,cancellation,manual',
            'notes' => 'nullable|string|max:500',
        ]);

        $clawbacks = $this->clawbackService->clawbackForCommissionable(
            $validated['commissionable_type'],
            $validated['commissionable_id'],
            $validated['reason'] ?? CommissionClawback::REASON_REFUND,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => count($clawbacks) . ' clawback(s) created successfully.',
            'data' => ClawbackResource::collection(collect($clawbacks)),
        ], 201);
    }
}
