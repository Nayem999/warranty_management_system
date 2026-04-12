<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:wms_brands,name|max:255',
            'short_name' => 'nullable|string|max:50',
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
