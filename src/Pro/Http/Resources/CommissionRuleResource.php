<?php

namespace SalesCommission\Pro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'name' => $this->name,
            'type' => $this->type,
            'value' => (float) $this->value,
            'conditions' => $this->conditions,
            'priority' => (int) $this->priority,
            'is_active' => (bool) $this->is_active,
            'plan' => new CommissionPlanResource($this->whenLoaded('plan')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
