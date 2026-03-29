<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Samsung',
                'short_name' => 'SMG',
                'description' => 'Samsung Electronics - Premium electronics brand',
                'status' => 'active',
            ],
            [
                'name' => 'LG',
                'short_name' => 'LG',
                'description' => 'LG Electronics - Home appliances and electronics',
                'status' => 'active',
            ],
            [
                'name' => 'Sony',
                'short_name' => 'SNY',
                'description' => 'Sony Corporation - Electronics and entertainment',
                'status' => 'active',
            ],
            [
                'name' => 'Haier',
                'short_name' => 'HAI',
                'description' => 'Haier Group - Home appliances manufacturer',
                'status' => 'active',
            ],
            [
                'name' => 'Walton',
                'short_name' => 'WLN',
                'description' => 'Walton Group - Bangladeshi electronics brand',
                'status' => 'active',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
