<?php

namespace SalesCommission\Pro\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period' => $this->period,
            'total_amount' => (float) $this->total_amount,
            'total_earnings_count' => (int) $this->total_earnings_count,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'payment_reference' => $this->payment_reference,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'earnings' => CommissionEarningResource::collection($this->whenLoaded('earnings')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
