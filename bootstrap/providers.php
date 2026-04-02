<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use Illuminate\View\ViewServiceProvider;

return [
    ViewServiceProvider::class,
    AppServiceProvider::class,
    EventServiceProvider::class,
];
