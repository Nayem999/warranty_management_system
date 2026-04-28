<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    protected $table = 'wms_customers';

    protected $fillable = [
        'customer_name',
        'contact_person',
        'email',
        'phone',
        'landline',
        'address',
        'city',
    ];

    protected $casts = [
        'customer_name' => 'string',
        'contact_person' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'landline' => 'string',
        'address' => 'string',
        'city' => 'string',
    ];

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }
}