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

        $brands = $query->orderBy('display_order', 'asc')->paginate($request->limit ?? 15);

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

        if ($request->hasFile('logo')) {
            $this->deleteFile($brand->logo);
            $data['logo'] = $this->uploadFile($request->file('logo'), 'brands');
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

        if ($brand->warranties()->count() > 0) {
            return $this->error('Cannot delete brand with associated warranties.');
        }

        $this->deleteFile($brand->logo);
        $brand->delete();

        return $this->deleted('Brand deleted successfully.');
    }

    public function categories(Request $request, int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        $categories = $brand->categories()
            ->with(['parent', 'children']);

        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null') {
                $categories->whereNull('parent_id');
            } else {
                $categories->where('parent_id', $request->parent_id);
            }
        }

        if ($request->has('search')) {
            $categories->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('short_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('status')) {
            $categories->where('status', $request->status);
        }

        $categories = $categories->orderBy('name')->paginate($request->limit ?? 15);

        return $this->success($categories);
    }

    public function warranties(Request $request, int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        $warranties = $brand->warranties()
            ->with(['category', 'creator'])
            ->orderBy('id', 'desc')
            ->paginate($request->limit ?? 15);

        return $this->success($warranties);
    }

    public function stats(int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (! $brand) {
            return $this->notFound('Brand not found.');
        }

        $totalWarranties = $brand->warranties()->count();
        $activeWarranties = $brand->warranties()->active()->count();
        $totalClaims = $brand->warranties()
            ->withCount('claims')
            ->get()
            ->sum('claims_count');
        $openWorkOrders = WorkOrder::whereHas('claim.warranty', function ($query) use ($id) {
            $query->where('brand_id', $id);
        })->where('status', '!=', 'Delivered')->count();

        return $this->success([
            'total_warranties' => $totalWarranties,
            'active_warranties' => $activeWarranties,
            'total_claims' => $totalClaims,
            'open_work_orders' => $openWorkOrders,
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
}
