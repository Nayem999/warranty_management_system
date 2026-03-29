<?php

namespace Database\Seeders;

use App\Models\ServiceCenter;
use Illuminate\Database\Seeder;

class ServiceCenterSeeder extends Seeder
{
    public function run(): void
    {
        $serviceCenters = [
            [
                'title' => 'Dhaka Service Center',
                'address' => '123 Gulshan Avenue, Dhaka-1212',
                'uan' => '09612345678',
                'email' => 'dhaka@snpdist.com',
                'working_hours' => '9:00 AM - 6:00 PM (Sat-Thu)',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Chittagong Service Center',
                'address' => '45 Agrabad C/A, Chittagong-4100',
                'uan' => '09612345679',
                'email' => 'ctg@snpdist.com',
                'working_hours' => '9:00 AM - 6:00 PM (Sat-Thu)',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Sylhet Service Center',
                'address' => 'Zindabazar, Sylhet-3100',
                'uan' => '09612345680',
                'email' => 'sylhet@snpdist.com',
                'working_hours' => '9:00 AM - 6:00 PM (Sat-Thu)',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Khulna Service Center',
                'address' => 'Khalishpur, Khulna-9000',
                'uan' => '09612345681',
                'email' => 'khulna@snpdist.com',
                'working_hours' => '9:00 AM - 6:00 PM (Sat-Thu)',
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Barisal Service Center',
                'address' => 'Band Road, Barisal-1200',
                'uan' => '09612345682',
                'email' => 'barisal@snpdist.com',
                'working_hours' => '9:00 AM - 6:00 PM (Sat-Thu)',
                'display_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($serviceCenters as $center) {
            ServiceCenter::create($center);
        }
    }
}
