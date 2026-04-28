<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends BaseModel
{
    protected $table = 'wms_products';

    protected $fillable = [
        'model_no',
        'serial_number',
        'item_description',
        'brand_id',
        'category_id',
        'sub_category_id',
        'is_countable',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_countable' => 'boolean',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'product_id');
    }

    public function workOrders(): HasManyThrough
    {
        return $this->hasManyThrough(WorkOrder::class, Claim::class);
    }

    public function getProductStatusAttribute(): string
    {
        if (! $this->end_date) {
            return 'No Warranty';
        }

        $today = Carbon::today();

        if ($this->end_date->lt($today)) {
            return 'Expired';
        }

        return 'Active';
    }

    public function isActive(): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return $this->end_date->gte(Carbon::today());
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('end_date')->where('end_date', '>=', Carbon::today());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('end_date')->where('end_date', '<', Carbon::today());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays($days)]);
    }
}
