<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'landline' => $this->landline,
            'address' => $this->address,
            'city' => $this->city,
            'created_at' => $this->created_at?->format("d-M-Y h:i A"),
            'updated_at' => $this->updated_at?->format("d-M-Y h:i A"),
        ];
    }
}
