<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\MemorizeReportController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceCenterController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/brands/list', [BrandController::class, 'brands_list']);
    Route::get('/service-centers/list', [ServiceCenterController::class, 'service_centers_list']);
    Route::get('/service-centers/by-brand', [ServiceCenterController::class, 'byBrand']);
    Route::get('/claims/track/{claimNumber}', [ClaimController::class, 'track']);
    Route::post('/claims/feedback/{token}', [ClaimController::class, 'submitFeedback']);
    Route::get('/claims/{id}/feedback-link', [ClaimController::class, 'getFeedbackLink']);
    Route::get('/settings/{key}', [SettingController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

        Route::apiResource('users', UserController::class);
        Route::get('/users/trashed', [UserController::class, 'trashed']);
        Route::post('/users/{id}/restore', [UserController::class, 'restore']);
        Route::get('/users/{id}/brand-access', [UserController::class, 'getBrandAccess']);
        Route::post('/users/{id}/brand-access', [UserController::class, 'assignBrandAccess']);
        Route::delete('/users/{id}/brand-access/{brandId}', [UserController::class, 'revokeBrandAccess']);
        Route::get('/users/{id}/service-center-access', [UserController::class, 'getServiceCenterAccess']);
        Route::post('/users/{id}/service-center-access', [UserController::class, 'assignServiceCenterAccess']);
        Route::delete('/users/{id}/service-center-access/{serviceCenterId}', [UserController::class, 'revokeServiceCenterAccess']);
        Route::put('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('/users/{id}/permissions', [UserController::class, 'getPermissions']);
        Route::post('/users/{id}/permissions', [UserController::class, 'assignPermissions']);
        Route::delete('/users/{id}/permissions', [UserController::class, 'removePersonalPermissions']);

        Route::apiResource('roles', RoleController::class);
        Route::get('/roles/permissions/list', [RoleController::class, 'permissionsList']);

        Route::apiResource('brands', BrandController::class);
        Route::get('/brands/{id}/stats', [BrandController::class, 'stats']);
        Route::put('/brands/{id}/toggle-status', [BrandController::class, 'toggleStatus']);

        Route::get('/categories/parents', [CategoryController::class, 'parents']);
        Route::apiResource('categories', CategoryController::class);
        Route::get('/categories/{id}/subcategories', [CategoryController::class, 'subcategories']);
        Route::put('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);

        Route::apiResource('customers', CustomerController::class);

        Route::apiResource('products', ProductController::class);
        Route::get('/products/check/{serial}', [ProductController::class, 'checkSerial']);
        Route::post('/products/import', [ProductController::class, 'import']);
        Route::get('/products/import/sample', [ProductController::class, 'importSample']);

        Route::apiResource('claims', ClaimController::class);
        Route::get('/claims/track/{claimNumber}', [ClaimController::class, 'track']);
        Route::put('/claims/{id}/close', [ClaimController::class, 'close']);
        Route::get('/claims/{id}/activity-timeline', [ClaimController::class, 'activityTimeline']);

        // Route::apiResource('work-orders', WorkOrderController::class)->except(['store']);

        Route::apiResource('service-centers', ServiceCenterController::class);
        Route::put('/service-centers/{id}/toggle-status', [ServiceCenterController::class, 'toggleStatus']);

        Route::apiResource('couriers', CourierController::class);
        Route::put('/couriers/{id}/toggle-status', [CourierController::class, 'toggleStatus']);

        Route::apiResource('parts', PartController::class);
        Route::put('/parts/{id}/toggle-status', [PartController::class, 'toggleStatus']);
        Route::get('/work-order-history', [PartController::class, 'workOrderUsageHistory']);

        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'upsert']);
        Route::put('/settings', [SettingController::class, 'updateAll']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);

        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/client-dashboard', [DashboardController::class, 'clientDashboard']);

        Route::get('/exports/claims', [ExportController::class, 'downloadClaims']);
        Route::get('/exports/products', [ExportController::class, 'downloadProducts']);
        Route::get('/exports/work-orders', [ExportController::class, 'downloadWorkOrders']);

        Route::get('/activity-logs', [ActivityLogController::class, 'index']);

        Route::apiResource('memorize-reports', MemorizeReportController::class);
    });
});
