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
            'brand_id' => 'required|exists:wms_brands,id',
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
