<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkOrder extends BaseModel
{
    protected $table = 'wms_work_orders';

    protected $fillable = [
        'wo_number',
        'claim_id',
        'service_center_id',
        'engineer_id',
        'courier_in_id',
        'courier_slip_inward',
        'courier_out_id',
        'courier_slip_outward',
        'attachments',
        'feedback_preference',
        'received_date_time',
        'delivered_date_time',
        'counter',
        'wo_assigned_date',
        'wo_closed_date',
        'wo_delivery_date',
        'tat',
        'doa',
        'replace_serial',
        'replaced_warranty_id',
        'additional_comment',
        'work_done_comment',
        'customer_feedback',
        'customer_rating',
        'feedback_token',
        'status',
        'part1_used',
        'part2_used',
        'part3_used',
        'created_by',
        'assigned_by',
    ];

    protected $casts = [
        'wo_assigned_date' => 'date',
        'wo_closed_date' => 'date',
        'wo_delivery_date' => 'date',
        'received_date_time' => 'datetime',
        'delivered_date_time' => 'datetime',
        'doa' => 'boolean',
        'feedback_preference' => 'boolean',
        'customer_rating' => 'integer',
        'counter' => 'integer',
        'status' => 'string',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function warranty(): BelongsTo
    {
        return $this->claim()->warranty();
    }

    public function replacedWarranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class, 'replaced_warranty_id');
    }

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    public function courierIn(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_in_id');
    }

    public function courierOut(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_out_id');
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function warranty(): BelongsTo
    {
        return $this->claim()->warranty();
    }

    public function replacedWarranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class, 'replaced_warranty_id');
    }

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'In Progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'Delivered');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'Delivered')
            ->where('wo_assigned_date', '<', Carbon::today()->subDays(7));
    }

    public static function generateWoNumber(): string
    {
        $year = now()->year;
        $lastWo = static::whereYear('created_at', $year)->latest()->first();
        $seq = $lastWo ? (intval(substr($lastWo->wo_number, -5)) + 1) : 1;

        return 'WO-'.$year.'-'.str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    public static function generateFeedbackToken(): string
    {
        return Str::uuid()->toString();
    }

    public function calculateTat(): ?int
    {
        if ($this->wo_assigned_date && $this->wo_closed_date) {
            return $this->wo_assigned_date->diffInDays($this->wo_closed_date);
        }

        return null;
    }
}
