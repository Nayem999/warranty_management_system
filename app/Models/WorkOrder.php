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
        'product_id',
        'service_center_id',
        'replace_serial',
        'replace_product_id',
        'replace_ref',
        'created_by',
    ];

    protected $casts = [
        'replace_product_id' => 'integer',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function warranty(): BelongsTo
    {
        return $this->claim()->product();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function replaceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'replace_product_id');
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

            return 'WO-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
        });
    }

    public static function generateFeedbackToken(): string
    {
        return Str::uuid()->toString();
    }
}
