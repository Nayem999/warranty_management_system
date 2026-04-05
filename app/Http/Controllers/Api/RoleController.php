<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Role::query();

        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $roles = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($roles);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255|unique:wms_roles,title',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create($data);

        return $this->created($role, 'Role created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->notFound('Role not found.');
        }

        return $this->success($role);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->notFound('Role not found.');
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255|unique:wms_roles,title,' . $id,
            'permissions' => 'nullable|array',
        ]);

        $role->update($data);

        return $this->success($role, 'Role updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->notFound('Role not found.');
        }

        if ($role->users()->count() > 0) {
            return $this->error('Cannot delete role with assigned users.');
        }

        $role->delete();

        return $this->deleted('Role deleted successfully.');
    }

    public function permissionsList(): JsonResponse
    {
        $permissions = [
            'warranties' => ['view', 'create', 'edit', 'delete'],
            'claims' => ['view', 'create', 'edit', 'delete', 'convert_to_wo'],
            'work_orders' => ['view', 'create', 'edit', 'delete', 'assign'],
            'service_centers' => ['view', 'create', 'edit', 'delete'],
            'brands' => ['view', 'create', 'edit', 'delete'],
            'categories' => ['view', 'create', 'edit', 'delete'],
            'users' => ['view', 'create', 'edit', 'delete'],
            'settings' => ['view', 'edit'],
            'reports' => ['view'],
        ];

        return $this->success($permissions);
    }
}
