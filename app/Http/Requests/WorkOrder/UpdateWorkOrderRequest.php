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
            'attachments' => 'nullable',
            'feedback_preference' => 'nullable|boolean',
            'status' => 'required|in:Progress,Closed,Delivered',
            'replace_serial' => 'nullable|string|max:255',
            'replace_product_name' => 'nullable|string|max:255',
            'replace_product_info' => 'nullable|string',
            'replace_ref' => 'nullable|string|max:255',
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
