<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Models\UserBrandAccess;
use App\Models\UserServiceCenterAccess;
use App\Traits\ApiResponse;
use App\Traits\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use ApiResponse, FileUpload;

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['role', 'brands']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadFile($request->file('image'), 'users');
        } elseif (! empty($data['image']) && is_string($data['image'])) {
            $data['image'] = $this->handleImageUpload($data['image'], 'users');
        }

        $password = $data['password'] ?? Str::random(12);
        $data['password'] = Hash::make($password);

        if (! isset($data['is_admin'])) {
            $data['is_admin'] = false;
        }
        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        $user = User::create($data);

        if (! empty($data['brand_ids'])) {
            foreach ($data['brand_ids'] as $brandId) {
                UserBrandAccess::create([
                    'user_id' => $user->id,
                    'brand_id' => $brandId,
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        return $this->created($user, 'User created successfully. Password: ' . $password);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['role', 'brands', 'brandAccess.brand'])->find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        return $this->success($user);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        $data = $request->validated();

        if (!isset($data['image']) || empty($data['image'])) {
            unset($data['image']);
        }

        if ($request->hasFile('image')) {
            $this->deleteFile($user->image);
            $data['image'] = $this->uploadFile($request->file('image'), 'users');
        } elseif (! empty($data['image']) && is_string($data['image'])) {
            if ($user->image !== $data['image']) {
                $this->deleteFile($user->image);
                $data['image'] = $this->handleImageUpload($data['image'], 'users');
            }
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $this->success($user, 'User updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        $user->delete();

        return $this->deleted('User deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $user = User::withTrashed()->find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        $user->restore();

        return $this->success($user, 'User restored successfully.');
    }

    public function trashed(Request $request): JsonResponse
    {
        $users = User::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->paginate($request->limit ?? 15);

        return $this->success($users);
    }

    public function getBrandAccess(int $id): JsonResponse
    {
        $user = User::with(['brandAccess.brand'])->find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        return $this->success($user->brandAccess);
    }

    public function assignBrandAccess(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        $brandIds = $request->validate([
            'brand_ids' => 'required|array',
            'brand_ids.*' => 'exists:wms_brands,id',
        ])['brand_ids'];

        UserBrandAccess::where('user_id', $id)->delete();

        foreach ($brandIds as $brandId) {
            UserBrandAccess::create([
                'user_id' => $user->id,
                'brand_id' => $brandId,
                'created_by' => $request->user()->id,
            ]);
        }

        return $this->success($user->load('brandAccess.brand'), 'Brand access assigned successfully.');
    }

    public function revokeBrandAccess(Request $request, int $id, int $brandId): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        UserBrandAccess::where('user_id', $id)
            ->where('brand_id', $brandId)
            ->delete();

        return $this->success(null, 'Brand access revoked successfully.');
    }

    public function getServiceCenterAccess(int $id): JsonResponse
    {
        $user = User::with(['serviceCenterAccess.serviceCenter'])->find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        return $this->success($user->serviceCenterAccess);
    }

    public function assignServiceCenterAccess(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        $serviceCenterIds = $request->validate([
            'service_center_ids' => 'required|array',
            'service_center_ids.*' => 'exists:wms_service_centers,id',
        ])['service_center_ids'];

        UserServiceCenterAccess::where('user_id', $id)->delete();

        foreach ($serviceCenterIds as $serviceCenterId) {
            UserServiceCenterAccess::create([
                'user_id' => $user->id,
                'service_center_id' => $serviceCenterId,
                'created_by' => $request->user()->id,
            ]);
        }

        return $this->success($user->load('serviceCenterAccess.serviceCenter'), 'Service center access assigned successfully.');
    }

    public function revokeServiceCenterAccess(Request $request, int $id, int $serviceCenterId): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        UserServiceCenterAccess::where('user_id', $id)
            ->where('service_center_id', $serviceCenterId)
            ->delete();

        return $this->success(null, 'Service center access revoked successfully.');
    }

    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return $this->success($user, 'User status updated successfully.');
    }

    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        $permissions = $request->validate([
            'permissions' => 'required|array',
        ])['permissions'];

        $user->update(['personal_permissions' => $permissions]);

        return $this->success($user->fresh(), 'Personal permissions assigned successfully.');
    }

    public function removePersonalPermissions(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        $user->update(['personal_permissions' => null]);

        return $this->success($user->fresh(), 'Personal permissions removed. Role permissions will be used.');
    }

    public function getPermissions(int $id): JsonResponse
    {
        $user = User::with('role')->find($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        return $this->success([
            'role_permissions' => $user->role?->permissions ?? [],
            'personal_permissions' => $user->personal_permissions,
            'effective_permissions' => $user->permissions,
        ]);
    }

    public function handleImageUpload(string $base64Data, string $folder): string
    {
        if (empty($base64Data)) {
            return '';
        }

        if (str_starts_with($base64Data, 'data:')) {
            $ext = 'jpg';
            if (preg_match('/data:image\/(\w+);/', $base64Data, $matches)) {
                $ext = $matches[1];
            }

            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
            $base64Data = base64_decode($base64Data);

            if ($base64Data === false) {
                return '';
            }

            $filename = Str::uuid() . '.' . $ext;
            $path = "uploads/{$folder}/{$filename}";
            Storage::disk('public')->put($path, $base64Data);

            return $path;
        }

        return $base64Data;
    }
}
