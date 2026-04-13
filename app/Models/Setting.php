<?php

namespace App\Models;

class Setting extends BaseModel
{
    protected $table = 'wms_settings';

    protected $fillable = [
        'setting_name',
        'setting_value',
        'type'
    ];

    protected $casts = [
        'setting_value' => 'string',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('setting_name', $key)->first();

        return $setting ? $setting->setting_value : $default;
    }

    public static function set(string $key, $value, string $type = 'app'): void
    {
        static::updateOrCreate(
            ['setting_name' => $key],
            ['setting_value' => $value, 'type' => $type]
        );
    }
}
