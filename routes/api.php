<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DeliveryChallanController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\MemorizeReportController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceCenterController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::post('/customer/login', [CustomerAuthController::class, 'login']);
    Route::post('/customer/forgot-password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('/customer/reset-password', [CustomerAuthController::class, 'resetPassword']);

    Route::get('/brands/list', [BrandController::class, 'brands_list']);
    Route::get('/cities/list', [CityController::class, 'cities_list']);
    Route::get('/service-centers/list', [ServiceCenterController::class, 'service_centers_list']);
    Route::get('/service-centers/by-brand', [ServiceCenterController::class, 'byBrand']);
    Route::get('/claims/track/{claimNumber}', [ClaimController::class, 'track']);
    Route::post('/claims/feedback/{token}', [ClaimController::class, 'submitFeedback']);
    Route::get('/settings/{key}', [SettingController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('permission:profile/change_password');
        Route::post('/auth/change-password', [AuthController::class, 'changePassword'])->middleware('permission:profile/change_password');
        Route::get('/auth/me', [AuthController::class, 'me'])->middleware('permission:profile/view');
        Route::put('/auth/profile', [AuthController::class, 'updateProfile'])->middleware('permission:profile/edit');

        Route::middleware('permission:team_members/view')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::get('/users/trashed', [UserController::class, 'trashed']);
            Route::get('/users/{id}/brand-access', [UserController::class, 'getBrandAccess']);
            Route::get('/users/{id}/service-center-access', [UserController::class, 'getServiceCenterAccess']);
            Route::get('/users/{id}/permissions', [UserController::class, 'getPermissions']);
        });

        Route::middleware('permission:team_members/create')->group(function () {
            Route::post('/users', [UserController::class, 'store']);
            Route::post('/users/{id}/restore', [UserController::class, 'restore']);
            Route::post('/users/{id}/brand-access', [UserController::class, 'assignBrandAccess']);
            Route::post('/users/{id}/service-center-access', [UserController::class, 'assignServiceCenterAccess']);
            Route::post('/users/{id}/permissions', [UserController::class, 'assignPermissions']);
        });

        Route::middleware('permission:team_members/edit')->group(function () {
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::put('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
            Route::delete('/users/{id}/brand-access/{brandId}', [UserController::class, 'revokeBrandAccess']);
            Route::delete('/users/{id}/service-center-access/{serviceCenterId}', [UserController::class, 'revokeServiceCenterAccess']);
            Route::delete('/users/{id}/permissions', [UserController::class, 'removePersonalPermissions']);
        });

        Route::middleware('permission:team_members/delete')->group(function () {
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });

        Route::middleware('permission:roles/list')->group(function () {
            Route::get('/roles', [RoleController::class, 'index']);
            Route::get('/roles/permissions/list', [RoleController::class, 'permissionsList']);
        });

        Route::middleware('permission:roles/view')->group(function () {
            Route::get('/roles/{role}', [RoleController::class, 'show']);
        });

        Route::middleware('permission:roles/create')->group(function () {
            Route::post('/roles', [RoleController::class, 'store']);
        });

        Route::middleware('permission:roles/edit')->group(function () {
            Route::put('/roles/{role}', [RoleController::class, 'update']);
        });

        Route::middleware('permission:roles/delete')->group(function () {
            Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
        });

        Route::middleware('permission:brands/list')->group(function () {
            Route::get('/brands', [BrandController::class, 'index']);
            Route::get('/brands/list', [BrandController::class, 'brands_list']);
        });

        Route::middleware('permission:brands/view')->group(function () {
            Route::get('/brands/{brand}', [BrandController::class, 'show']);
            Route::get('/brands/{id}/stats', [BrandController::class, 'stats']);
            Route::get('/brands/{id}/products', [BrandController::class, 'products']);
        });

        Route::middleware('permission:brands/create')->group(function () {
            Route::post('/brands', [BrandController::class, 'store']);
        });

        Route::middleware('permission:brands/edit')->group(function () {
            Route::put('/brands/{brand}', [BrandController::class, 'update']);
            Route::put('/brands/{id}/toggle-status', [BrandController::class, 'toggleStatus']);
        });

        Route::middleware('permission:brands/delete')->group(function () {
            Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
        });

        Route::middleware('permission:categories/list')->group(function () {
            Route::get('/categories', [CategoryController::class, 'index']);
            Route::get('/categories/parents', [CategoryController::class, 'parents']);
            Route::get('/categories/{id}/subcategories', [CategoryController::class, 'subcategories']);
        });

        Route::middleware('permission:categories/view')->group(function () {
            Route::get('/categories/{category}', [CategoryController::class, 'show']);
        });

        Route::middleware('permission:categories/create')->group(function () {
            Route::post('/categories', [CategoryController::class, 'store']);
        });

        Route::middleware('permission:categories/edit')->group(function () {
            Route::put('/categories/{category}', [CategoryController::class, 'update']);
            Route::put('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);
        });

        Route::middleware('permission:categories/delete')->group(function () {
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
        });

        Route::middleware('permission:cities/list')->group(function () {
            Route::get('/cities', [CityController::class, 'index']);
            Route::get('/cities/list', [CityController::class, 'cities_list']);
        });

        Route::middleware('permission:cities/view')->group(function () {
            Route::get('/cities/{city}', [CityController::class, 'show']);
        });

        Route::middleware('permission:cities/create')->group(function () {
            Route::post('/cities', [CityController::class, 'store']);
        });

        Route::middleware('permission:cities/edit')->group(function () {
            Route::put('/cities/{city}', [CityController::class, 'update']);
            Route::put('/cities/{id}/toggle-status', [CityController::class, 'toggleStatus']);
        });

        Route::middleware('permission:cities/delete')->group(function () {
            Route::delete('/cities/{city}', [CityController::class, 'destroy']);
        });

        Route::middleware('permission:customers/list')->group(function () {
            Route::get('/customers', [CustomerController::class, 'index']);
        });

        Route::middleware('permission:customers/view')->group(function () {
            Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        });

        Route::middleware('permission:customers/create')->group(function () {
            Route::post('/customers', [CustomerController::class, 'store']);
        });

        Route::middleware('permission:customers/edit')->group(function () {
            Route::put('/customers/{customer}', [CustomerController::class, 'update']);
        });

        Route::middleware('permission:customers/delete')->group(function () {
            Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
        });

        Route::prefix('customer')->group(function () {
            Route::post('/logout', [CustomerAuthController::class, 'logout']);
            Route::post('/change-password', [CustomerAuthController::class, 'changePassword']);
            Route::get('/profile', [CustomerAuthController::class, 'profile']);
            Route::put('/profile', [CustomerAuthController::class, 'updateProfile']);
            Route::get('/claims', [CustomerAuthController::class, 'claims']);
            Route::get('/dashboard', [CustomerAuthController::class, 'dashboard']);
        });

        Route::middleware('permission:products/list')->group(function () {
            Route::get('/products', [ProductController::class, 'index']);
            Route::get('/products/check/{serial}', [ProductController::class, 'checkSerial']);
            Route::get('/products/import/sample', [ProductController::class, 'importSample']);
        });

        Route::middleware('permission:products/view')->group(function () {
            Route::get('/products/{product}', [ProductController::class, 'show']);
        });

        Route::middleware('permission:products/create')->group(function () {
            Route::post('/products', [ProductController::class, 'store']);
            Route::post('/products/import', [ProductController::class, 'import']);
        });

        Route::middleware('permission:products/edit')->group(function () {
            Route::put('/products/{product}', [ProductController::class, 'update']);
        });

        Route::middleware('permission:products/delete')->group(function () {
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        });

        Route::middleware('permission:claims/list')->group(function () {
            Route::get('/claims', [ClaimController::class, 'index']);
            Route::get('/claimDeliveryList', [ClaimController::class, 'getDeliveryList']);
        });

        Route::middleware('permission:claims/view')->group(function () {
            Route::get('/claims/{claim}', [ClaimController::class, 'show']);
            Route::get('/claims/{id}/feedback-link', [ClaimController::class, 'getFeedbackLink']);
            Route::get('/claims/{id}/activity-timeline', [ClaimController::class, 'activityTimeline']);
        });

        Route::middleware('permission:claims/create')->group(function () {
            Route::post('/claims', [ClaimController::class, 'store']);
        });

        Route::middleware('permission:claims/edit')->group(function () {
            Route::put('/claims/{claim}', [ClaimController::class, 'update']);
            Route::put('/claims/{id}/close', [ClaimController::class, 'close']);
            Route::delete('/claims/{id}/attachment', [ClaimController::class, 'deleteAttachment']);
        });

        Route::middleware('permission:claims/delete')->group(function () {
            Route::delete('/claims/{claim}', [ClaimController::class, 'destroy']);
        });

        Route::middleware('permission:claims/export')->group(function () {
            Route::get('/exports/claims', [ExportController::class, 'downloadClaims']);
        });

        Route::middleware('permission:service_centers/list')->group(function () {
            Route::get('/service-centers', [ServiceCenterController::class, 'index']);
            Route::get('/service-centers/list', [ServiceCenterController::class, 'service_centers_list']);
            Route::get('/service-centers/by-brand', [ServiceCenterController::class, 'byBrand']);
        });

        Route::middleware('permission:service_centers/view')->group(function () {
            Route::get('/service-centers/{serviceCenter}', [ServiceCenterController::class, 'show']);
        });

        Route::middleware('permission:service_centers/create')->group(function () {
            Route::post('/service-centers', [ServiceCenterController::class, 'store']);
        });

        Route::middleware('permission:service_centers/edit')->group(function () {
            Route::put('/service-centers/{serviceCenter}', [ServiceCenterController::class, 'update']);
            Route::put('/service-centers/{id}/toggle-status', [ServiceCenterController::class, 'toggleStatus']);
        });

        Route::middleware('permission:service_centers/delete')->group(function () {
            Route::delete('/service-centers/{serviceCenter}', [ServiceCenterController::class, 'destroy']);
        });

        Route::middleware('permission:couriers/list')->group(function () {
            Route::get('/couriers', [CourierController::class, 'index']);
        });

        Route::middleware('permission:couriers/view')->group(function () {
            Route::get('/couriers/{courier}', [CourierController::class, 'show']);
        });

        Route::middleware('permission:couriers/create')->group(function () {
            Route::post('/couriers', [CourierController::class, 'store']);
        });

        Route::middleware('permission:couriers/edit')->group(function () {
            Route::put('/couriers/{courier}', [CourierController::class, 'update']);
            Route::put('/couriers/{id}/toggle-status', [CourierController::class, 'toggleStatus']);
        });

        Route::middleware('permission:couriers/delete')->group(function () {
            Route::delete('/couriers/{courier}', [CourierController::class, 'destroy']);
        });

        Route::middleware('permission:delivery_challans/list')->group(function () {
            Route::get('/delivery-challans', [DeliveryChallanController::class, 'index']);
        });

        Route::middleware('permission:delivery_challans/view')->group(function () {
            Route::get('/delivery-challans/{id}', [DeliveryChallanController::class, 'show']);
        });

        Route::middleware('permission:delivery_challans/create')->group(function () {
            Route::post('/delivery-challans', [DeliveryChallanController::class, 'store']);
        });

        Route::middleware('permission:parts/list')->group(function () {
            Route::get('/parts', [PartController::class, 'index']);
        });

        Route::middleware('permission:parts/view')->group(function () {
            Route::get('/parts/{part}', [PartController::class, 'show']);
            Route::get('/parts/{part}/usage-history', [PartController::class, 'usageHistory']);
        });

        Route::middleware('permission:parts/create')->group(function () {
            Route::post('/parts', [PartController::class, 'store']);
        });

        Route::middleware('permission:parts/edit')->group(function () {
            Route::put('/parts/{part}', [PartController::class, 'update']);
            Route::put('/parts/{id}/toggle-status', [PartController::class, 'toggleStatus']);
        });

        Route::middleware('permission:parts/delete')->group(function () {
            Route::delete('/parts/{part}', [PartController::class, 'destroy']);
        });

        Route::middleware('permission:app_settings/view')->group(function () {
            Route::get('/settings', [SettingController::class, 'index']);
        });

        Route::middleware('permission:app_settings/edit')->group(function () {
            Route::post('/settings', [SettingController::class, 'upsert']);
            Route::put('/settings', [SettingController::class, 'updateAll']);
            Route::delete('/settings/{key}', [SettingController::class, 'destroy']);
        });

        // Route::middleware('permission:reports/view')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/client-dashboard', [DashboardController::class, 'clientDashboard']);
        Route::get('/exports/products', [ExportController::class, 'downloadProducts']);
        // });

        Route::middleware('permission:activity_logs/list')->group(function () {
            Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        });

        Route::middleware('permission:memorized_reports/list')->group(function () {
            Route::get('/memorize-reports', [MemorizeReportController::class, 'index']);
        });

        Route::middleware('permission:memorized_reports/view')->group(function () {
            Route::get('/memorize-reports/{memorizeReport}', [MemorizeReportController::class, 'show']);
        });

        Route::middleware('permission:memorized_reports/create')->group(function () {
            Route::post('/memorize-reports', [MemorizeReportController::class, 'store']);
        });

        Route::middleware('permission:memorized_reports/edit')->group(function () {
            Route::put('/memorize-reports/{memorizeReport}', [MemorizeReportController::class, 'update']);
        });

        Route::middleware('permission:memorized_reports/delete')->group(function () {
            Route::delete('/memorize-reports/{memorizeReport}', [MemorizeReportController::class, 'destroy']);
        });
    });
});
