<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'logo' => $this->logo ? $this->logo : null,
            'description' => $this->description,
            'status' => $this->status,
            'categories' => $this->whenLoaded('categories'),
            'created_at' => $this->created_at?->format("d-M-Y h:i A"),
            'updated_at' => $this->updated_at?->format("d-M-Y h:i A"),
        ];
    }
}
