<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => 'sometimes|exists:wms_brands,id',
            'name' => 'sometimes|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
