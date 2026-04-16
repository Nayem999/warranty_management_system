<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    protected $brandId;

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->brandId = $this->route('brand');

        if (! $this->brandId && is_numeric($this->route('id'))) {
            $this->brandId = $this->route('id');
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wms_brands', 'name')->ignore($this->brandId),
            ],
            'short_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('wms_brands', 'short_name')->ignore($this->brandId),
            ],
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
