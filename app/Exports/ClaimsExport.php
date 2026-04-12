<?php

namespace App\Exports;

use App\Models\Claim;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClaimsExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Claim::with(['warranty.brand', 'serviceCenter', 'creator']);

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['warranty_id'])) {
            $query->where('warranty_id', $this->filters['warranty_id']);
        }

        if (! empty($this->filters['service_center_id'])) {
            $query->where('service_center_id', $this->filters['service_center_id']);
        }

        if (! empty($this->filters['brand_id'])) {
            $query->whereHas('warranty', function ($q) {
                $q->where('brand_id', $this->filters['brand_id']);
            });
        }

        if (! empty($this->filters['date_from'])) {
            $query->where('claim_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->where('claim_date', '<=', $this->filters['date_to']);
        }

        $claims = $query->orderBy('id', 'desc')->get();

        return $claims->map(function ($claim) {
            return [
                'Claim Number' => $claim->claim_number,
                'Status' => $claim->status,
                'Claim Date' => $claim->claim_date,
                'Customer Name' => $claim->customer_firstname.' '.$claim->customer_lastname,
                'Customer Email' => $claim->customer_email,
                'Customer Phone' => $claim->customer_phone,
                'Customer City' => $claim->customer_city,
                'Product Serial' => $claim->warranty?->product_serial,
                'Product Name' => $claim->warranty?->product_name,
                'Brand' => $claim->warranty?->brand?->name,
                'Service Center' => $claim->serviceCenter?->title,
                'Problem Description' => $claim->problem_description,
                'Created By' => $claim->creator?->first_name.' '.$claim->creator?->last_name,
                'Created At' => $claim->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Claim Number',
            'Status',
            'Claim Date',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Customer City',
            'Product Serial',
            'Product Name',
            'Brand',
            'Service Center',
            'Problem Description',
            'Created By',
            'Created At',
        ];
    }
}
