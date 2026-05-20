<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryChallanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delivery_number' => $this->delivery_number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'courier_out' => new CourierResource($this->whenLoaded('courierOut')),
            'courier_slip_outward' => $this->courier_slip_outward,
            'delivered_date_time' => $this->delivered_date_time?->format("d-M-Y h:i A"),
            'delivered_remarks' => $this->delivered_remarks,
            'claim_ids' => $this->claim_ids,
            'claims' => ClaimResource::collection($this->claims),
            'created_at' => $this->created_at?->format("d-M-Y h:i A"),
            'updated_at' => $this->updated_at?->format("d-M-Y h:i A"),
        ];
    }
}
