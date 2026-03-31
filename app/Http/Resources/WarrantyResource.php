<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarrantyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_serial' => $this->product_serial,
            'product_name' => $this->product_name,
            'product_info' => $this->product_info,
            'brand_id' => $this->brand_id,
            'brand' => $this->whenLoaded('brand'),
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category'),
            'sub_category_id' => $this->sub_category_id,
            'sub_category' => $this->whenLoaded('subCategory'),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_void' => $this->is_void,
            'void_reason' => $this->void_reason,
            'warranty_status' => $this->warranty_status,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
