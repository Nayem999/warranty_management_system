<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // $categoryId = $this->route('id');
        $categoryId = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wms_product_categories', 'name')->ignore($categoryId),
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('wms_product_categories', 'short_name')->ignore($categoryId),
            ],
            'parent_id' => 'nullable|exists:wms_product_categories,id',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
