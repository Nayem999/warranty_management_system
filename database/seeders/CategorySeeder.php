<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['brand_id' => 1, 'name' => 'Mobile Phone', 'short_name' => 'Mobile'],
            ['brand_id' => 1, 'name' => 'Television', 'short_name' => 'TV'],
            ['brand_id' => 1, 'name' => 'Refrigerator', 'short_name' => 'Fridge'],
            ['brand_id' => 1, 'name' => 'Air Conditioner', 'short_name' => 'AC'],
            ['brand_id' => 1, 'name' => 'Washing Machine', 'short_name' => 'WM'],
            ['brand_id' => 2, 'name' => 'Television', 'short_name' => 'TV'],
            ['brand_id' => 2, 'name' => 'Refrigerator', 'short_name' => 'Fridge'],
            ['brand_id' => 2, 'name' => 'Air Conditioner', 'short_name' => 'AC'],
            ['brand_id' => 2, 'name' => 'Washing Machine', 'short_name' => 'WM'],
            ['brand_id' => 3, 'name' => 'Television', 'short_name' => 'TV'],
            ['brand_id' => 3, 'name' => 'Audio System', 'short_name' => 'Audio'],
            ['brand_id' => 3, 'name' => 'Camera', 'short_name' => 'Cam'],
            ['brand_id' => 4, 'name' => 'Refrigerator', 'short_name' => 'Fridge'],
            ['brand_id' => 4, 'name' => 'Air Conditioner', 'short_name' => 'AC'],
            ['brand_id' => 4, 'name' => 'Washing Machine', 'short_name' => 'WM'],
            ['brand_id' => 5, 'name' => 'Mobile Phone', 'short_name' => 'Mobile'],
            ['brand_id' => 5, 'name' => 'Television', 'short_name' => 'TV'],
            ['brand_id' => 5, 'name' => 'Refrigerator', 'short_name' => 'Fridge'],
        ];

        foreach ($categories as $category) {
            ProductCategory::create($category);
        }
    }
}
