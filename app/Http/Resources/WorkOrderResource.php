<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wo_number' => $this->wo_number,
            'claim_id' => $this->claim_id,
            'claim' => $this->whenLoaded('claim'),
            'warranty' => $this->whenLoaded('warranty'),
            'service_center_id' => $this->service_center_id,
            'service_center' => $this->whenLoaded('serviceCenter'),
            'wo_assigned_date' => $this->wo_assigned_date?->format('d-M-Y'),
            'wo_closed_date' => $this->wo_closed_date?->format('d-M-Y'),
            'wo_delivery_date' => $this->wo_delivery_date?->format('d-M-Y'),
            'tat' => $this->tat,
            'doa' => $this->doa,
            'replace_serial' => $this->replace_serial,
            'replace_product_id' => $this->replace_product_id,
            'replace_product' => $this->whenLoaded('replaceProduct'),
            'replace_ref' => $this->replace_ref,
            'additional_comment' => $this->additional_comment,
            'work_done_comment' => $this->work_done_comment,
            'customer_feedback' => $this->customer_feedback,
            'customer_rating' => $this->customer_rating,
            'feedback_token' => $this->feedback_token,
            'status' => $this->status,
            'part1_used' => $this->part1_used,
            'part2_used' => $this->part2_used,
            'part3_used' => $this->part3_used,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'assigned_by' => $this->assigned_by,
            'assignedBy' => $this->whenLoaded('assignedBy'),
            'created_at' => $this->created_at?->format("d-M-Y h:i A"),
            'updated_at' => $this->updated_at?->format("d-M-Y h:i A"),
        ];
    }
}
