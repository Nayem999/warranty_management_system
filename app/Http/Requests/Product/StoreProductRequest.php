<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'model_no' => 'required|string|unique:wms_products,model_no|max:255',
            'serial_number' => 'nullable|string|max:255',
            'item_description' => 'nullable|string',
            'brand_id' => 'nullable|exists:wms_brands,id',
            'category_id' => 'nullable|exists:wms_product_categories,id',
            'sub_category_id' => 'nullable|exists:wms_product_categories,id',
            'is_countable' => 'sometimes|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}