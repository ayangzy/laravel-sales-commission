<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\Payout;
use SalesCommission\Models\CommissionEarning;
use SalesCommission\Services\PayoutService;
use SalesCommission\Pro\Http\Resources\PayoutResource;

class PayoutController extends Controller
{
    protected PayoutService $payoutService;

    public function __construct(PayoutService $payoutService)
    {
        $this->payoutService = $payoutService;
    }

    /**
     * List all payouts with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $payouts = Payout::query()
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->has('period'), fn ($q) => $q->where('period', $request->period))
            ->when($request->has('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->has('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->to_date))
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

    /**
     * Get a specific payout.
     */
    public function show(Payout $payout): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new PayoutResource($payout->load('earnings')),
        ]);
    }

    /**
     * Create a manual payout.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'required|string',
            'earning_ids' => 'nullable|array',
            'earning_ids.*' => 'exists:commission_earnings,id',
        ]);

        $query = CommissionEarning::where('status', CommissionEarning::STATUS_PAYABLE)
            ->where('period', $validated['period']);

        if (!empty($validated['earning_ids'])) {
            $query->whereIn('id', $validated['earning_ids']);
        }

        $earnings = $query->get();

        if ($earnings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No payable earnings found for the specified period.',
            ], 422);
        }

        $payout = Payout::create([
            'period' => $validated['period'],
            'total_amount' => $earnings->sum('commission_amount'),
            'total_earnings_count' => $earnings->count(),
            'status' => Payout::STATUS_PENDING_APPROVAL,
        ]);

        $earnings->each(fn ($e) => $e->update(['payout_id' => $payout->id]));

        return response()->json([
            'success' => true,
            'message' => 'Payout created successfully.',
            'data' => new PayoutResource($payout),
        ], 201);
    }

    /**
     * Generate payout for a period.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|string',
        ]);

        $period = $validated['period'] ?? now()->format('Y-m');
        $payout = $this->payoutService->generateForPeriod($period);

        if (!$payout) {
            return response()->json([
                'success' => false,
                'message' => 'No payable earnings found for the specified period.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payout generated successfully.',
            'data' => new PayoutResource($payout),
        ], 201);
    }

    /**
     * Approve a payout.
     */
    public function approve(Request $request, Payout $payout): JsonResponse
    {
        if ($payout->status !== Payout::STATUS_PENDING_APPROVAL) {
            return response()->json([
                'success' => false,
                'message' => 'Payout cannot be approved in its current status.',
            ], 422);
        }

        $payout->approve($request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Payout approved successfully.',
            'data' => new PayoutResource($payout),
        ]);
    }

    /**
     * Reject a payout.
     */
    public function reject(Request $request, Payout $payout): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($payout->status !== Payout::STATUS_PENDING_APPROVAL) {
            return response()->json([
                'success' => false,
                'message' => 'Payout cannot be rejected in its current status.',
            ], 422);
        }

        $payout->update([
            'status' => Payout::STATUS_CANCELLED,
            'notes' => $validated['reason'] ?? null,
        ]);

        // Release earnings back to payable
        CommissionEarning::where('payout_id', $payout->id)
            ->update(['payout_id' => null, 'status' => CommissionEarning::STATUS_PAYABLE]);

        return response()->json([
            'success' => true,
            'message' => 'Payout rejected.',
            'data' => new PayoutResource($payout),
        ]);
    }

    /**
     * Process an approved payout (mark as paid).
     */
    public function process(Request $request, Payout $payout): JsonResponse
    {
        $validated = $request->validate([
            'payment_reference' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
        ]);

        if ($payout->status !== Payout::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved payouts can be processed.',
            ], 422);
        }

        $payout->markAsPaid([
            'reference' => $validated['payment_reference'] ?? null,
            'method' => $validated['payment_method'] ?? null,
            'processed_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payout processed successfully.',
            'data' => new PayoutResource($payout->fresh()),
        ]);
    }

    /**
     * Get pending payouts.
     */
    public function pending(): JsonResponse
    {
        $payouts = Payout::whereIn('status', [
            Payout::STATUS_PENDING_APPROVAL,
            Payout::STATUS_APPROVED,
        ])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => PayoutResource::collection($payouts),
            'summary' => [
                'total_pending_approval' => $payouts->where('status', Payout::STATUS_PENDING_APPROVAL)->sum('total_amount'),
                'total_approved' => $payouts->where('status', Payout::STATUS_APPROVED)->sum('total_amount'),
                'count' => $payouts->count(),
            ],
        ]);
    }
}
