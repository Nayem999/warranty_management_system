<?php

namespace App\Exports;

use App\Models\WorkOrder;
use Carbon\Carbon;
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
        $query = WorkOrder::query()->with([
            'claim.warranty.brand',
            'claim.warranty.category',
            'claim.warranty.subCategory',
            'serviceCenter',
            'courierIn',
            'courierOut',
            'engineer',
            'creator',
            'assignedBy',
            'parts.part',
        ]);

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['service_center_id'])) {
            $query->where('service_center_id', $this->filters['service_center_id']);
        }

        if (! empty($this->filters['courier_in_id'])) {
            $query->where('courier_in_id', $this->filters['courier_in_id']);
        }

        if (! empty($this->filters['courier_out_id'])) {
            $query->where('courier_out_id', $this->filters['courier_out_id']);
        }

        if (! empty($this->filters['engineer_id'])) {
            $query->where('engineer_id', $this->filters['engineer_id']);
        }

        if (! empty($this->filters['brand_id'])) {
            $query->whereHas('claim.warranty', function ($q) {
                $q->where('brand_id', $this->filters['brand_id']);
            });
        }

        if (! empty($this->filters['category_id'])) {
            $query->whereHas('claim.warranty', function ($q) {
                $q->where('category_id', $this->filters['category_id']);
            });
        }

        if (! empty($this->filters['sub_category_id'])) {
            $query->whereHas('claim.warranty', function ($q) {
                $q->where('sub_category_id', $this->filters['sub_category_id']);
            });
        }

        if (! empty($this->filters['claim_id'])) {
            $query->where('claim_id', $this->filters['claim_id']);
        }

        if (! empty($this->filters['claim_status'])) {
            $query->whereHas('claim', function ($q) {
                $q->where('status', $this->filters['claim_status']);
            });
        }

        if (! empty($this->filters['warranty_id'])) {
            $query->whereHas('claim', function ($q) {
                $q->where('warranty_id', $this->filters['warranty_id']);
            });
        }

        if (! empty($this->filters['service_type'])) {
            $query->where('service_type', $this->filters['service_type']);
        }

        if (! empty($this->filters['job_type'])) {
            $query->where('job_type', $this->filters['job_type']);
        }

        if (! empty($this->filters['doa'])) {
            $query->where('doa', $this->filters['doa']);
        }

        if (! empty($this->filters['invoice_no'])) {
            $query->where('invoice_no', 'like', '%'.$this->filters['invoice_no'].'%');
        }

        if (! empty($this->filters['ref'])) {
            $query->where('ref', 'like', '%'.$this->filters['ref'].'%');
        }

        if (! empty($this->filters['wo_assigned_date'])) {
            $query->whereDate('wo_assigned_date', Carbon::parse($this->filters['wo_assigned_date']));
        }

        if (! empty($this->filters['wo_closed_date'])) {
            $query->whereDate('wo_closed_date', Carbon::parse($this->filters['wo_closed_date']));
        }

        if (! empty($this->filters['wo_delivery_date'])) {
            $query->whereDate('wo_delivery_date', Carbon::parse($this->filters['wo_delivery_date']));
        }

        if (! empty($this->filters['invoice_date'])) {
            $query->whereDate('invoice_date', Carbon::parse($this->filters['invoice_date']));
        }

        if (! empty($this->filters['part_id'])) {
            $query->whereHas('parts.part', function ($q) {
                $q->where('part_id', 'like', '%'.$this->filters['part_id'].'%');
            });
        }

        if (! empty($this->filters['part_description'])) {
            $query->whereHas('parts.part', function ($q) {
                $q->where('part_description', 'like', '%'.$this->filters['part_description'].'%');
            });
        }

        if (! empty($this->filters['customer_phone'])) {
            $query->whereHas('claim', function ($q) {
                $q->where('customer_phone', 'like', '%'.$this->filters['customer_phone'].'%');
            });
        }

        if (! empty($this->filters['customer_name'])) {
            $query->whereHas('claim', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('customer_firstname', 'like', '%'.$this->filters['customer_name'].'%')
                        ->orWhere('customer_lastname', 'like', '%'.$this->filters['customer_name'].'%');
                });
            });
        }

        if (! empty($this->filters['product_serial'])) {
            $query->whereHas('claim.warranty', function ($q) {
                $q->where('product_serial', 'like', '%'.$this->filters['product_serial'].'%');
            });
        }

        if (! empty($this->filters['product_name'])) {
            $query->whereHas('claim.warranty', function ($q) {
                $q->where('product_name', 'like', '%'.$this->filters['product_name'].'%');
            });
        }

        if (! empty($this->filters['date'])) {
            $query->whereDate('wo_assigned_date', Carbon::parse($this->filters['date']));
        }

        if (! empty($this->filters['wo_number'])) {
            $query->where('wo_number', 'like', '%'.$this->filters['wo_number'].'%');
        }

        if (! empty($this->filters['claim_number'])) {
            $query->whereHas('claim', function ($q) {
                $q->where('claim_number', 'like', '%'.$this->filters['claim_number'].'%');
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
                'Category' => $workOrder->claim?->warranty?->category?->name,
                'Sub Category' => $workOrder->claim?->warranty?->subCategory?->name,
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
            'Category',
            'Sub Category',
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
