<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCenter extends BaseModel
{
    protected $table = 'wms_service_centers';

    protected $fillable = [
        'title',
        'address',
        'uan',
        'email',
        'brand_ids',
        'working_hours',
        'logo',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'brand_ids' => 'array',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        $backendUrl = rtrim(config('app.backend_url', env('BACKEND_URL', '')), '/');

        return $backendUrl.'/storage/uploads/'.$this->logo;
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'wms_service_centers_brands', 'service_center_id', 'brand_id')
            ->where('wms_brands.is_active', true);
    }

    public function getBrandIdsAttribute($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return $value ?? [];
    }

    public function getBrandsListAttribute()
    {
        $brandIds = $this->brand_ids;

        if (empty($brandIds) || ! is_array($brandIds)) {
            return collect();
        }

        $ids = array_filter($brandIds, fn ($id) => is_numeric($id));

        if (empty($ids)) {
            return collect();
        }

        return Brand::where('status', 'active')
            ->whereIn('id', $ids)
            ->get();
    }
}
