<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\ProductCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = ProductCategory::query()->with(['parent', 'children']);

        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
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

        $categories = $query->orderBy('name')->paginate($request->limit ?? 15);

        return $this->success($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $category = ProductCategory::create($data);

            DB::commit();

            return $this->created($category->load(['parent', 'children']), 'Category created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $category = ProductCategory::with(['parent', 'children'])->find($id);

        if (! $category) {
            return $this->notFound('Category not found.');
        }

        return $this->success($category);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $category = ProductCategory::find($id);

            if (! $category) {
                return $this->notFound('Category not found.');
            }

            $data = $request->validated();

            if (isset($data['parent_id']) && $data['parent_id'] == $id) {
                return $this->error('A category cannot be its own parent.');
            }

            $category->update($data);

            DB::commit();

            return $this->success($category->load(['parent', 'children']), 'Category updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (! $category) {
            return $this->notFound('Category not found.');
        }

        if ($category->children()->count() > 0) {
            return $this->error('Cannot delete category with sub-categories. Delete sub-categories first.');
        }

        $category->delete();

        return $this->deleted('Category deleted successfully.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (! $category) {
            return $this->notFound('Category not found.');
        }

        $category->status = $category->status === 'active' ? 'inactive' : 'active';
        $category->save();

        return $this->success($category, 'Category status updated successfully.');
    }

    public function subcategories(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (! $category) {
            return $this->notFound('Category not found.');
        }

        $subcategories = $category->children()->orderBy('name')->get();

        return $this->success($subcategories);
    }

    public function parents(Request $request): JsonResponse
    {
        $query = ProductCategory::parents();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('short_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $categories = $query->orderBy('name')->get();

        return $this->success($categories);
    }
}
