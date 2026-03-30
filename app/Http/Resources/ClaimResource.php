<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'claim_number' => $this->claim_number,
            'warranty_id' => $this->warranty_id,
            'warranty' => $this->whenLoaded('warranty'),
            'problem_description' => $this->problem_description,
            'customer_firstname' => $this->customer_firstname,
            'customer_lastname' => $this->customer_lastname,
            'customer_name' => $this->customer_firstname.' '.$this->customer_lastname,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_city' => $this->customer_city,
            'customer_address' => $this->customer_address,
            'service_center_id' => $this->service_center_id,
            'service_center' => $this->whenLoaded('serviceCenter'),
            'claim_date' => $this->claim_date?->format('Y-m-d'),
            'status' => $this->status,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'work_order' => $this->whenLoaded('workOrder'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
