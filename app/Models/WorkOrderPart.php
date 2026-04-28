<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPart extends BaseModel
{
    protected $table = 'wms_work_order_parts';

    protected $fillable = [
        'work_order_id',
        'claim_id',
        'claim_date_time',
        'part_id',
        'case_id',
        'case_date_time',
        'order_id',
        'order_date_time',
        'received_date_time',
        'install_date_time',
        'good_part_serial',
        'faulty_part_serial',
        'return_date_time',
        'part_returned',
        'part_status',
        'part_return_comment',
    ];

    protected $casts = [
        'claim_id' => 'string',
        'claim_date_time' => 'datetime',
        'case_date_time' => 'datetime',
        'order_date_time' => 'datetime',
        'received_date_time' => 'datetime',
        'install_date_time' => 'datetime',
        'return_date_time' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
