<?php

namespace App\Providers;

use App\Events\ClaimCreated;
use App\Events\ClaimStatusUpdated;
use App\Events\DeliveryChallanCreated;
use App\Events\WorkOrderCreated;
use App\Events\WorkOrderStatusUpdated;
use App\Listeners\SendClaimCreatedNotification;
use App\Listeners\SendClaimStatusUpdatedNotification;
use App\Listeners\SendDeliveryChallanCreatedNotification;
use App\Listeners\SendWorkOrderCreatedNotification;
use App\Listeners\SendWorkOrderStatusNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(ClaimCreated::class, SendClaimCreatedNotification::class);
        Event::listen(ClaimStatusUpdated::class, SendClaimStatusUpdatedNotification::class);
        Event::listen(DeliveryChallanCreated::class, SendDeliveryChallanCreatedNotification::class);
        Event::listen(WorkOrderCreated::class, SendWorkOrderCreatedNotification::class);
        Event::listen(WorkOrderStatusUpdated::class, SendWorkOrderStatusNotification::class);
    }
}
