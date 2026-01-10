<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Pro\Http\Resources\CommissionPlanResource;

class CommissionPlanController extends Controller
{
    /**
     * List all commission plans.
     */
    public function index(Request $request): JsonResponse
    {
        $plans = CommissionPlan::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->has('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->with(['tiers', 'rules'])
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CommissionPlanResource::collection($plans),
            'meta' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
            ],
        ]);
    }

    /**
     * Create a new commission plan.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:commission_plans,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $plan = CommissionPlan::create($validated);

        if ($request->boolean('is_default')) {
            CommissionPlan::where('id', '!=', $plan->id)->update(['is_default' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commission plan created successfully.',
            'data' => new CommissionPlanResource($plan->load(['tiers', 'rules'])),
        ], 201);
    }

    /**
     * Get a specific commission plan.
     */
    public function show(CommissionPlan $plan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CommissionPlanResource($plan->load(['tiers', 'rules'])),
        ]);
    }

    /**
     * Update a commission plan.
     */
    public function update(Request $request, CommissionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:commission_plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $plan->update($validated);

        if ($request->boolean('is_default')) {
            CommissionPlan::where('id', '!=', $plan->id)->update(['is_default' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commission plan updated successfully.',
            'data' => new CommissionPlanResource($plan->fresh(['tiers', 'rules'])),
        ]);
    }

    /**
     * Delete a commission plan.
     */
    public function destroy(CommissionPlan $plan): JsonResponse
    {
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commission plan deleted successfully.',
        ]);
    }

    /**
     * Activate a commission plan.
     */
    public function activate(CommissionPlan $plan): JsonResponse
    {
        $plan->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Commission plan activated.',
            'data' => new CommissionPlanResource($plan),
        ]);
    }

    /**
     * Deactivate a commission plan.
     */
    public function deactivate(CommissionPlan $plan): JsonResponse
    {
        $plan->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Commission plan deactivated.',
            'data' => new CommissionPlanResource($plan),
        ]);
    }

    /**
     * Set plan as default.
     */
    public function setDefault(CommissionPlan $plan): JsonResponse
    {
        CommissionPlan::where('is_default', true)->update(['is_default' => false]);
        $plan->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Commission plan set as default.',
            'data' => new CommissionPlanResource($plan),
        ]);
    }
}
