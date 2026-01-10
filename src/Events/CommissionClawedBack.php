<?php

namespace SalesCommission\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SalesCommission\Models\CommissionClawback;

class CommissionClawedBack
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CommissionClawback $clawback
    ) {}
}
