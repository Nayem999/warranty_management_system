<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Models\Brand;
use App\Models\WorkOrder;
use App\Traits\ApiResponse;
use App\Traits\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ApiResponse, FileUpload;

    public function index(Request $request): JsonResponse
    {
        $query = Brand::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('short_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $brands = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($brands);
    }

    public function show(int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        return $this->success($brand);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploadFile($request->file('logo'), 'brands');
        } elseif (! empty($data['logo']) && is_string($data['logo'])) {
            $data['logo'] = $this->handleImageUpload($data['logo'], 'brands');
        }

        $brand = Brand::create($data);

        return $this->created($brand, 'Brand created successfully.');
    }

    public function update(UpdateBrandRequest $request, int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        $data = $request->validated();
        if (!isset($data['logo']) || empty($data['logo'])) {
            unset($data['logo']);
        }

        if ($request->hasFile('logo')) {
            $this->deleteFile($brand->logo);
            $data['logo'] = $this->uploadFile($request->file('logo'), 'brands');
        } elseif (! empty($data['logo']) && is_string($data['logo'])) {
            if ($brand->logo !== $data['logo']) {
                $this->deleteFile($brand->logo);
                $data['logo'] = $this->handleImageUpload($data['logo'], 'brands');
            }
        }

        $brand->update($data);

        return $this->success($brand, 'Brand updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        $this->deleteFile($brand->logo);
        $brand->delete();

        return $this->deleted('Brand deleted successfully.');
    }

    public function products(Request $request, int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        $products = \App\Models\Product::where('brand_id', $id)
            ->with(['category', 'creator'])
            ->orderBy('id', 'desc')
            ->paginate($request->limit ?? 15);

        return $this->success($products);
    }

    public function stats(int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        $totalProducts = \App\Models\Product::where('brand_id', $id)->count();
        $activeProducts = \App\Models\Product::where('brand_id', $id)->active()->count();
        $totalClaims = \App\Models\Claim::whereHas('product', function ($query) use ($id) {
            $query->where('brand_id', $id);
        })->count();
        // $openWorkOrders = \App\Models\WorkOrder::whereHas('claim.product', function ($query) use ($id) {
        //     $query->where('brand_id', $id);
        // })->where('status', '!=', 'Delivered')->count();

        return $this->success([
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'total_claims' => $totalClaims,
            // 'open_work_orders' => $openWorkOrders,
        ]);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        $brand->status = $brand->status === 'active' ? 'inactive' : 'active';
        $brand->save();

        return $this->success($brand, 'Brand status updated successfully.');
    }

    public function brands_list(Request $request): JsonResponse
    {
        $query = Brand::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('short_name', 'like', "%{$request->search}%");
            });
        }

        $query->where('status', "active");
        $brands = $query->orderBy('display_order', 'asc')->get();

        return $this->success($brands);
    }
}
