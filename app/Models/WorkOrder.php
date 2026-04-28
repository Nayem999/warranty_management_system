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
        'attachments',
        'feedback_preference',
        'product_id',
        'replaced_warranty_id',
        'replace_serial',
        'replace_product_name',
        'replace_product_info',
        'replace_ref',
        'created_by',
    ];

    protected $casts = [
        'feedback_preference' => 'boolean',
        'attachments' => 'array',
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

        $urls = [];
        foreach ($attachments as $attachment) {
            if (filter_var($attachment, FILTER_VALIDATE_URL)) {
                $urls[] = $attachment;
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
            $lastWo = static::withTrashed()
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
}
