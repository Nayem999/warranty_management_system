<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends BaseModel
{
    protected $table = 'wms_product_categories';

    protected $fillable = [
        'name',
        'short_name',
        'status',
        'parent_id',
        'brand_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSubcategories($query)
    {
        return $query->whereNotNull('parent_id');
    }
}

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSubcategories($query)
    {
        return $query->whereNotNull('parent_id');
    }
}
