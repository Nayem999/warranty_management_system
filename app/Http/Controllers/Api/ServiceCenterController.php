<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCenterResource;
use App\Models\ServiceCenter;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceCenterController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = ServiceCenter::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('city', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $serviceCenters = $query->orderBy('display_order', 'asc')->paginate($request->limit ?? 15);

        return $this->success(ServiceCenterResource::collection($serviceCenters));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'address' => 'nullable|string',
            'uan' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'exists:wms_brands,id',
            'working_hours' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploadFile($request->file('logo'), 'service-centers');
        }

        $serviceCenter = ServiceCenter::create($data);

        return $this->created($serviceCenter, 'Service center created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $serviceCenter = ServiceCenter::find($id);

        if (! $serviceCenter) {
            return $this->notFound('Service center not found.');
        }

        return $this->success(new ServiceCenterResource($serviceCenter));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $serviceCenter = ServiceCenter::find($id);

        if (! $serviceCenter) {
            return $this->notFound('Service center not found.');
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'uan' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'exists:wms_brands,id',
            'working_hours' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $this->deleteFile($serviceCenter->logo);
            $data['logo'] = $this->uploadFile($request->file('logo'), 'service-centers');
        }

        $serviceCenter->update($data);

        return $this->success(new ServiceCenterResource($serviceCenter), 'Service center updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $serviceCenter = ServiceCenter::find($id);

        if (! $serviceCenter) {
            return $this->notFound('Service center not found.');
        }

        if ($serviceCenter->workOrders()->count() > 0) {
            return $this->error('Cannot delete service center with associated work orders.');
        }

        $this->deleteFile($serviceCenter->logo);
        $serviceCenter->delete();

        return $this->deleted('Service center deleted successfully.');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $serviceCenter = ServiceCenter::find($id);

        if (! $serviceCenter) {
            return $this->notFound('Service center not found.');
        }

        $serviceCenter->is_active = ! $serviceCenter->is_active;
        $serviceCenter->save();

        return $this->success(new ServiceCenterResource($serviceCenter), 'Service center status updated successfully.');
    }

    public function byBrand(Request $request): JsonResponse
    {
        $brandId = $request->query('brand_id');

        if (! $brandId) {
            return $this->error('brand_id query parameter is required.');
        }

        $serviceCenters = ServiceCenter::where('is_active', true)
            ->where(function ($query) use ($brandId) {
                $query->whereJsonContains('brand_ids', (int) $brandId)
                    ->orWhereJsonContains('brand_ids', (string) $brandId);
            })
            ->orderBy('display_order', 'asc')
            ->get();

        return $this->success(ServiceCenterResource::collection($serviceCenters));
    }

    protected function uploadFile($file, string $folder): string
    {
        $path = $file->store("uploads/{$folder}", 'public');

        return $path;
    }

    protected function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
