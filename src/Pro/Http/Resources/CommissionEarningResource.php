<?php

namespace SalesCommission\Pro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionEarningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'agent_type' => $this->agent_type,
            'plan_id' => $this->plan_id,
            'tier_id' => $this->tier_id,
            'payout_id' => $this->payout_id,
            'commissionable_type' => $this->commissionable_type,
            'commissionable_id' => $this->commissionable_id,
            'base_amount' => (float) $this->base_amount,
            'commission_amount' => (float) $this->commission_amount,
            'rate' => (float) $this->rate,
            'status' => $this->status,
            'period' => $this->period,
            'meta' => $this->meta,
            'earned_at' => $this->earned_at?->toISOString(),
            'plan' => new CommissionPlanResource($this->whenLoaded('plan')),
            'payout' => new PayoutResource($this->whenLoaded('payout')),
            'clawbacks' => ClawbackResource::collection($this->whenLoaded('clawbacks')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
