<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $defaultSettings = [
            'company_name' => ['value' => 'SNP Distribution', 'type' => 'app'],
            'company_email' => ['value' => 'info@snpdist.com', 'type' => 'app'],
            'company_phone' => ['value' => '', 'type' => 'app'],
            'company_address' => ['value' => '', 'type' => 'app'],
            'company_logo' => ['value' => '', 'type' => 'app'],
            'warranty_default_days' => ['value' => '365', 'type' => 'app'],
            'smtp_host' => ['value' => '', 'type' => 'smtp'],
            'smtp_port' => ['value' => '', 'type' => 'smtp'],
            'smtp_username' => ['value' => '', 'type' => 'smtp'],
            'smtp_password' => ['value' => '', 'type' => 'smtp'],
            'smtp_from_email' => ['value' => '', 'type' => 'smtp'],
            'smtp_from_name' => ['value' => 'SNP Distribution', 'type' => 'smtp'],
            'date_format' => ['value' => 'Y-m-d', 'type' => 'app'],
            'timezone' => ['value' => 'Asia/Dhaka', 'type' => 'app'],
            'app_currency' => ['value' => 'BDT', 'type' => 'app'],
        ];

        $dbSettings = Setting::all()->keyBy('setting_name')->map(function ($item) {
            return [
                'value' => $item->setting_value,
                'type' => $item->type,
            ];
        });

        $merged = array_merge($defaultSettings, $dbSettings->toArray());

        $result = collect($merged)->map(function ($data, $key) {
            return [
                'key' => $key,
                'value' => $data['value'],
                'type' => $data['type'],
            ];
        })->values();

        return $this->success($result);
    }

    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('setting_name', $key)->first();

        if (! $setting) {
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

        if (! $setting) {
            return $this->notFound('Setting not found.');
        }

        $setting->delete();

        return $this->deleted('Setting deleted successfully.');
    }
}
