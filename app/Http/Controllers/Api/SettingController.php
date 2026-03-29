<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('setting_value', 'setting_name');
        
        $defaultSettings = [
            'company_name' => 'SNP Distribution',
            'company_email' => 'info@snpdist.com',
            'company_phone' => '',
            'company_address' => '',
            'company_logo' => '',
            'warranty_default_days' => '365',
            'smtp_host' => '',
            'smtp_port' => '',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => 'SNP Distribution',
            'date_format' => 'Y-m-d',
            'timezone' => 'Asia/Dhaka',
            'app_currency' => 'BDT',
        ];

        $merged = array_merge($defaultSettings, $settings->toArray());

        return $this->success($merged);
    }

    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('setting_name', $key)->first();

        if (!$setting) {
            return $this->notFound('Setting not found.');
        }

        return $this->success([
            'key' => $setting->setting_name,
            'value' => $setting->setting_value,
            'type' => $setting->type,
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'required|string',
            'value' => 'nullable',
            'type' => 'nullable|string',
        ]);

        Setting::updateOrCreate(
            ['setting_name' => $data['key']],
            [
                'setting_value' => $data['value'],
                'type' => $data['type'] ?? 'app',
            ]
        );

        return $this->success(null, 'Setting saved successfully.');
    }

    public function destroy(string $key): JsonResponse
    {
        $setting = Setting::where('setting_name', $key)->first();

        if (!$setting) {
            return $this->notFound('Setting not found.');
        }

        $setting->delete();

        return $this->deleted('Setting deleted successfully.');
    }
}
