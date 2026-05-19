<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkOrderPartResource;
use App\Models\Part;
use App\Models\WorkOrderPart;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'brand_id' => 'required|exists:wms_brands,id',
                'category_id' => 'nullable|exists:wms_product_categories,id',
                'sub_category_id' => 'nullable|exists:wms_product_categories,id',
                'part_id' => 'required|string|unique:wms_parts,part_id',
                'part_description' => 'required|string',
                'is_active' => 'nullable|boolean',
            ]);

            $part = Part::create($data);

            DB::commit();

            return $this->created($part->load(['brand', 'category']), 'Part created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
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
        DB::beginTransaction();
        try {
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

            DB::commit();

            return $this->success($part->load(['brand', 'category']), 'Part updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
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

    public function usageHistory(Request $request, int $id): JsonResponse
    {
        $part = Part::find($id);

        if (!$part) {
            return $this->notFound('Part not found.');
        }

        $history = WorkOrderPart::with([
            'workOrder.claim.customer',
            'workOrder.claim.engineer',
            'workOrder.claim.creator',
            'workOrder.claim.courierIn',
            'workOrder.claim.courierOut',
            'workOrder.claim.product.category',
            'workOrder.claim.product.brand',
            'workOrder.claim.product.subCategory',
            'workOrder.serviceCenter:id,title',
            'part',
            'faultyPart',
        ])
            ->where('part_id', $id)
            ->orderBy('install_date_time', 'desc')
            ->paginate($request->limit ?? 15);

        return $this->success(WorkOrderPartResource::collection($history));
    }
}
