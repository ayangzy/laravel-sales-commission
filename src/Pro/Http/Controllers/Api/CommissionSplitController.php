<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\CommissionSplit;
use SalesCommission\Pro\Http\Resources\CommissionSplitResource;

class CommissionSplitController extends Controller
{

    /**
     * List all commission splits.
     */
    public function index(Request $request): JsonResponse
    {
        $splits = CommissionSplit::query()
            ->when($request->has('earning_id'), fn ($q) => $q->where('earning_id', $request->earning_id))
            ->when($request->has('agent_id'), fn ($q) => $q->where('agent_id', $request->agent_id))
            ->with('earning')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CommissionSplitResource::collection($splits),
            'meta' => [
                'current_page' => $splits->currentPage(),
                'last_page' => $splits->lastPage(),
                'per_page' => $splits->perPage(),
                'total' => $splits->total(),
            ],
        ]);
    }

    /**
     * Get a specific split.
     */
    public function show(CommissionSplit $split): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CommissionSplitResource($split->load('earning')),
        ]);
    }

    /**
     * Calculate and create splits for an earning.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'earning_id' => 'required|exists:commission_earnings,id',
            'splits' => 'required|array|min:2',
            'splits.*.agent_id' => 'required|string',
            'splits.*.agent_type' => 'required|string',
            'splits.*.percentage' => 'required|numeric|min:0|max:100',
            'splits.*.role' => 'nullable|string|max:100',
        ]);

        // Validate percentages total 100%
        $totalPercentage = collect($validated['splits'])->sum('percentage');
        if (abs($totalPercentage - 100) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Split percentages must total 100%. Current total: ' . $totalPercentage . '%',
            ], 422);
        }

        $earning = \SalesCommission\Models\CommissionEarning::findOrFail($validated['earning_id']);
        $createdSplits = [];

        foreach ($validated['splits'] as $splitData) {
            $splitAmount = ($splitData['percentage'] / 100) * $earning->commission_amount;

            $split = CommissionSplit::create([
                'earning_id' => $earning->id,
                'agent_type' => $splitData['agent_type'],
                'agent_id' => $splitData['agent_id'],
                'split_percentage' => $splitData['percentage'],
                'split_amount' => $splitAmount,
                'role' => $splitData['role'] ?? null,
            ]);

            $createdSplits[] = $split;
        }

        return response()->json([
            'success' => true,
            'message' => count($createdSplits) . ' splits created successfully.',
            'data' => CommissionSplitResource::collection(collect($createdSplits)),
        ], 201);
    }
}
