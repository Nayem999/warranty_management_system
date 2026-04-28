<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'model_no' => $this->model_no,
            'serial_number' => $this->serial_number,
            'item_description' => $this->item_description,
            'brand_id' => $this->brand_id,
            'brand' => $this->whenLoaded('brand'),
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category'),
            'sub_category_id' => $this->sub_category_id,
            'sub_category' => $this->whenLoaded('subCategory'),
            'is_countable' => $this->is_countable,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'product_status' => $this->product_status,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}