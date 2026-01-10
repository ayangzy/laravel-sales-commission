<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Models\CommissionRule;
use SalesCommission\Pro\Http\Resources\CommissionRuleResource;

class CommissionRuleController extends Controller
{
    /**
     * List all rules for a plan.
     */
    public function index(CommissionPlan $plan): JsonResponse
    {
        $rules = $plan->rules()->orderBy('priority')->get();

        return response()->json([
            'success' => true,
            'data' => CommissionRuleResource::collection($rules),
        ]);
    }

    /**
     * Create a new rule for a plan.
     */
    public function store(Request $request, CommissionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:percentage,fixed,tiered,bonus_threshold',
            'value' => 'required|numeric',
            'conditions' => 'nullable|array',
            'priority' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $rule = $plan->rules()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Commission rule created successfully.',
            'data' => new CommissionRuleResource($rule),
        ], 201);
    }

    /**
     * Get a specific rule.
     */
    public function show(CommissionRule $rule): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CommissionRuleResource($rule->load('plan')),
        ]);
    }

    /**
     * Update a rule.
     */
    public function update(Request $request, CommissionRule $rule): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:percentage,fixed,tiered,bonus_threshold',
            'value' => 'sometimes|numeric',
            'conditions' => 'nullable|array',
            'priority' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Commission rule updated successfully.',
            'data' => new CommissionRuleResource($rule),
        ]);
    }

    /**
     * Delete a rule.
     */
    public function destroy(CommissionRule $rule): JsonResponse
    {
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commission rule deleted successfully.',
        ]);
    }
}
