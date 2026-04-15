<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'replace_ref',
        'replaced_warranty_id',
        'additional_comment',
        'work_done_comment',
        'customer_feedback',
        'customer_rating',
        'feedback_token',
        'status',
        'status_comment',
        'service_type',
        'job_type',
        'invoice_no',
        'invoice_date',
        'purchase_price',
        'ref',
        'web_wty_date',
        'created_by',
        'assigned_by',
    ];

    protected $casts = [
        'wo_assigned_date' => 'date',
        'wo_closed_date' => 'date',
        'wo_delivery_date' => 'date',
        'invoice_date' => 'date',
        'web_wty_date' => 'date',
        'received_date_time' => 'datetime',
        'delivered_date_time' => 'datetime',
        'doa' => 'boolean',
        'feedback_preference' => 'boolean',
        'customer_rating' => 'integer',
        'counter' => 'integer',
        'purchase_price' => 'decimal:2',
        'status' => 'string',
        'attachments' => 'array',
    ];

    protected $appends = [
        'attachments_urls',
    ];

    public function getAttachmentsUrlsAttribute(): ?array
    {
        $attachments = $this->attachments;

        if (empty($attachments)) {
            return null;
        }

        if (is_string($attachments)) {
            $decoded = json_decode($attachments, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $attachments = $decoded;
            } else {
                $attachments = [$attachments];
            }
        }

        if (! is_array($attachments)) {
            return null;
        }

        $backendUrl = rtrim(config('app.backend_url', env('BACKEND_URL', '')), '/');

        $urls = [];
        foreach ($attachments as $attachment) {
            if (filter_var($attachment, FILTER_VALIDATE_URL)) {
                $urls[] = $attachment;
            } else {
                $urls[] = $backendUrl.'/storage/'.$attachment;
            }
        }

        return $urls;
    }

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Progress');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'Progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Closed');
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

        return \DB::transaction(function () use ($year) {
            $lastWo = static::withTrashed() // 👈 include soft deleted
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->latest()
                ->first();

            $seq = $lastWo ? (intval(substr($lastWo->wo_number, -5)) + 1) : 1;

            return 'WO-'.$year.'-'.str_pad($seq, 5, '0', STR_PAD_LEFT);
        });
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
