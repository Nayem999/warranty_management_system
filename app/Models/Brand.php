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

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    public function userBrandAccess(): HasMany
    {
        return $this->hasMany(UserBrandAccess::class);
    }
}
