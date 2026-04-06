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

    public function categories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    public function userBrandAccess(): HasMany
    {
        return $this->hasMany(UserBrandAccess::class);
    }

    public function getServiceCentersAttribute()
    {
        return ServiceCenter::where('is_active', true)
            ->where(function ($query) {
                $query->whereJsonContains('brand_ids', $this->id)
                    ->orWhereJsonContains('brand_ids', (string) $this->id);
            })
            ->get();
    }
}
