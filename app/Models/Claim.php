<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Claim extends BaseModel
{
    protected $table = 'wms_claims';

    protected $fillable = [
        'claim_number',
        'warranty_id',
        'problem_description',
        'customer_firstname',
        'customer_lastname',
        'customer_email',
        'customer_phone',
        'customer_city',
        'customer_address',
        'service_center_id',
        'claim_date',
        'status',
        'created_by',
        'customer_user_id',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'status' => 'string',
    ];

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class, 'warranty_id');
    }

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
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

    public static function generateClaimNumber(): string
    {
        $year = now()->year;

        return \DB::transaction(function () use ($year) {
            $lastClaim = static::withTrashed() // 👈 include soft deleted
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->latest()
                ->first();

            $seq = $lastClaim ? (intval(substr($lastClaim->claim_number, -5)) + 1) : 1;

            return 'CLM-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
        });
    }
}
