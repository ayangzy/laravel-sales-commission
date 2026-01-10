<?php

namespace SalesCommission\Pro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClawbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'earning_id' => $this->earning_id,
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'processed_by' => $this->processed_by,
            'earning' => new CommissionEarningResource($this->whenLoaded('earning')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
