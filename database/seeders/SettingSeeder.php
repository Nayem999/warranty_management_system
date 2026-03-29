<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['setting_name' => 'company_name', 'setting_value' => 'SNP Distribution', 'type' => 'app'],
            ['setting_name' => 'company_email', 'setting_value' => 'info@snpdist.com', 'type' => 'app'],
            ['setting_name' => 'company_phone', 'setting_value' => '+8801234567890', 'type' => 'app'],
            ['setting_name' => 'company_address', 'setting_value' => 'Dhaka, Bangladesh', 'type' => 'app'],
            ['setting_name' => 'company_logo', 'setting_value' => '', 'type' => 'app'],
            ['setting_name' => 'warranty_default_days', 'setting_value' => '365', 'type' => 'app'],
            ['setting_name' => 'smtp_host', 'setting_value' => '', 'type' => 'smtp'],
            ['setting_name' => 'smtp_port', 'setting_value' => '587', 'type' => 'smtp'],
            ['setting_name' => 'smtp_username', 'setting_value' => '', 'type' => 'smtp'],
            ['setting_name' => 'smtp_password', 'setting_value' => '', 'type' => 'smtp'],
            ['setting_name' => 'smtp_from_email', 'setting_value' => 'noreply@snpdist.com', 'type' => 'smtp'],
            ['setting_name' => 'smtp_from_name', 'setting_value' => 'SNP Distribution', 'type' => 'smtp'],
            ['setting_name' => 'date_format', 'setting_value' => 'Y-m-d', 'type' => 'app'],
            ['setting_name' => 'timezone', 'setting_value' => 'Asia/Dhaka', 'type' => 'app'],
            ['setting_name' => 'app_currency', 'setting_value' => 'BDT', 'type' => 'app'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
