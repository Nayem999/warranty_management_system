<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'permissions' => $this->permissions,
            'created_at' => $this->created_at?->format("d-M-Y h:i A"),
            'updated_at' => $this->updated_at?->format("d-M-Y h:i A"),
        ];
    }
}
