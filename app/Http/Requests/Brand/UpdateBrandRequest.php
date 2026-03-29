<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
