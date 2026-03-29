<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('title', 'Super Admin')->first();

        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@snpdist.com',
            'password' => Hash::make('Admin@1234'),
            'user_type' => 'admin',
            'is_admin' => true,
            'role_id' => $superAdminRole?->id,
            'status' => 'active',
            'phone' => '+8801234567890',
            'job_title' => 'System Administrator',
        ]);
    }
}
