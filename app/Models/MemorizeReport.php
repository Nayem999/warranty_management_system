<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizeReport extends BaseModel
{
    protected $table = 'wms_memorize_reports';

    protected $fillable = [
        'title',
        'type',
        'filter',
        'created_by',
    ];

    protected $casts = [
        'filter' => 'array',
        'created_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
