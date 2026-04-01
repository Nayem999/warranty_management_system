<?php

namespace App\Providers;

use App\Events\ClaimCreated;
use App\Events\WorkOrderCreated;
use App\Events\WorkOrderStatusUpdated;
use App\Listeners\SendClaimCreatedNotification;
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
        Event::listen(WorkOrderCreated::class, SendWorkOrderCreatedNotification::class);
        Event::listen(WorkOrderStatusUpdated::class, SendWorkOrderStatusNotification::class);
    }
}
