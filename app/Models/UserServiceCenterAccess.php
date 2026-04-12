<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserServiceCenterAccess extends BaseModel
{
    protected $table = 'wms_user_service_center_access';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'service_center_id',
        'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'service_center_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
