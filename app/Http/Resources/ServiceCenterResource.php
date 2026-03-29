<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCenterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'address' => $this->address,
            'uan' => $this->uan,
            'email' => $this->email,
            'working_hours' => $this->working_hours,
            'logo' => $this->logo ? asset('storage/' . $this->logo) : null,
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
