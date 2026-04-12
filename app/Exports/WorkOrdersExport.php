<?php

namespace App\Exports;

use App\Models\WorkOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WorkOrdersExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = WorkOrder::with(['claim.warranty.brand', 'serviceCenter', 'engineer', 'creator', 'assignedBy']);

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['service_center_id'])) {
            $query->where('service_center_id', $this->filters['service_center_id']);
        }

        if (! empty($this->filters['engineer_id'])) {
            $query->where('engineer_id', $this->filters['engineer_id']);
        }

        if (! empty($this->filters['brand_id'])) {
            $query->whereHas('claim.warranty', function ($q) {
                $q->where('brand_id', $this->filters['brand_id']);
            });
        }

        if (! empty($this->filters['date_from'])) {
            $query->where('wo_assigned_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->where('wo_assigned_date', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('wo_number', 'like', '%'.$this->filters['search'].'%')
                    ->orWhereHas('claim', function ($q2) {
                        $q2->where('claim_number', 'like', '%'.$this->filters['search'].'%');
                    });
            });
        }

        $workOrders = $query->orderBy('id', 'desc')->get();

        return $workOrders->map(function ($workOrder) {
            return [
                'WO Number' => $workOrder->wo_number,
                'Status' => $workOrder->status,
                'Claim Number' => $workOrder->claim?->claim_number,
                'Product Serial' => $workOrder->claim?->warranty?->product_serial,
                'Product Name' => $workOrder->claim?->warranty?->product_name,
                'Brand' => $workOrder->claim?->warranty?->brand?->name,
                'Service Center' => $workOrder->serviceCenter?->title,
                'Engineer' => $workOrder->engineer?->first_name.' '.$workOrder->engineer?->last_name,
                'Assigned Date' => $workOrder->wo_assigned_date,
                'Closed Date' => $workOrder->wo_closed_date,
                'Delivery Date' => $workOrder->wo_delivery_date,
                'TAT (Days)' => $workOrder->tat,
                'Service Type' => $workOrder->service_type,
                'Job Type' => $workOrder->job_type,
                'Created By' => $workOrder->creator?->first_name.' '.$workOrder->creator?->last_name,
                'Assigned By' => $workOrder->assignedBy?->first_name.' '.$workOrder->assignedBy?->last_name,
                'Created At' => $workOrder->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'WO Number',
            'Status',
            'Claim Number',
            'Product Serial',
            'Product Name',
            'Brand',
            'Service Center',
            'Engineer',
            'Assigned Date',
            'Closed Date',
            'Delivery Date',
            'TAT (Days)',
            'Service Type',
            'Job Type',
            'Created By',
            'Assigned By',
            'Created At',
        ];
    }
}
