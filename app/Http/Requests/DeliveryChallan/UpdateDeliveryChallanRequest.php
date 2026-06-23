<?php

namespace App\Http\Requests\DeliveryChallan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryChallanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'courier_slip_outward' => 'nullable|string|max:255',
            'delivered_date_time' => 'nullable|date',
            'delivered_remarks' => 'nullable|string|max:1000',
        ];
    }
}
