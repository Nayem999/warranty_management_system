<?php

namespace App\Models;

class MemorizeReport extends BaseModel
{
    protected $table = 'wms_memorize_reports';

    protected $fillable = [
        'title',
        'type',
        'filter',
    ];

    protected $casts = [
        'filter' => 'array',
    ];
}
