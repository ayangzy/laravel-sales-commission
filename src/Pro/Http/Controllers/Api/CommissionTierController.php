<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionTier;
use SalesCommission\Pro\Http\Resources\CommissionTierResource;

class CommissionTierController extends Controller
{
    /**
     * List all tiers for a plan.
     */
    public function index(CommissionPlan $plan): JsonResponse
    {
        $tiers = $plan->tiers()->orderBy('min_threshold')->get();

        return response()->json([
            'success' => true,
            'data' => CommissionTierResource::collection($tiers),
        ]);
    }

    /**
     * Create a new tier for a plan.
     */
    public function store(Request $request, CommissionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_threshold' => 'required|numeric|min:0',
            'max_threshold' => 'nullable|numeric|gt:min_threshold',
            'rate' => 'required|numeric|min:0|max:100',
            'bonus_amount' => 'nullable|numeric|min:0',
        ]);

        $tier = $plan->tiers()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Commission tier created successfully.',
            'data' => new CommissionTierResource($tier),
        ], 201);
    }

    /**
     * Get a specific tier.
     */
    public function show(CommissionTier $tier): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CommissionTierResource($tier->load('plan')),
        ]);
    }

    /**
     * Update a tier.
     */
    public function update(Request $request, CommissionTier $tier): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'min_threshold' => 'sometimes|numeric|min:0',
            'max_threshold' => 'nullable|numeric',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'bonus_amount' => 'nullable|numeric|min:0',
        ]);

        $tier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Commission tier updated successfully.',
            'data' => new CommissionTierResource($tier),
        ]);
    }

    /**
     * Delete a tier.
     */
    public function destroy(CommissionTier $tier): JsonResponse
    {
        $tier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commission tier deleted successfully.',
        ]);
    }
}
