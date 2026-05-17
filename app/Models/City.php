<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends BaseModel
{
    protected $table = 'wms_cities';

    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}