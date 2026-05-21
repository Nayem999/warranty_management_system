<?php

namespace App\Listeners;

use App\Events\ClaimStatusUpdated;
use App\Services\Notifications\NotificationService;

class SendClaimStatusUpdatedNotification
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(ClaimStatusUpdated $event): void
    {
        $this->notificationService->sendClaimStatusUpdatedNotification($event->claim, $event->previousStatus);
    }
}
