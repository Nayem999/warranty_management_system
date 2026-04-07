<?php

namespace App\Http\Requests\Warranty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarrantyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warrantyId = $this->route('id');

        return [
            'product_serial' => [
                'sometimes',
                'string',
                Rule::unique('wms_warranties', 'product_serial')->ignore($warrantyId),
            ],
            'product_name' => 'sometimes|string|max:255',
            'product_info' => 'nullable|string',
            'brand_id' => 'sometimes|exists:wms_brands,id',
            'category_id' => 'sometimes|exists:wms_product_categories,id',
            'sub_category_id' => 'nullable|exists:wms_product_categories,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_void' => 'sometimes|in:YES,NO',
            'void_reason' => 'nullable|string',
        ];
    }
}
