<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'claim_number' => $this->claim_number,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product'),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer'),
            'problem_description' => $this->problem_description,
            'service_center_id' => $this->service_center_id,
            'service_center' => $this->whenLoaded('serviceCenter'),
            'claim_date' => $this->claim_date?->format('Y-m-d'),
            'status' => $this->status,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'work_order' => $this->whenLoaded('workOrder'),
            'engineer_id' => $this->engineer_id,
            'engineer' => $this->whenLoaded('engineer'),
            'courier_in_id' => $this->courier_in_id,
            'courier_in' => $this->whenLoaded('courierIn'),
            'courier_slip_inward' => $this->courier_slip_inward,
            'courier_out_id' => $this->courier_out_id,
            'courier_out' => $this->whenLoaded('courierOut'),
            'courier_slip_outward' => $this->courier_slip_outward,
            'received_date_time' => $this->received_date_time?->toIso8601String(),
            'delivered_date_time' => $this->delivered_date_time?->toIso8601String(),
            'counter' => $this->counter,
            'wo_assigned_date' => $this->wo_assigned_date?->format('Y-m-d'),
            'wo_closed_date' => $this->wo_closed_date?->format('Y-m-d'),
            'wo_delivery_date' => $this->wo_delivery_date?->format('Y-m-d'),
            'tat' => $this->tat,
            'doa' => $this->doa,
            'invoice_no' => $this->invoice_no,
            'invoice_date' => $this->invoice_date?->format('Y-m-d'),
            'purchase_price' => $this->purchase_price,
            'ref' => $this->ref,
            'web_wty_date' => $this->web_wty_date?->format('Y-m-d'),
            'additional_comment' => $this->additional_comment,
            'work_done_comment' => $this->work_done_comment,
            'customer_feedback' => $this->customer_feedback,
            'customer_rating' => $this->customer_rating,
            'feedback_token' => $this->feedback_token,
            'status_comment' => $this->status_comment,
            'service_type' => $this->service_type,
            'job_type' => $this->job_type,
            'assigned_by' => $this->assigned_by,
            'assigned_by_user' => $this->whenLoaded('assignedByUser'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}