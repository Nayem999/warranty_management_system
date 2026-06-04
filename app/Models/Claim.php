<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Claim extends BaseModel
{
    protected $table = 'wms_claims';

    protected $fillable = [
        'claim_number',
        'product_id',
        'serial_number',
        'customer_id',
        'view_count',
        'problem_description',
        'service_center_id',
        'claim_date',
        'status',
        'created_by',
        'engineer_id',
        'courier_in_id',
        'courier_slip_inward',
        'received_date_time',
        'counter',
        'wo_assigned_date',
        'wo_closed_date',
        'wo_delivery_date',
        'tat',
        'doa',
        'invoice_no',
        'invoice_date',
        'purchase_price',
        'ref',
        'web_wty_date',
        'additional_comment',
        'work_done_comment',
        'customer_feedback',
        'customer_rating',
        'feedback_token',
        'status_comment',
        'service_type',
        'job_type',
        'assigned_by',
        'attachments',
        'is_feedback_taken',
        'job_remarks',
        'accessories',
        'is_delivered',
        'delivery_id',
    ];

    // protected $casts = [
    //     'claim_date' => 'datetime',
    //     'received_date_time' => 'datetime',
    //     'invoice_date' => 'date',
    //     'web_wty_date' => 'date',
    //     'wo_assigned_date' => 'datetime',
    //     'wo_closed_date' => 'datetime',
    //     'wo_delivery_date' => 'datetime',
    //     'purchase_price' => 'decimal:2',
    //     'doa' => 'boolean',
    //     'status' => 'string',
    //     'attachments' => 'array',
    //     'is_feedback_taken' => 'boolean',
    //     'is_delivered' => 'boolean',
    // ];


    protected $casts = [
        'claim_date' => 'datetime:Y-m-d H:i:s',
        'received_date_time' => 'datetime:Y-m-d H:i:s',
        'invoice_date' => 'date',
        'web_wty_date' => 'date',
        'wo_assigned_date' => 'datetime:Y-m-d H:i:s',
        'wo_closed_date' => 'datetime:Y-m-d H:i:s',
        'wo_delivery_date' => 'datetime:Y-m-d H:i:s',
        'purchase_price' => 'decimal:2',
        'doa' => 'boolean',
        'attachments' => 'array',
        'is_feedback_taken' => 'boolean',
        'is_delivered' => 'boolean',
        'view_count' => 'integer',
    ];

    // public function getClaimDateAttribute($value)
    // {
    //     return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    // }
    // public function getWoAssignedDateAttribute($value)
    // {
    //     return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    // }

    // public function getWoClosedDateAttribute($value)
    // {
    //     return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    // }

    // public function getWoDeliveryDateAttribute($value)
    // {
    //     return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    // }

    // public function getReceivedDateTimeAttribute($value)
    // {
    //     return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    // }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function courierIn(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_in_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'Open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'Closed');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'Converted');
    }

    public function scopeNotAssigned($query)
    {
        return $query->where('status', 'Not Assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'In Progress');
    }


    public static function generateClaimNumber(): string
    {
        $year = now()->year;

        return \DB::transaction(function () use ($year) {
            $lastClaim = static::withTrashed()
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->latest()
                ->first();

            $seq = $lastClaim ? (intval(substr($lastClaim->claim_number, -5)) + 1) : 1;

            return 'CLM-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
        });
    }
}
