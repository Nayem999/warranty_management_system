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
        $statuses = implode(',', [
            'Not Assigned',
            'Open',
            'In Progress',
            'Closed(Repaired)',
            'Closed-(Without Repaired)',
            'Closed-(Replaced)',
            'Closed-(Reimbursed)',
            'Delivered',
        ]);

        $serviceTypes = implode(',', [
            'In Warranty',
            'Warranty Void',
            'DOA',
            'OOW/Expired',
        ]);

        $jobTypes = implode(',', [
            'Carry In',
            'On Site',
            'Pick Up',
        ]);

        return [
            'product_id' => 'required|exists:wms_products,id',
            'customer_id' => 'required|exists:wms_customers,id',
            'problem_description' => 'required|string',
            'service_center_id' => 'required|exists:wms_service_centers,id',
            'claim_date' => 'nullable|date',
            'status' => "nullable|in:{$statuses}",
            'engineer_id' => 'nullable|exists:users,id',
            'courier_in_id' => 'nullable|exists:wms_couriers,id',
            'courier_slip_inward' => 'nullable|string|max:255',
            'courier_out_id' => 'nullable|exists:wms_couriers,id',
            'courier_slip_outward' => 'nullable|string|max:255',
            'received_date_time' => 'nullable|date',
            'delivered_date_time' => 'nullable|date',
            'counter' => 'nullable|integer|min:0',
            'wo_assigned_date' => 'nullable|date',
            'wo_closed_date' => 'nullable|date',
            'wo_delivery_date' => 'nullable|date',
            'tat' => 'nullable|integer|min:0',
            'doa' => 'nullable|boolean',
            'replace_serial' => 'nullable|string|max:255',
            'replace_product_name' => 'nullable|string|max:255',
            'replace_product_info' => 'nullable|string',
            'replace_ref' => 'nullable|string|max:255',
            'invoice_no' => 'nullable|string|max:255',
            'invoice_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'ref' => 'nullable|string|max:255',
            'web_wty_date' => 'nullable|date',
            'additional_comment' => 'nullable|string',
            'work_done_comment' => 'nullable|string',
            'status_comment' => 'nullable|string',
            'service_type' => "nullable|in:{$serviceTypes}",
            'job_type' => "nullable|in:{$jobTypes}",
            'assigned_by' => 'nullable|exists:users,id',
        ];
    }
}
