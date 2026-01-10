<?php

namespace SalesCommission\Pro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'is_default' => (bool) $this->is_default,
            'tiers' => CommissionTierResource::collection($this->whenLoaded('tiers')),
            'rules' => CommissionRuleResource::collection($this->whenLoaded('rules')),
            'tiers_count' => $this->when(!$this->relationLoaded('tiers'), $this->tiers_count ?? null),
            'rules_count' => $this->when(!$this->relationLoaded('rules'), $this->rules_count ?? null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
