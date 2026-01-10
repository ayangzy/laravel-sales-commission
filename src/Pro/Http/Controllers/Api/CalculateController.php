<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalesCommission\Services\CommissionCalculator;
use SalesCommission\Models\CommissionPlan;
use SalesCommission\Pro\Http\Resources\CommissionEarningResource;

class CalculateController extends Controller
{
    protected CommissionCalculator $calculator;

    public function __construct(CommissionCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Calculate commission for a single item.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'agent_id' => 'required|string',
            'agent_type' => 'required|string',
            'plan' => 'nullable|string',
            'commissionable_type' => 'nullable|string',
            'commissionable_id' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        // Check if plan exists (if specified) or default plan exists
        $plan = null;
        if (!empty($validated['plan'])) {
            $plan = CommissionPlan::where('slug', $validated['plan'])
                ->orWhere('id', $validated['plan'])
                ->orWhere('name', $validated['plan'])
                ->first();
            
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => "Commission plan '{$validated['plan']}' not found.",
                    'error_code' => 'PLAN_NOT_FOUND',
                ], 404);
            }
        } else {
            $plan = CommissionPlan::where('is_default', true)->first();
            
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No default commission plan configured. Please create a plan and set it as default, or specify a plan in the request.',
                    'error_code' => 'NO_DEFAULT_PLAN',
                ], 422);
            }
        }

        // Get agent
        $agentModel = $validated['agent_type'];
        
        if (!class_exists($agentModel)) {
            return response()->json([
                'success' => false,
                'message' => "Agent model '{$agentModel}' does not exist.",
                'error_code' => 'INVALID_AGENT_TYPE',
            ], 422);
        }

        $agent = app($agentModel)->find($validated['agent_id']);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found with ID: ' . $validated['agent_id'],
                'error_code' => 'AGENT_NOT_FOUND',
            ], 404);
        }

        // Create a simple commissionable object
        $commissionable = new class($validated) {
            private array $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function getCommissionableAmount(): float
            {
                return $this->data['amount'];
            }

            public function getCommissionAgent()
            {
                return null;
            }

            public function getCommissionDate()
            {
                return now();
            }

            public function getCommissionMeta(): array
            {
                return $this->data['meta'] ?? [];
            }

            public function getKey()
            {
                return $this->data['commissionable_id'] ?? uniqid();
            }

            public function getMorphClass(): string
            {
                return $this->data['commissionable_type'] ?? 'ApiCalculation';
            }
        };

        try {
            $earning = $this->calculator->forPlan($plan)->calculate($commissionable, $agent);

            if (!$earning) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commission could not be calculated. Please check your plan has rules configured.',
                    'error_code' => 'CALCULATION_FAILED',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commission calculated and saved.',
                'data' => new CommissionEarningResource($earning),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating commission: ' . $e->getMessage(),
                'error_code' => 'CALCULATION_ERROR',
            ], 500);
        }
    }

    /**
     * Calculate commissions for multiple items.
     */
    public function calculateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.agent_id' => 'required|string',
            'items.*.agent_type' => 'required|string',
            'plan' => 'nullable|string',
        ]);

        $agentModel = config('sales-commission.models.agent', 'App\\Models\\User');
        $results = [];
        $errors = [];

        foreach ($validated['items'] as $index => $item) {
            $agent = app($agentModel)->find($item['agent_id']);

            if (!$agent) {
                $errors[] = [
                    'index' => $index,
                    'message' => 'Agent not found: ' . $item['agent_id'],
                ];
                continue;
            }

            $commissionable = new class($item) {
                private array $data;

                public function __construct(array $data)
                {
                    $this->data = $data;
                }

                public function getCommissionableAmount(): float
                {
                    return $this->data['amount'];
                }

                public function getCommissionAgent()
                {
                    return null;
                }

                public function getCommissionDate()
                {
                    return now();
                }

                public function getCommissionMeta(): array
                {
                    return $this->data['meta'] ?? [];
                }

                public function getKey()
                {
                    return $this->data['commissionable_id'] ?? uniqid();
                }

                public function getMorphClass(): string
                {
                    return $this->data['commissionable_type'] ?? 'ApiCalculation';
                }
            };

            $calculator = $this->calculator;

            if (!empty($validated['plan'])) {
                $calculator = $calculator->forPlan($validated['plan']);
            }

            $earning = $calculator->calculate($commissionable, $agent);
            $results[] = $earning;
        }

        return response()->json([
            'success' => true,
            'message' => count($results) . ' commission(s) calculated.',
            'data' => CommissionEarningResource::collection(collect($results)),
            'errors' => $errors,
        ], 201);
    }

    /**
     * Preview commission calculation without saving.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'plan' => 'nullable|string',
            'agent_id' => 'nullable|string',
        ]);

        $planQuery = CommissionPlan::query();

        if (!empty($validated['plan'])) {
            $planQuery->where('slug', $validated['plan'])->orWhere('id', $validated['plan']);
        } else {
            $planQuery->where('is_default', true);
        }

        $plan = $planQuery->with(['tiers', 'rules'])->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'No commission plan found.',
            ], 404);
        }

        $amount = $validated['amount'];
        $tier = $plan->findTierForAmount($amount);
        
        // Simple percentage calculation from tier
        $rate = $tier?->rate ?? 0;
        $commissionAmount = ($rate / 100) * $amount;

        // Apply rules
        foreach ($plan->rules()->where('is_active', true)->orderBy('priority')->get() as $rule) {
            if ($rule->type === 'percentage') {
                $commissionAmount = ($rule->value / 100) * $amount;
            } elseif ($rule->type === 'fixed') {
                $commissionAmount = $rule->value;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'base_amount' => $amount,
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ],
                'tier' => $tier ? [
                    'name' => $tier->name,
                    'rate' => $tier->rate,
                ] : null,
                'estimated_rate' => $rate,
                'estimated_commission' => round($commissionAmount, 2),
                'note' => 'This is an estimate. Actual commission may vary based on agent tier and conditions.',
            ],
        ]);
    }
}
