<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Customer::with('city');

        if (! empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('phone', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('contact_person', 'like', '%'.$this->filters['search'].'%');
            });
        }

        if (! empty($this->filters['city_id'])) {
            $query->where('city_id', $this->filters['city_id']);
        }

        if (! empty($this->filters['city'])) {
            $query->whereHas('city', function ($q) {
                $q->where('name', 'like', '%'.$this->filters['city'].'%');
            });
        }

        $customers = $query->orderBy('customer_name')->get();

        return $customers->map(function ($customer) {
            return [
                'Customer Name' => $customer->customer_name,
                'Contact Person' => $customer->contact_person,
                'Email' => $customer->email,
                'Phone' => $customer->phone,
                'Landline' => $customer->landline,
                'Address' => $customer->address,
                'City' => $customer->city?->name,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'Contact Person',
            'Email',
            'Phone',
            'Landline',
            'Address',
            'City',
        ];
    }
}
