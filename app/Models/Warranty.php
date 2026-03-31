<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Warranty extends BaseModel
{
    protected $table = 'wms_warranties';

    protected $fillable = [
        'product_serial',
        'product_name',
        'product_info',
        'brand_id',
        'category_id',
        'sub_category_id',
        'start_date',
        'end_date',
        'is_void',
        'void_reason',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_void' => 'string',
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
        return $this->hasMany(Claim::class, 'warranty_id');
    }

    public function workOrders(): HasManyThrough
    {
        return $this->hasManyThrough(WorkOrder::class, Claim::class);
    }

    public function getWarrantyStatusAttribute(): string
    {
        if ($this->is_void === 'YES') {
            return 'Void';
        }

        $today = Carbon::today();

        if ($this->end_date->lt($today)) {
            return 'Expired';
        }

        return 'Active';
    }

    public function isActive(): bool
    {
        return $this->is_void === 'NO' && $this->end_date->gte(Carbon::today());
    }

    public function scopeActive($query)
    {
        return $query->where('is_void', 'NO')
            ->where('end_date', '>=', Carbon::today());
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', Carbon::today())
            ->where('is_void', 'NO');
    }

    public function scopeVoid($query)
    {
        return $query->where('is_void', 'YES');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('is_void', 'NO')
            ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays($days)]);
    }
}
