<?php

namespace App\Listeners;

use App\Events\ClaimCreated;
use App\Services\Notifications\NotificationService;

class SendClaimCreatedNotification
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(ClaimCreated $event): void
    {
        $this->notificationService->sendClaimCreatedNotification($event->claim);
    }
}
