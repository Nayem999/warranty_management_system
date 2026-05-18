<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class DeliveryChallan extends Model
{
    protected $table = 'wms_delivery_challans';

    protected $fillable = [
        'delivery_number',
        'customer_id',
        'courier_out_id',
        'courier_slip_outward',
        'delivered_date_time',
        'delivered_remarks',
        'claim_ids',
    ];

    protected $casts = [
        'claim_ids' => 'array',
        'delivered_date_time' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    public function courierOut(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_out_id');
    }

    public function getClaimsAttribute(): Collection
    {
        if (empty($this->claim_ids)) {
            return collect();
        }

        return Claim::with('product.brand', 'product.category', 'product.subCategory', 'customer.city', 'serviceCenter', 'engineer', 'courierIn', 'courierOut', 'assignedByUser', 'creator', 'workOrder.parts.part')->whereIn('id', $this->claim_ids)->get();
    }

    public static function generateDeliveryNumber(): string
    {
        $year = now()->year;

        return \DB::transaction(function () use ($year) {
            $lastChallan = static::withTrashed()
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->latest()
                ->first();

            $seq = $lastChallan ? (intval(substr($lastChallan->delivery_number, -5)) + 1) : 1;

            return 'DC-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
        });
    }
}
