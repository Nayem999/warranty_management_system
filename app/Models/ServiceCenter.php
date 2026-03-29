<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCenter extends BaseModel
{
    protected $table = 'wms_service_centers';

    protected $fillable = [
        'title',
        'address',
        'uan',
        'email',
        'working_hours',
        'logo',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
