<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_center_id' => 'nullable|exists:wms_service_centers,id',
            'wo_assigned_date' => 'nullable|date',
            'wo_closed_date' => 'nullable|date',
            'wo_delivery_date' => 'nullable|date',
            'doa' => 'nullable|boolean',
            'replace_serial' => 'nullable|string',
            'additional_comment' => 'nullable|string',
            'work_done_comment' => 'nullable|string',
            'part1_used' => 'nullable|string',
            'part2_used' => 'nullable|string',
            'part3_used' => 'nullable|string',
        ];
    }
}
