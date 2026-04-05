<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceCenterController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarrantyController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/auth/login-with-otp', [AuthController::class, 'loginWithOtp']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/warranties/check/{serial}', [WarrantyController::class, 'checkSerial']);
    Route::post('/work-orders/feedback/{token}', [WorkOrderController::class, 'submitFeedback']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

        Route::apiResource('users', UserController::class);
        Route::post('/users/{id}/restore', [UserController::class, 'restore']);
        Route::get('/users/{id}/brand-access', [UserController::class, 'getBrandAccess']);
        Route::post('/users/{id}/brand-access', [UserController::class, 'assignBrandAccess']);
        Route::delete('/users/{id}/brand-access/{brandId}', [UserController::class, 'revokeBrandAccess']);
        Route::put('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('/users/{id}/permissions', [UserController::class, 'getPermissions']);
        Route::post('/users/{id}/permissions', [UserController::class, 'assignPermissions']);
        Route::delete('/users/{id}/permissions', [UserController::class, 'removePersonalPermissions']);

        Route::apiResource('roles', RoleController::class);
        Route::get('/roles/permissions/list', [RoleController::class, 'permissionsList']);

        Route::apiResource('brands', BrandController::class);
        Route::get('/brands/{id}/categories', [BrandController::class, 'categories']);
        Route::get('/brands/{id}/warranties', [BrandController::class, 'warranties']);
        Route::get('/brands/{id}/stats', [BrandController::class, 'stats']);
        Route::put('/brands/{id}/toggle-status', [BrandController::class, 'toggleStatus']);

        Route::get('/categories/parents', [CategoryController::class, 'parents']);
        Route::apiResource('categories', CategoryController::class);
        Route::get('/categories/{id}/subcategories', [CategoryController::class, 'subcategories']);
        Route::put('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);

        Route::apiResource('warranties', WarrantyController::class);
        Route::post('/warranties/{id}/void', [WarrantyController::class, 'void']);
        Route::post('/warranties/{id}/unvoid', [WarrantyController::class, 'unvoid']);
        Route::get('/warranties/{id}/claims', [WarrantyController::class, 'claims']);
        Route::get('/warranties/expiring-soon', [WarrantyController::class, 'expiringSoon']);

        Route::apiResource('claims', ClaimController::class);
        Route::post('/claims/{id}/convert-to-work-order', [ClaimController::class, 'convertToWorkOrder']);
        Route::put('/claims/{id}/close', [ClaimController::class, 'close']);
        Route::get('/claims/{id}/work-order', [ClaimController::class, 'workOrder']);

        Route::get('/work-orders/pending', [WorkOrderController::class, 'pending']);
        Route::get('/work-orders/overdue', [WorkOrderController::class, 'overdue']);
        Route::apiResource('work-orders', WorkOrderController::class)->except(['store']);
        Route::post('/work-orders/{id}/assign', [WorkOrderController::class, 'assignServiceCenter']);
        Route::put('/work-orders/{id}/status', [WorkOrderController::class, 'updateStatus']);
        Route::get('/work-orders/{id}/feedback-link', [WorkOrderController::class, 'getFeedbackLink']);

        Route::apiResource('service-centers', ServiceCenterController::class);
        Route::put('/service-centers/{id}/toggle-status', [ServiceCenterController::class, 'toggleStatus']);
        Route::get('/service-centers/{id}/work-orders', [ServiceCenterController::class, 'workOrders']);
        Route::get('/service-centers/{id}/stats', [ServiceCenterController::class, 'stats']);

        Route::apiResource('couriers', CourierController::class);
        Route::put('/couriers/{id}/toggle-status', [CourierController::class, 'toggleStatus']);

        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'upsert']);
        Route::get('/settings/{key}', [SettingController::class, 'show']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);

        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [DashboardController::class, 'stats']);
            Route::get('/warranty-stats', [DashboardController::class, 'warrantyStats']);
            Route::get('/claim-stats', [DashboardController::class, 'claimStats']);
            Route::get('/work-order-stats', [DashboardController::class, 'workOrderStats']);
            Route::get('/recent-claims', [DashboardController::class, 'recentClaims']);
            Route::get('/recent-work-orders', [DashboardController::class, 'recentWorkOrders']);
            Route::get('/brand-wise-summary', [DashboardController::class, 'brandWiseSummary']);
            Route::get('/service-center-performance', [DashboardController::class, 'serviceCenterPerformance']);
            Route::get('/monthly-claims', [DashboardController::class, 'monthlyClaims']);
            Route::get('/expiring-warranties', [DashboardController::class, 'expiringWarranties']);
        });

        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    });
});
