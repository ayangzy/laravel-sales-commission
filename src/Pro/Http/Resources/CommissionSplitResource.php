<?php

namespace SalesCommission\Pro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionSplitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'earning_id' => $this->earning_id,
            'agent_id' => $this->agent_id,
            'agent_type' => $this->agent_type,
            'split_percentage' => (float) $this->split_percentage,
            'split_amount' => (float) $this->split_amount,
            'role' => $this->role,
            'earning' => new CommissionEarningResource($this->whenLoaded('earning')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
