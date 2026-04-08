<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'to_email',
        'subject',
        'status',
        'reason',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
