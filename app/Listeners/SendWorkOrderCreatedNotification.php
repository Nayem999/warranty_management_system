<?php

namespace App\Listeners;

use App\Events\WorkOrderCreated;
use App\Services\Notifications\NotificationService;

class SendWorkOrderCreatedNotification
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(WorkOrderCreated $event): void
    {
        $this->notificationService->sendWorkOrderCreatedNotification($event->workOrder);
    }
}
