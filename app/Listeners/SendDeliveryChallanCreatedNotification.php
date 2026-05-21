<?php

namespace App\Listeners;

use App\Events\DeliveryChallanCreated;
use App\Services\Notifications\NotificationService;

class SendDeliveryChallanCreatedNotification
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(DeliveryChallanCreated $event): void
    {
        $this->notificationService->sendDeliveryChallanCreatedNotification($event->deliveryChallan);
    }
}
