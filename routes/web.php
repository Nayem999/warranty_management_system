<?php

use App\Http\Controllers\Api\MicrosoftApiController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('optimize');
    Artisan::call('route:cache');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:cache');
    Artisan::call('storage:link');
    // Artisan::call('migrate --force');
    return '<h1>Routes and Cache Cleared Successfully! </h1>';
});

Route::prefix('microsoft_api')->group(function () {
    Route::get('/redirect', [MicrosoftApiController::class, 'redirect'])->name('microsoft.redirect');
    Route::get('/save_outlook_smtp_access_token', [MicrosoftApiController::class, 'callback'])->name('microsoft.callback');
    Route::get('/status', [MicrosoftApiController::class, 'status'])->name('microsoft.status');
    Route::post('/revoke', [MicrosoftApiController::class, 'revoke'])->name('microsoft.revoke');
});
