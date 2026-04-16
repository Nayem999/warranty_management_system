<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:wms_product_categories,name|max:255',
            'short_name' => 'required|string|unique:wms_product_categories,short_name|max:50',
            'parent_id' => 'nullable|exists:wms_product_categories,id',
            'brand_id' => 'required|exists:wms_brands,id',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
