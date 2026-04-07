<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends BaseModel
{
    protected $table = 'wms_parts';

    protected $fillable = [
        'brand_id',
        'category_id',
        'sub_category_id',
        'part_id',
        'part_description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'sub_category_id');
    }

    public function workOrderParts(): HasMany
    {
        return $this->hasMany(WorkOrderPart::class);
    }
}
