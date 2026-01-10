<?php

namespace SalesCommission\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SalesCommission\Models\Payout;

class PayoutProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payout $payout
    ) {}
}
