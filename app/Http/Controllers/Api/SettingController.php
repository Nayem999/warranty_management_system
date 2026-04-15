<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use App\Traits\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    use ApiResponse, FileUpload;

    public function index(): JsonResponse
    {
        $defaultSettings = [
            'company_name' => ['value' => 'SNP Distribution', 'type' => 'app'],
            'company_email' => ['value' => 'info@snpdist.com', 'type' => 'app'],
            'company_phone' => ['value' => '', 'type' => 'app'],
            'company_address' => ['value' => '', 'type' => 'app'],
            'company_logo' => ['value' => '', 'type' => 'app'],
            'company_website' => ['value' => '', 'type' => 'app'],
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
            // $backendUrl = rtrim(config('app.backend_url', env('BACKEND_URL', '')), '/');
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
        $keyArray = explode(",", $key);
        $settings = Setting::whereIn('setting_name', $keyArray)->get();

        if ($settings->isEmpty()) {
            return $this->notFound('Setting not found.');
        }

        $data = $settings->map(function ($setting) {
            $value = $setting->setting_value;

            // $backendUrl = rtrim(config('app.backend_url', env('BACKEND_URL', '')), '/');

            return [
                'key'   => $setting->setting_name,
                'value' => $value,
                'type'  => $setting->type,
            ];
        });

        return $this->success($data);
    }

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'nullable|string',
            'settings.*.input_type' => 'nullable|string',
        ]);

        foreach ($data['settings'] as $setting) {
            $value = $setting['value'] ?? '';
            $inputType = $setting['type'] ?? 'text';

            if ($inputType === 'image' && $request->hasFile('settings')) {
                foreach ($request->file('settings') as $fileSetting) {
                    if ($fileSetting['key'] === $setting['key'] && $fileSetting->getClientOriginalName()) {
                        $paths = $this->uploadFiles([$fileSetting], 'settings');
                        $value = $paths[0] ?? '';
                        break;
                    }
                }
            }

            Setting::updateOrCreate(
                ['setting_name' => $setting['key']],
                [
                    'setting_value' => $value,
                    'type' => $setting['type'] ?? 'app'
                ]
            );
        }

        return $this->success(null, 'Settings saved successfully.');
    }

    public function updateAll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key'   => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type'  => 'nullable|string',
        ]);

        foreach ($data['settings'] as $item) {

            $key       = $item['key'];
            $inputType = $item['type'] ?? 'text';
            $value     = $item['value'] ?? '';

            if ($inputType === 'image' && !empty($value)) {

                $prevSetting = Setting::where('setting_name', $key)->first();
                $oldFilePath = $prevSetting->setting_value ?? null;

                if (str_starts_with($value, 'data:image')) {

                    preg_match('/data:image\/(\w+);base64,/', $value, $matches);
                    $ext = $matches[1] ?? 'jpg';

                    $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $value);
                    $decoded = base64_decode($base64Data);

                    if ($decoded === false) {
                        continue;
                    }
                    if ($oldFilePath) {
                        $this->deleteFile($oldFilePath);
                    }

                    // ✅ Use original filename
                    $filename = Str::slug($key) . '.' . $ext;

                    $filePath = "uploads/settings/{$filename}";
                    Storage::disk('public')->put($filePath, $decoded);

                    $value = $filePath;
                } else {
                    $value = $oldFilePath;
                }
            }

            Setting::updateOrCreate(
                ['setting_name' => $key],
                [
                    'setting_value' => $value,
                    'type'          => $inputType
                ]
            );
        }

        return $this->success(null, 'All settings updated successfully.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $paths = $this->uploadFiles([$request->file('image')], 'settings');

        Setting::updateOrCreate(
            ['setting_name' => $data['key']],
            [
                'setting_value' => $paths[0],
                'input_type' => 'image',
            ]
        );

        $setting = Setting::where('setting_name', $data['key'])->first();

        return $this->success([
            'key' => $setting->setting_name,
            'value' => $setting->setting_value,
            'value_url' => $setting->value_url,
        ], 'Image uploaded successfully.');
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

    public function inputTypes(): JsonResponse
    {
        return $this->success(Setting::inputTypes());
    }
}
