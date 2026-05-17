<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends BaseModel
{
    protected $table = 'wms_brands';

    protected $fillable = [
        'name',
        'short_name',
        'logo',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected $appends = [
        'service_centers',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        return $this->logo;
    }

    public function products(): HasMany
    {
        return $this->hasMany(\App\Models\Product::class);
    }

    public function userBrandAccess(): HasMany
    {
        return $this->hasMany(UserBrandAccess::class);
    }

    public function getServiceCentersAttribute()
    {
        return ServiceCenter::where('is_active', true)
            ->whereHas('brands', function ($query) {
                $query->where('wms_brands.id', $this->id);
            })
            ->get();
    }
}
