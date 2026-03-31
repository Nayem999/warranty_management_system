<?php

namespace App\Http\Requests\Warranty;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarrantyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_serial' => 'required|string|max:255|unique:wms_warranties,product_serial',
            'product_name' => 'required|string|max:255',
            'product_info' => 'nullable|string',
            'brand_id' => 'required|exists:wms_brands,id',
            'category_id' => 'required|exists:wms_product_categories,id',
            'sub_category_id' => 'nullable|exists:wms_product_categories,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_void' => 'sometimes|in:YES,NO',
            'void_reason' => 'nullable|string',
        ];
    }
}
