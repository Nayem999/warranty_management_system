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
            'name' => 'sometimes|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:wms_product_categories,id',
            'brand_id' => 'sometimes|required|exists:wms_brands,id',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
