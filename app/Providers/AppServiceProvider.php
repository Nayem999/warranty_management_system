<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            $settings = Cache::remember('app_settings', 3600, function () {
                return Setting::pluck('setting_value', 'setting_name')->toArray();
            });

            config([
                'settings' => $settings + config('settings', []),
            ]);
        } catch (\Exception $e) {
            config([
                'settings' => config('settings', []),
            ]);
        }
    }
}
