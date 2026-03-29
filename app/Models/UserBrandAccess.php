<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBrandAccess extends BaseModel
{
    protected $table = 'wms_user_brand_access';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'brand_id',
        'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'brand_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
