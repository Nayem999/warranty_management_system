<?php

namespace App\Exports;

use App\Models\DeliveryChallan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DeliveryChallansExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DeliveryChallan::with(['customer', 'serviceCenter', 'courierOut']);

        if (!empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('delivery_number', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('courier_slip_outward', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('created_at', '<=', $this->filters['date_to']);
        }

        $challans = $query->orderBy('id', 'desc')->get();

        return $challans->map(function ($challan) {
            return [
                'Delivery Number' => $challan->delivery_number,
                'Customer' => $challan->customer?->customer_name,
                'Service Center' => $challan->serviceCenter?->title,
                'Courier' => $challan->courierOut?->name,
                'Courier Slip' => $challan->courier_slip_outward,
                'Delivered Date Time' => $challan->delivered_date_time,
                'Delivered Remarks' => $challan->delivered_remarks,
                'Claim Numbers' => $challan->claims->pluck('claim_number')->implode(', '),
                'Created At' => $challan->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Delivery Number',
            'Customer',
            'Service Center',
            'Courier',
            'Courier Slip',
            'Delivered Date Time',
            'Delivered Remarks',
            'Claim Numbers',
            'Created At',
        ];
    }
}
