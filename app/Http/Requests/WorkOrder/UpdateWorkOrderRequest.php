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
            'engineer_id' => 'nullable|exists:users,id',
            'courier_in_id' => 'nullable|exists:wms_couriers,id',
            'courier_slip_inward' => 'nullable|string',
            'courier_out_id' => 'nullable|exists:wms_couriers,id',
            'courier_slip_outward' => 'nullable|string',
            'attachments' => 'nullable|string',
            'feedback_preference' => 'nullable|boolean',
            'received_date_time' => 'nullable|date',
            'delivered_date_time' => 'nullable|date',
            'counter' => 'nullable|integer|min:1',
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
