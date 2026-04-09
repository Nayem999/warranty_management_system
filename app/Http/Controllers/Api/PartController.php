<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\WorkOrderPart;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Part::query()->with(['brand', 'category', 'subCategory']);

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('part_id', 'like', "%{$request->search}%")
                    ->orWhere('part_description', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $parts = $query->orderBy('part_id')->paginate($request->limit ?? 15);

        return $this->success($parts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brand_id' => 'required|exists:wms_brands,id',
            'category_id' => 'nullable|exists:wms_product_categories,id',
            'sub_category_id' => 'nullable|exists:wms_product_categories,id',
            'part_id' => 'required|string|unique:wms_parts,part_id',
            'part_description' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $part = Part::create($data);

        return $this->created($part->load(['brand', 'category']), 'Part created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $part = Part::with(['brand', 'category', 'subCategory'])->find($id);

        if (! $part) {
            return $this->notFound('Part not found.');
        }

        return $this->success($part);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $part = Part::find($id);

        if (! $part) {
            return $this->notFound('Part not found.');
        }

        $data = $request->validate([
            'brand_id' => 'sometimes|exists:wms_brands,id',
            'category_id' => 'nullable|exists:wms_product_categories,id',
            'sub_category_id' => 'nullable|exists:wms_product_categories,id',
            'part_id' => 'sometimes|string|unique:wms_parts,part_id,' . $id,
            'part_description' => 'sometimes|string',
            'is_active' => 'nullable|boolean',
        ]);

        $part->update($data);

        return $this->success($part->load(['brand', 'category']), 'Part updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $part = Part::find($id);

        if (! $part) {
            return $this->notFound('Part not found.');
        }

        if ($part->workOrderParts()->count() > 0) {
            return $this->error('Cannot delete part with associated work orders.');
        }

        $part->delete();

        return $this->deleted('Part deleted successfully.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $part = Part::find($id);

        if (! $part) {
            return $this->notFound('Part not found.');
        }

        $part->is_active = ! $part->is_active;
        $part->save();

        return $this->success($part, 'Part status updated successfully.');
    }

    public function workOrderUsageHistory(Request $request): JsonResponse
    {
        $query = WorkOrderPart::with([
            'workOrder.claim:id,claim_number,customer_firstname,customer_lastname',
            'workOrder.serviceCenter:id,title',
            'part.brand',
            'part.category',
            'part.subCategory',
            'workOrder.assignedBy:id,first_name,last_name,email',
        ])
            ->when($request->filled('created_at'), function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            })
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            })
            ->when($request->filled('part_id'), function ($q) use ($request) {
                $q->where('part_id', $request->part_id);
            })
            ->orderBy('install_date_time', 'desc');

        $history = $query->paginate($request->limit ?? 15);

        return $this->success($history);
    }
}
