<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warranty;
use App\Models\Claim;
use App\Models\WorkOrder;
use App\Models\ServiceCenter;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $warrantyQuery = Warranty::query();
        $claimQuery = Claim::query();
        $workOrderQuery = WorkOrder::query();

        if ($user->isBrandRestricted()) {
            $brandIds = $user->accessibleBrandIds();
            $warrantyQuery->whereIn('brand_id', $brandIds);
            $claimQuery->whereHas('warranty', function ($q) use ($brandIds) {
                $q->whereIn('brand_id', $brandIds);
            });
            $workOrderQuery->whereHas('claim.warranty', function ($q) use ($brandIds) {
                $q->whereIn('brand_id', $brandIds);
            });
        }

        $stats = [
            'total_warranties' => $warrantyQuery->count(),
            'active_warranties' => (clone $warrantyQuery)->active()->count(),
            'expired_warranties' => (clone $warrantyQuery)->expired()->count(),
            'void_warranties' => (clone $warrantyQuery)->void()->count(),
            'total_claims' => $claimQuery->count(),
            'open_claims' => (clone $claimQuery)->open()->count(),
            'converted_claims' => (clone $claimQuery)->converted()->count(),
            'closed_claims' => (clone $claimQuery)->closed()->count(),
            'total_work_orders' => $workOrderQuery->count(),
            'pending_work_orders' => (clone $workOrderQuery)->pending()->count(),
            'in_progress_work_orders' => (clone $workOrderQuery)->inProgress()->count(),
            'completed_work_orders' => (clone $workOrderQuery)->completed()->count(),
            'delivered_work_orders' => (clone $workOrderQuery)->delivered()->count(),
            'total_service_centers' => ServiceCenter::where('is_active', true)->count(),
            'total_brands' => Brand::where('status', 'active')->count(),
            'avg_customer_rating' => (clone $workOrderQuery)->whereNotNull('customer_rating')->avg('customer_rating') ?? 0,
            'avg_tat_days' => (clone $workOrderQuery)->whereNotNull('tat')->avg('tat') ?? 0,
        ];

        return $this->success($stats);
    }

    public function warrantyStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Warranty::query();

        if ($user->isBrandRestricted()) {
            $query->whereIn('brand_id', $user->accessibleBrandIds());
        }

        $stats = [
            'total' => $query->count(),
            'active' => (clone $query)->active()->count(),
            'expired' => (clone $query)->expired()->count(),
            'void' => (clone $query)->void()->count(),
        ];

        return $this->success($stats);
    }

    public function claimStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Claim::query();

        if ($user->isBrandRestricted()) {
            $query->whereHas('warranty', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        $stats = [
            'total' => $query->count(),
            'open' => (clone $query)->open()->count(),
            'converted' => (clone $query)->converted()->count(),
            'closed' => (clone $query)->closed()->count(),
        ];

        return $this->success($stats);
    }

    public function workOrderStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = WorkOrder::query();

        if ($user->isBrandRestricted()) {
            $query->whereHas('claim.warranty', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->pending()->count(),
            'in_progress' => (clone $query)->inProgress()->count(),
            'completed' => (clone $query)->completed()->count(),
            'delivered' => (clone $query)->delivered()->count(),
            'avg_tat_days' => (clone $query)->whereNotNull('tat')->avg('tat') ?? 0,
        ];

        return $this->success($stats);
    }

    public function recentClaims(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Claim::with(['warranty.brand', 'serviceCenter']);

        if ($user->isBrandRestricted()) {
            $query->whereHas('warranty', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        $claims = $query->orderBy('created_at', 'desc')->limit(10)->get();

        return $this->success($claims);
    }

    public function recentWorkOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = WorkOrder::with(['claim.warranty.brand', 'serviceCenter']);

        if ($user->isBrandRestricted()) {
            $query->whereHas('claim.warranty', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        $workOrders = $query->orderBy('created_at', 'desc')->limit(10)->get();

        return $this->success($workOrders);
    }

    public function brandWiseSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $brands = Brand::query();
        
        if ($user->isBrandRestricted()) {
            $brands->whereIn('id', $user->accessibleBrandIds());
        }

        $brands = $brands->get()->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'total_warranties' => $brand->warranties()->count(),
                'active_warranties' => $brand->warranties()->active()->count(),
                'total_claims' => $brand->warranties()->withCount('claims')->get()->sum('claims_count'),
                'open_work_orders' => WorkOrder::whereHas('claim.warranty', function ($q) use ($brand) {
                    $q->where('brand_id', $brand->id);
                })->where('status', '!=', 'Delivered')->count(),
            ];
        });

        return $this->success($brands);
    }

    public function serviceCenterPerformance(Request $request): JsonResponse
    {
        $serviceCenters = ServiceCenter::with(['workOrders' => function ($query) {
            $query->select('id', 'service_center_id', 'status', 'customer_rating', 'tat');
        }])->get()->map(function ($center) {
            return [
                'id' => $center->id,
                'title' => $center->title,
                'assigned_count' => $center->workOrders()->count(),
                'completed_count' => $center->workOrders()->completed()->count(),
                'delivered_count' => $center->workOrders()->delivered()->count(),
                'avg_rating' => $center->workOrders()->whereNotNull('customer_rating')->avg('customer_rating') ?? 0,
                'avg_tat' => $center->workOrders()->whereNotNull('tat')->avg('tat') ?? 0,
            ];
        });

        return $this->success($serviceCenters);
    }

    public function monthlyClaims(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = $request->year ?? now()->year;

        $query = Claim::query()
            ->selectRaw('MONTH(claim_date) as month, COUNT(*) as count')
            ->whereYear('claim_date', $year);

        if ($user->isBrandRestricted()) {
            $query->whereHas('warranty', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        $claims = $query->groupBy('month')->get();

        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[] = [
                'month' => $i,
                'month_name' => Carbon::createFromDate($year, $i)->format('M'),
                'count' => $claims->where('month', $i)->first()?->count ?? 0,
            ];
        }

        return $this->success($monthlyData);
    }

    public function expiringWarranties(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = $request->days ?? 30;

        $query = Warranty::with(['brand', 'category'])
            ->expiringSoon($days);

        if ($user->isBrandRestricted()) {
            $query->whereIn('brand_id', $user->accessibleBrandIds());
        }

        $warranties = $query->orderBy('end_date', 'asc')->paginate($request->per_page ?? 15);

        return $this->success($warranties);
    }
}
