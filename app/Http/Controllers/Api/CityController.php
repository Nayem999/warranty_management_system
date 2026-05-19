<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = City::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $cities = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($cities);
    }

    public function show(int $id): JsonResponse
    {
        $city = City::find($id);

        if (! $city) {
            return $this->notFound('City not found.');
        }

        return $this->success($city);
    }

    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            $city = City::create($validated);

            DB::commit();

            return $this->created($city, 'City created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $city = City::find($id);

            if (! $city) {
                return $this->notFound('City not found.');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'code' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:active,inactive',
            ]);

            $city->update($validated);

            DB::commit();

            return $this->success($city, 'City updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $city = City::find($id);

        if (! $city) {
            return $this->notFound('City not found.');
        }

        $city->delete();

        return $this->deleted('City deleted successfully.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $city = City::find($id);

        if (! $city) {
            return $this->notFound('City not found.');
        }

        $city->status = $city->status === 'active' ? 'inactive' : 'active';
        $city->save();

        return $this->success($city, 'City status updated successfully.');
    }

    public function cities_list(Request $request): JsonResponse
    {
        $query = City::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $query->where('status', 'active');
        $cities = $query->orderBy('name', 'asc')->get();

        return $this->success($cities);
    }
}