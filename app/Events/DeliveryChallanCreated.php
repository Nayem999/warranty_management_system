<?php

namespace App\Events;

use App\Models\DeliveryChallan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryChallanCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DeliveryChallan $deliveryChallan
    ) {}
}
