<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'user_type' => $this->user_type,
            'is_admin' => $this->is_admin,
            'role_id' => $this->role_id,
            'role' => $this->whenLoaded('role'),
            'status' => $this->status,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'phone' => $this->phone,
            'job_title' => $this->job_title,
            'disable_login' => $this->disable_login,
            'address' => $this->address,
            'dob' => $this->dob?->format('Y-m-d'),
            'gender' => $this->gender,
            'language' => $this->language,
            'last_online' => $this->last_online?->toIso8601String(),
            'enable_web_notification' => $this->enable_web_notification,
            'enable_email_notification' => $this->enable_email_notification,
            'brands' => $this->whenLoaded('brands'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
