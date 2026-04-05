<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends BaseModel
{
    protected $table = 'wms_couriers';

    protected $fillable = [
        'name',
        'phone',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
