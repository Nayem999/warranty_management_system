<?php

namespace App\Listeners;

use App\Events\WorkOrderStatusUpdated;
use App\Services\Notifications\NotificationService;

class SendWorkOrderStatusNotification
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(WorkOrderStatusUpdated $event): void
    {
        $this->notificationService->sendWorkOrderStatusNotification(
            $event->workOrder,
            $event->previousStatus
        );
    }
}
