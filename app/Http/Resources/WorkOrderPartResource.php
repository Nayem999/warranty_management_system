<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderPartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_order_id' => $this->work_order_id,
            'claim_id' => $this->claim_id,
            'claim_date_time' => $this->claim_date_time?->toIso8601String(),
            'part_id' => $this->part_id,
            'part' => $this->whenLoaded('part'),
            'case_id' => $this->case_id,
            'case_date_time' => $this->case_date_time?->toIso8601String(),
            'order_id' => $this->order_id,
            'order_date_time' => $this->order_date_time?->toIso8601String(),
            'received_date_time' => $this->received_date_time?->toIso8601String(),
            'install_date_time' => $this->install_date_time?->toIso8601String(),
            'good_part_serial' => $this->good_part_serial,
            'faulty_part_serial' => $this->faulty_part_serial,
            'return_date_time' => $this->return_date_time?->toIso8601String(),
            'part_returned' => $this->part_returned,
            'part_status' => $this->part_status,
            'part_return_comment' => $this->part_return_comment,
            'labour_claim_id' => $this->labour_claim_id,
            'labour_claim_date' => $this->labour_claim_date?->format('Y-m-d'),
            'faulty_part_id' => $this->faulty_part_id,
            'faulty_part' => $this->whenLoaded('faultyPart'),
            'faulty_description' => $this->faulty_description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}