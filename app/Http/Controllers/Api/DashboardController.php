<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Claim;
use App\Models\ServiceCenter;
use App\Models\Warranty;
use App\Models\WorkOrder;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $brandId = $request->brand_id;
        $serviceCenterId = $request->service_center_id;

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

        if ($brandId) {
            $warrantyQuery->where('brand_id', $brandId);
            $claimQuery->whereHas('warranty', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            });
            $workOrderQuery->whereHas('claim.warranty', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            });
        }

        if ($serviceCenterId) {
            $claimQuery->where('service_center_id', $serviceCenterId);
            $workOrderQuery->where('service_center_id', $serviceCenterId);
        }

        $stats = [
            'total_warranties' => $warrantyQuery->count(),
            'active_warranties' => (clone $warrantyQuery)->active()->count(),
            'expired_warranties' => (clone $warrantyQuery)->expired()->count(),
            'total_claims' => $claimQuery->count(),
            'open_claims' => (clone $claimQuery)->open()->count(),
            'converted_claims' => (clone $claimQuery)->converted()->count(),
            'closed_claims' => (clone $claimQuery)->closed()->count(),
            'total_work_orders' => $workOrderQuery->count(),
            'pending_work_orders' => (clone $workOrderQuery)->pending()->count(),
            'in_progress_work_orders' => (clone $workOrderQuery)->inProgress()->count(),
            'completed_work_orders' => (clone $workOrderQuery)->completed()->count(),
            'delivered_work_orders' => (clone $workOrderQuery)->delivered()->count(),
            'total_service_centers' => ServiceCenter::when($brandId, fn($q) => $q->whereJsonContains('brand_ids', (int) $brandId))->where('is_active', true)->count(),
            'total_brands' => Brand::where('status', 'active')->count(),
            'avg_customer_rating' => (clone $workOrderQuery)->whereNotNull('customer_rating')->avg('customer_rating') ?? 0,
            'avg_tat_days' => (clone $workOrderQuery)->whereNotNull('tat')->avg('tat') ?? 0,
        ];

        $recentClaims = Claim::with(['warranty.brand', 'serviceCenter'])
            ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('warranty', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
            ->when($brandId, fn($q) => $q->whereHas('warranty', fn($q) => $q->where('brand_id', $brandId)))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentWorkOrders = WorkOrder::with(['claim.warranty.brand', 'serviceCenter'])
            ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('claim.warranty', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
            ->when($brandId, fn($q) => $q->whereHas('claim.warranty', fn($q) => $q->where('brand_id', $brandId)))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $year = $request->year ?? now()->year;
        $monthlyClaims = Claim::query()
            ->selectRaw('MONTH(claim_date) as month, COUNT(*) as count')
            ->whereYear('claim_date', $year)
            ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('warranty', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
            ->groupBy('month')
            ->get();

        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[] = [
                'month' => $i,
                'month_name' => Carbon::createFromDate($year, $i)->format('M'),
                'count' => $monthlyClaims->where('month', $i)->first()?->count ?? 0,
            ];
        }

        $expiringWarranties = Warranty::with(['brand', 'category'])
            ->expiringSoon(30)
            ->when($user->isBrandRestricted(), fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()))
            ->orderBy('end_date', 'asc')
            ->limit(10)
            ->get();

        $data = [
            'stats' => $stats,
            'warranty' => [
                'total' => $stats['total_warranties'],
                'active' => $stats['active_warranties'],
                'expired' => $stats['expired_warranties'],
                'expiring_soon' => $expiringWarranties,
            ],
            'claim' => [
                'total' => $stats['total_claims'],
                'open' => $stats['open_claims'],
                'converted' => $stats['converted_claims'],
                'closed' => $stats['closed_claims'],
                'recent' => $recentClaims,
                'monthly' => $monthlyData,
            ],
            'work_order' => [
                'total' => $stats['total_work_orders'],
                'pending' => $stats['pending_work_orders'],
                'in_progress' => $stats['in_progress_work_orders'],
                'completed' => $stats['completed_work_orders'],
                'delivered' => $stats['delivered_work_orders'],
                'recent' => $recentWorkOrders,
            ],
            'service_center' => [
                'total' => $stats['total_service_centers'],
            ],
            'brand' => [
                'total' => $stats['total_brands'],
            ],
            'metrics' => [
                'avg_customer_rating' => $stats['avg_customer_rating'],
                'avg_tat_days' => $stats['avg_tat_days'],
            ],
        ];

        return $this->success($data);
    }

    public function stats(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function warrantyStats(Request $request): JsonResponse
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

    /* public function warrantyStats(Request $request): JsonResponse
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
        ];

        return $this->success($stats);
    } */

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

        $warranties = $query->orderBy('end_date', 'asc')->paginate($request->limit ?? 15);

        return $this->success($warranties);
    }

    public function clientDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalClaims = Claim::where('customer_user_id', $user->id)->count();

        $statusWise = Claim::where('customer_user_id', $user->id)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $recentClaims = Claim::with(['warranty.brand', 'serviceCenter', 'workOrder'])
            ->where('customer_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $this->success([
            'total_claims' => $totalClaims,
            'status_wise' => $statusWise,
            'recent_claims' => $recentClaims,
        ]);
    }
}
