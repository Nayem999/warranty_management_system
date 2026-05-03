<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class Customer extends Authenticatable
{
    use HasApiTokens;
    protected $table = 'wms_customers';

    protected $fillable = [
        'customer_name',
        'contact_person',
        'email',
        'phone',
        'landline',
        'address',
        'city',
        'password',
    ];

    protected $hidden = [
        'password',
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

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = $value ? bcrypt($value) : null;
    }

    public function checkPassword(string $password): bool
    {
        return $this->password && Hash::check($password, $this->password);
    }
}