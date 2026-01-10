<?php

namespace SalesCommission\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SalesCommission\Models\CommissionTier;

class TierAchieved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $agent,
        public CommissionTier $tier,
        public float $totalSales
    ) {}
}
