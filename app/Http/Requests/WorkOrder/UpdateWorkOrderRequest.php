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
            'attachments' => 'nullable',
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
            'parts' => 'nullable|array',
            'parts.*.part_id' => 'nullable|exists:wms_parts,id',
            'parts.*.case_id' => 'nullable|string',
            'parts.*.case_date_time' => 'nullable|date',
            'parts.*.order_id' => 'nullable|string',
            'parts.*.order_date_time' => 'nullable|date',
            'parts.*.received_date_time' => 'nullable|date',
            'parts.*.install_date_time' => 'nullable|date',
            'parts.*.good_part_serial' => 'nullable|string',
            'parts.*.faulty_part_serial' => 'nullable|string',
            'parts.*.return_date_time' => 'nullable|date',
            'parts.*.part_returned' => 'nullable|in:Yes,No',
            'parts.*.part_status' => 'nullable|in:DOA Part,Wrong Parts delivered,Cancelled/Roll Over,Used in repair,Un-used,Damaged',
            'parts.*.part_return_comment' => 'nullable|string',
        ];
    }
}
