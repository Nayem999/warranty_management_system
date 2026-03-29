<?php

namespace App\Http\Requests\Claim;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_serial' => 'required|exists:wms_warranties,id',
            'problem_description' => 'required|string',
            'customer_firstname' => 'required|string|max:255',
            'customer_lastname' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'required|string|max:20',
            'customer_city' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string',
            'service_center_id' => 'nullable|exists:wms_service_centers,id',
            'claim_date' => 'nullable|date',
        ];
    }
}
