<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = ProductCategory::query()->with(['brand']);

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('short_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $categories = $query->orderBy('name')->paginate($request->per_page ?? 15);

        return $this->success($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $category = ProductCategory::create($data);

        return $this->created($category, 'Category created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $category = ProductCategory::with(['brand'])->find($id);

        if (!$category) {
            return $this->notFound('Category not found.');
        }

        return $this->success($category);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return $this->notFound('Category not found.');
        }

        $data = $request->validated();

        $category->update($data);

        return $this->success($category, 'Category updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return $this->notFound('Category not found.');
        }

        if ($category->warranties()->count() > 0) {
            return $this->error('Cannot delete category with associated warranties.');
        }

        $category->delete();

        return $this->deleted('Category deleted successfully.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return $this->notFound('Category not found.');
        }

        $category->status = $category->status === 'active' ? 'inactive' : 'active';
        $category->save();

        return $this->success($category, 'Category status updated successfully.');
    }
}
