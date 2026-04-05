<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Courier::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $couriers = $query->orderBy('name')->paginate($request->limit ?? 15);

        return $this->success($couriers);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $courier = Courier::create($data);

        return $this->created($courier, 'Courier created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $courier = Courier::find($id);

        if (! $courier) {
            return $this->notFound('Courier not found.');
        }

        return $this->success($courier);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $courier = Courier::find($id);

        if (! $courier) {
            return $this->notFound('Courier not found.');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $courier->update($data);

        return $this->success($courier, 'Courier updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $courier = Courier::find($id);

        if (! $courier) {
            return $this->notFound('Courier not found.');
        }

        if ($courier->workOrders()->count() > 0) {
            return $this->error('Cannot delete courier with associated work orders.');
        }

        $courier->delete();

        return $this->deleted('Courier deleted successfully.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $courier = Courier::find($id);

        if (! $courier) {
            return $this->notFound('Courier not found.');
        }

        $courier->is_active = ! $courier->is_active;
        $courier->save();

        return $this->success($courier, 'Courier status updated successfully.');
    }
}
