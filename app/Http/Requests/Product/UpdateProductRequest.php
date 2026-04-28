<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    protected $productId;

    public function prepareForValidation(): void
    {
        $this->productId = $this->route('Product');

        if (! $this->productId && is_numeric($this->route('id'))) {
            $this->productId = $this->route('id');
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'model_no' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('wms_products', 'model_no')->ignore($this->productId),
            ],
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