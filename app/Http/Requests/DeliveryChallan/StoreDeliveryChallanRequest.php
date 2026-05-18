<?php

namespace App\Http\Requests\DeliveryChallan;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryChallanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'claim_ids' => 'required|array|min:1',
            'claim_ids.*' => 'required|exists:wms_claims,id',
            'customer_id' => 'nullable|exists:wms_customers,id',
            'courier_out_id' => 'nullable|exists:wms_couriers,id',
            'courier_slip_outward' => 'nullable|string|max:255',
            'delivered_date_time' => 'nullable|date',
            'delivered_remarks' => 'nullable|string|max:1000',
        ];
    }
}