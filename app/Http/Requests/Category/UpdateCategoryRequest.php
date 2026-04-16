<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    protected $categoryId;

    public function prepareForValidation(): void
    {
        $this->categoryId = $this->route('ProductCategory');

        if (! $this->categoryId && is_numeric($this->route('id'))) {
            $this->categoryId = $this->route('id');
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('wms_product_categories', 'name')->ignore($this->categoryId),
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('wms_product_categories', 'short_name')->ignore($this->categoryId),
            ],
            'parent_id' => 'nullable|exists:wms_product_categories,id',
            'brand_id' => 'sometimes|required|exists:wms_brands,id',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
