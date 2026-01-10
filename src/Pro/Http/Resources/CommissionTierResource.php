<?php

namespace SalesCommission\Pro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'name' => $this->name,
            'min_threshold' => (float) $this->min_threshold,
            'max_threshold' => $this->max_threshold ? (float) $this->max_threshold : null,
            'rate' => (float) $this->rate,
            'bonus_amount' => $this->bonus_amount ? (float) $this->bonus_amount : null,
            'plan' => new CommissionPlanResource($this->whenLoaded('plan')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
