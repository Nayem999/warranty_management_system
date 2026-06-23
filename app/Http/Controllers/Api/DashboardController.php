<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Claim;
use App\Models\Product;
use App\Models\ServiceCenter;
use App\Models\WorkOrder;
use App\Traits\ApiResponse;
use App\Traits\UserAccessFilter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse, UserAccessFilter;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $brandId = $request->brand_id;
        $serviceCenterId = $request->service_center_id;

        $productQuery = Product::query();
        $claimQuery = Claim::query();

        if ($user->isBrandRestricted()) {
            $brandIds = $user->accessibleBrandIds();
            $productQuery->whereIn('brand_id', $brandIds);
            $claimQuery->whereHas('product', function ($q) use ($brandIds) {
                $q->whereIn('brand_id', $brandIds);
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $serviceCenterIds = $user->accessibleServiceCenterIds();
            $claimQuery->whereIn('service_center_id', $serviceCenterIds);
        }

        if ($brandId) {
            $productQuery->where('brand_id', $brandId);
            $claimQuery->whereHas('product', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            });
        }

        if ($serviceCenterId) {
            $claimQuery->where('service_center_id', $serviceCenterId);
        }

        $stats = [
            'total_products' => $productQuery->count(),
            // 'active_products' => (clone $productQuery)->active()->count(),
            // 'expired_products' => (clone $productQuery)->expired()->count(),
            'total_claims' => $claimQuery->count(),
            'open_claims' => (clone $claimQuery)->open()->count(),
            'closed_claims' => (clone $claimQuery)->closed()->count(),
            'total_service_centers' => ServiceCenter::when($brandId, fn($q) => $q->whereHas('brands', fn($q) => $q->where('wms_brands.id', $brandId)))->where('is_active', true)->count(),
            'total_brands' => Brand::where('status', 'active')->count(),
            'avg_customer_rating' => (int) (clone $claimQuery)->whereNotNull('customer_rating')->avg('customer_rating') ?? 0,
            'avg_tat_days' => (int) (clone $claimQuery)->whereNotNull('tat')->avg('tat') ?? 0,
        ];

        $closed_status = [
            'closed_repaired' => (clone $claimQuery)->where("status", "Closed-Repaired")->count(),
            'closed_un_repaired' => (clone $claimQuery)->where("status", "Closed-Un Repaired")->count(),
            'closed_replaced' => (clone $claimQuery)->where("status", "Closed-Replaced")->count(),
            'closed_reimbursement' => (clone $claimQuery)->where("status", "Closed-Reimbursement")->count(),
        ];
        $claim_service_type = [
            'in_warranty' => (clone $claimQuery)->where("service_type", "In Warranty")->count(),
            'Warranty_void' => (clone $claimQuery)->where("service_type", "Warranty Void")->count(),
            'doa' => (clone $claimQuery)->where("service_type", "DOA")->count(),
            'oow_or_expired' => (clone $claimQuery)->where("service_type", "OOW/Expired")->count(),
        ];

        $recentClaims = Claim::with(['product.brand', 'serviceCenter'])
            ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
            ->when($user->isServiceCenterRestricted(), fn($q) => $q->whereIn('service_center_id', $user->accessibleServiceCenterIds()))
            ->when($brandId, fn($q) => $q->whereHas('product', fn($q) => $q->where('brand_id', $brandId)))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();


        $engineerClaimsSummary = Claim::query()
            ->join('users', 'users.id', '=', 'wms_claims.engineer_id')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                DB::raw('COUNT(*) AS total_job'),
                DB::raw("SUM(CASE WHEN wms_claims.status = 'Assigned' THEN 1 ELSE 0 END) AS assigned"),
                DB::raw("SUM(CASE WHEN wms_claims.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress"),
                DB::raw("SUM(CASE WHEN wms_claims.status = 'Waiting For Part' THEN 1 ELSE 0 END) AS waiting_for_part"),
                DB::raw("SUM( CASE WHEN wms_claims.status IN ( 'Closed-Repaired', 'Closed-Un Repaired', 'Closed-Replaced', 'Closed-Reimbursement') THEN 1 ELSE 0 END) AS completed")
            )
            ->when($user->isBrandRestricted(), function ($q) use ($user) {
                $q->whereHas('product', function ($q) use ($user) {
                    $q->whereIn('brand_id', $user->accessibleBrandIds());
                });
            })
            ->when($user->isServiceCenterRestricted(), function ($q) use ($user) {
                $q->whereIn('wms_claims.service_center_id', $user->accessibleServiceCenterIds());
            })
            ->when($brandId, function ($q) use ($brandId) {
                $q->whereHas('product', function ($q) use ($brandId) {
                    $q->where('brand_id', $brandId);
                });
            })
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderBy('users.first_name')
            ->get();

        /* $recentWorkOrders = WorkOrder::with(['claim.product.brand', 'serviceCenter'])
            ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('claim.product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
            ->when($user->isServiceCenterRestricted(), fn($q) => $q->whereIn('service_center_id', $user->accessibleServiceCenterIds()))
            ->when($brandId, fn($q) => $q->whereHas('claim.product', fn($q) => $q->where('brand_id', $brandId)))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(); */

        $year = $request->year ?? now()->year;
        $monthlyClaims = Claim::query()
            ->selectRaw('MONTH(claim_date) as month, COUNT(*) as count')
            ->whereYear('claim_date', $year)
            ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
            ->when($user->isServiceCenterRestricted(), fn($q) => $q->whereIn('service_center_id', $user->accessibleServiceCenterIds()))
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

        /* $expiringProducts = Product::with(['brand', 'category'])
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>=', now())
            ->when($user->isBrandRestricted(), fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()))
            ->orderBy('end_date', 'asc')
            ->limit(10)
            ->get(); */

        $serviceCenterQuery = ServiceCenter::query();
        if ($user->isBrandRestricted()) {
            $brandIds = $user->accessibleBrandIds();
            $serviceCenterQuery->whereHas('brands', fn($q) => $q->whereIn('wms_brands.id', $brandIds));
        }
        if ($user->isServiceCenterRestricted()) {
            $serviceCenterQuery->whereIn('id', $user->accessibleServiceCenterIds());
        }

        $serviceCenterComparison = $serviceCenterQuery->get()->map(function ($center) use ($brandId) {
            $query = Claim::query()
                ->where('service_center_id', $center->id);

            if ($brandId) {
                $query->whereHas('product', fn($q) => $q->where('brand_id', $brandId));
            }

            $total = $query->count();
            $progress = (clone $query)->whereIn('status', ['Assigned', 'Not Assigned', 'Progress', 'In Progress','Waiting for Part'])->count();
            $closed = (clone $query)->whereIn('status', ['Closed-Repaired', 'Closed-Un Repaired', 'Closed-Replaced', 'Closed-Reimbursement'])->where('is_delivered', 0)->count();
            $delivered = (clone $query)->where('is_delivered', 1)->count();
            $completed = $closed + $delivered;
            $completionRate = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

            return [
                'id' => $center->id,
                'name' => $center->title,
                'total' => $total,
                'progress' => $progress,
                'closed' => $closed,
                'delivered' => $delivered,
                'completion_rate' => $completionRate,
            ];
        });

        $customerRatings = Claim::query()
            ->select('customer_rating', DB::raw('COUNT(*) as count'))
            ->whereNotNull('customer_rating')
            ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
            ->when($user->isServiceCenterRestricted(), fn($q) => $q->whereIn('service_center_id', $user->accessibleServiceCenterIds()))
            ->when($brandId, fn($q) => $q->whereHas('product', fn($q) => $q->where('brand_id', $brandId)))
            ->when($serviceCenterId, fn($q) => $q->where('service_center_id', $serviceCenterId))
            ->groupBy('customer_rating')
            ->get()
            ->mapWithKeys(fn($item) => [(int) $item->customer_rating => $item->count]);

        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[] = [
                'rating' => $i,
                'count' => $customerRatings[$i] ?? 0,
            ];
        }


        $today = Carbon::today();

        $ranges = [
            '1-7 Days'   => [
                'start' => $today->copy()->subDays(7)->startOfDay()->format('Y-m-d H:i:s'),
                'end'   => $today->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ],
            '8-14 Days'  => [
                'start' => $today->copy()->subDays(14)->startOfDay()->format('Y-m-d H:i:s'),
                'end'   => $today->copy()->subDays(8)->endOfDay()->format('Y-m-d H:i:s'),
            ],
            '1 Month'    => [
                'start' => $today->copy()->subDays(30)->startOfDay()->format('Y-m-d H:i:s'),
                'end'   => $today->copy()->subDays(15)->endOfDay()->format('Y-m-d H:i:s'),
            ],
            '2 Months'   => [
                'start' => $today->copy()->subDays(60)->startOfDay()->format('Y-m-d H:i:s'),
                'end'   => $today->copy()->subDays(31)->endOfDay()->format('Y-m-d H:i:s'),
            ],
            '3 Months'   => [
                'start' => $today->copy()->subDays(90)->startOfDay()->format('Y-m-d H:i:s'),
                'end'   => $today->copy()->subDays(61)->endOfDay()->format('Y-m-d H:i:s'),
            ],
            '> 3 Months' => [
                'start' => null,
                'end'   => $today->copy()->subDays(91)->endOfDay()->format('Y-m-d H:i:s'),
            ],
        ];

        $pending_by_claim_date = [];
        $completed_by_claim_date = [];
        $completed_by_closed_date_n_delivered = [];
        $delivered_by_claim_date = [];
        foreach ($ranges as $key => $value) {

            $dateFilter = function ($q, $column) use ($value) {
                if (!$value['start']) {
                    return $q->where($column, '<=', $value['end']);
                }

                return $q->whereBetween($column, [
                    $value['start'],
                    $value['end']
                ]);
            };

            $pending_by_claim_date[$key] = Claim::query()
                ->where(function ($q) use ($dateFilter) {
                    $dateFilter($q, 'claim_date');
                })
                ->whereIn('status', ["Not Assigned", "Assigned", "In Progress", "Waiting for Part"])
                ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
                ->when($user->isServiceCenterRestricted(), fn($q) => $q->whereIn('service_center_id', $user->accessibleServiceCenterIds()))
                ->count();


            $completed_by_claim_date[$key] = Claim::query()
                ->where(function ($q) use ($dateFilter) {
                    $dateFilter($q, 'claim_date');
                })
                ->whereIn('status', ["Closed-Repaired", "Closed-Un Repaired", "Closed-Replaced", "Closed-Reimbursement", "Delivered"])
                ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
                ->when($user->isServiceCenterRestricted(), fn($q) => $q->whereIn('service_center_id', $user->accessibleServiceCenterIds()))
                ->count();


            $completed_by_closed_date_n_delivered[$key] = Claim::query()
                ->where(function ($q) use ($dateFilter) {
                    $dateFilter($q, 'wo_closed_date');
                })
                ->where('is_delivered', 1)
                ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
                ->when($user->isServiceCenterRestricted(), fn($q) => $q->whereIn('service_center_id', $user->accessibleServiceCenterIds()))
                ->count();


            $delivered_by_claim_date[$key] = Claim::query()
                ->where(function ($q) use ($dateFilter) {
                    $dateFilter($q, 'claim_date');
                })
                ->where('is_delivered', 1)
                ->when($user->isBrandRestricted(), fn($q) => $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds())))
                ->when($user->isServiceCenterRestricted(), fn($q) => $q->whereIn('service_center_id', $user->accessibleServiceCenterIds()))
                ->count();
        }
        $data = [
            'stats' => $stats,
            /* 'product' => [
                'total' => $stats['total_products'],
                'active' => $stats['active_products'],
                'expired' => $stats['expired_products'],
                'expiring_soon' => $expiringProducts,
            ], */
            'claim' => [
                'total' => $stats['total_claims'],
                'open' => $stats['open_claims'],
                'closed' => $stats['closed_claims'],
                'closed_status' => $closed_status,
                'service_type' => $claim_service_type,

                'engineerClaimsSummary' => $engineerClaimsSummary,
                'recent' => $recentClaims,
                'monthly' => $monthlyData,
            ],
            'service_center' => [
                'total' => $stats['total_service_centers'],
                'comparison' => $serviceCenterComparison,
            ],
            'brand' => [
                'total' => $stats['total_brands'],
            ],
            'metrics' => [
                'avg_customer_rating' => $stats['avg_customer_rating'],
                'avg_tat_days' => $stats['avg_tat_days'],
                'customer_rating_distribution' => $ratingDistribution,
            ],
            'aging_rpt' => [
                // 'ranges' => $ranges,
                'pending_by_claim_date' => $pending_by_claim_date,
                'completed_by_claim_date' => $completed_by_claim_date,
                'completed_by_closed_date_n_delivered' => $completed_by_closed_date_n_delivered,
                'delivered_by_claim_date' => $delivered_by_claim_date,
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

        $productQuery = Product::query();
        $claimQuery = Claim::query();
        $workOrderQuery = WorkOrder::query();

        if ($user->isBrandRestricted()) {
            $brandIds = $user->accessibleBrandIds();
            $productQuery->whereIn('brand_id', $brandIds);
            $claimQuery->whereHas('product', function ($q) use ($brandIds) {
                $q->whereIn('brand_id', $brandIds);
            });
            $workOrderQuery->whereHas('claim.product', function ($q) use ($brandIds) {
                $q->whereIn('brand_id', $brandIds);
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $serviceCenterIds = $user->accessibleServiceCenterIds();
            $claimQuery->whereIn('service_center_id', $serviceCenterIds);
            $workOrderQuery->whereIn('service_center_id', $serviceCenterIds);
        }

        $stats = [
            'total_products' => $productQuery->count(),
            'active_products' => (clone $productQuery)->active()->count(),
            'expired_products' => (clone $productQuery)->expired()->count(),
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
            'avg_customer_rating' => (int) (clone $claimQuery)->whereNotNull('customer_rating')->avg('customer_rating') ?? 0,
            'avg_tat_days' => (int) (clone $claimQuery)->whereNotNull('tat')->avg('tat') ?? 0,
        ];

        return $this->success($stats);
    }

    public function claimStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Claim::query();

        if ($user->isBrandRestricted()) {
            $query->whereHas('product', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $query->whereIn('service_center_id', $user->accessibleServiceCenterIds());
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
            $query->whereHas('claim.product', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $query->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

        $claimQuery = Claim::query();
        if ($user->isBrandRestricted()) {
            $claimQuery->whereHas('product', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }
        if ($user->isServiceCenterRestricted()) {
            $claimQuery->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->pending()->count(),
            'in_progress' => (clone $query)->inProgress()->count(),
            'completed' => (clone $query)->completed()->count(),
            'delivered' => (clone $query)->delivered()->count(),
            'avg_tat_days' => (int) (clone $claimQuery)->whereNotNull('tat')->avg('tat') ?? 0,
        ];

        return $this->success($stats);
    }

    public function recentClaims(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Claim::with(['product.brand', 'serviceCenter']);

        if ($user->isBrandRestricted()) {
            $query->whereHas('product', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $query->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

        $claims = $query->orderBy('created_at', 'desc')->limit(10)->get();

        return $this->success($claims);
    }

    public function recentWorkOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = WorkOrder::with(['claim.product.brand', 'serviceCenter']);

        if ($user->isBrandRestricted()) {
            $query->whereHas('claim.product', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $query->whereIn('service_center_id', $user->accessibleServiceCenterIds());
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
                'total_products' => $brand->products()->count(),
                'active_products' => $brand->products()->active()->count(),
                'total_claims' => $brand->products()->withCount('claims')->get()->sum('claims_count'),
                'open_work_orders' => WorkOrder::whereHas('claim.product', function ($q) use ($brand) {
                    $q->where('brand_id', $brand->id);
                })->where('status', '!=', 'Delivered')->count(),
            ];
        });

        return $this->success($brands);
    }

    public function serviceCenterPerformance(Request $request): JsonResponse
    {
        $user = $request->user();

        $serviceCenterQuery = ServiceCenter::query();

        if ($user->isBrandRestricted()) {
            $brandIds = $user->accessibleBrandIds();
            $serviceCenterQuery->whereHas('brands', fn($q) => $q->whereIn('wms_brands.id', $brandIds));
        }

        if ($user->isServiceCenterRestricted()) {
            $serviceCenterQuery->whereIn('id', $user->accessibleServiceCenterIds());
        }

        $serviceCenters = $serviceCenterQuery->with(['claims' => function ($query) {
            $query->select('id', 'service_center_id', 'status', 'customer_rating');
        }])->get()->map(function ($center) {
            return [
                'id' => $center->id,
                'title' => $center->title,
                'assigned_count' => $center->claims()->count(),
                'completed_count' => $center->claims()->closed()->count(),
                'delivered_count' => $center->claims()->where('status', 'Delivered')->count(),
                'avg_rating' => (int) $center->claims()->whereNotNull('customer_rating')->avg('customer_rating') ?? 0,
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
            $query->whereHas('product', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $query->whereIn('service_center_id', $user->accessibleServiceCenterIds());
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

        $query = Product::with(['brand', 'category'])
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>=', now());

        if ($user->isBrandRestricted()) {
            $query->whereIn('brand_id', $user->accessibleBrandIds());
        }

        $products = $query->orderBy('end_date', 'asc')->paginate($request->limit ?? 15);

        return $this->success($products);
    }

    public function clientDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalClaims = Claim::where('customer_user_id', $user->id)->count();

        $statusWise = Claim::where('customer_user_id', $user->id)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $recentClaims = Claim::with(['product.brand', 'serviceCenter', 'workOrder'])
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
