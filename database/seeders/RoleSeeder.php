<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'title' => 'Super Admin',
                'permissions' => [
                    'warranties' => ['view', 'create', 'edit', 'delete'],
                    'claims' => ['view', 'create', 'edit', 'delete', 'convert_to_wo'],
                    'work_orders' => ['view', 'create', 'edit', 'delete', 'assign'],
                    'service_centers' => ['view', 'create', 'edit', 'delete'],
                    'brands' => ['view', 'create', 'edit', 'delete'],
                    'categories' => ['view', 'create', 'edit', 'delete'],
                    'users' => ['view', 'create', 'edit', 'delete'],
                    'settings' => ['view', 'edit'],
                    'reports' => ['view'],
                ],
            ],
            [
                'title' => 'Manager',
                'permissions' => [
                    'warranties' => ['view', 'create', 'edit'],
                    'claims' => ['view', 'create', 'edit', 'convert_to_wo'],
                    'work_orders' => ['view', 'create', 'edit', 'assign'],
                    'service_centers' => ['view'],
                    'brands' => ['view'],
                    'categories' => ['view'],
                    'users' => ['view'],
                    'settings' => ['view'],
                    'reports' => ['view'],
                ],
            ],
            [
                'title' => 'Technician',
                'permissions' => [
                    'warranties' => ['view'],
                    'claims' => ['view', 'edit'],
                    'work_orders' => ['view', 'edit'],
                    'service_centers' => ['view'],
                    'brands' => ['view'],
                    'categories' => ['view'],
                ],
            ],
            [
                'title' => 'Viewer',
                'permissions' => [
                    'warranties' => ['view'],
                    'claims' => ['view'],
                    'work_orders' => ['view'],
                    'service_centers' => ['view'],
                    'brands' => ['view'],
                    'categories' => ['view'],
                ],
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
