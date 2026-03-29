<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends BaseModel
{
    protected $table = 'wms_product_categories';

    protected $fillable = [
        'brand_id',
        'name',
        'short_name',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }
}
