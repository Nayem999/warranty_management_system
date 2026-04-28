<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    protected $customerId;

    public function prepareForValidation(): void
    {
        $this->customerId = $this->route('Customer');

        if (! $this->customerId && is_numeric($this->route('id'))) {
            $this->customerId = $this->route('id');
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'sometimes|required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('wms_customers', 'email')->ignore($this->customerId),
            ],
            'phone' => 'nullable|string|max:20',
            'landline' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
        ];
    }
}