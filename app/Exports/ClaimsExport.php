<?php

namespace App\Exports;

use App\Models\Claim;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClaimsExport implements FromCollection, WithHeadings
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Claim::with([
            'product.brand',
            'product.category',
            'product.subCategory',
            'customer.city',
            'serviceCenter',
            'engineer',
            'courierIn',
            'assignedByUser',
            'creator',
            'workOrder.replaceProduct',
            'workOrder.parts.part',
            'workOrder.parts.faultyPart',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Basic Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['status'])) {
            $statuses = array_filter(
                array_map('trim', explode(',', $this->filters['status']))
            );

            if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        if (!empty($this->filters['brand_id'])) {
            $query->whereHas('product', function ($q) {
                $q->where('brand_id', $this->filters['brand_id']);
            });
        }

        if (!empty($this->filters['category_id'])) {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->filters['category_id']);
            });
        }

        if (!empty($this->filters['sub_category_id'])) {
            $query->whereHas('product', function ($q) {
                $q->where('sub_category_id', $this->filters['sub_category_id']);
            });
        }

        if (!empty($this->filters['service_center_id'])) {
            $query->where('service_center_id', $this->filters['service_center_id']);
        }

        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['engineer_id'])) {
            $query->where('engineer_id', $this->filters['engineer_id']);
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['date_from'])) {
            $query->whereDate(
                'claim_date',
                '>=',
                Carbon::parse($this->filters['date_from'])
            );
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate(
                'claim_date',
                '<=',
                Carbon::parse($this->filters['date_to'])
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Search Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['search'])) {

            $search = $this->filters['search'];

            $searchFields = explode(
                ',',
                $this->filters['search_include'] ?? ''
            );

            $query->where(function ($q) use ($searchFields, $search) {

                foreach ($searchFields as $field) {

                    $field = trim($field);

                    if (empty($field)) {
                        continue;
                    }

                    switch ($field) {

                        case 'wo_number':
                            $q->orWhereHas('workOrder', fn($q2) =>
                                $q2->where('wo_number', 'like', "%{$search}%"));
                            break;

                        case 'customer_name':
                            $q->orWhereHas('customer', fn($q2) =>
                                $q2->where('customer_name', 'like', "%{$search}%"));
                            break;

                        case 'customer_email':
                            $q->orWhereHas('customer', fn($q2) =>
                                $q2->where('email', 'like', "%{$search}%"));
                            break;

                        case 'customer_phone':
                            $q->orWhereHas('customer', fn($q2) =>
                                $q2->where('phone', 'like', "%{$search}%"));
                            break;

                        case 'product_serial':
                            $q->orWhereHas('product', fn($q2) =>
                                $q2->where('product_serial', 'like', "%{$search}%"));
                            break;

                        case 'problem':
                            $q->orWhere(
                                'problem_description',
                                'like',
                                "%{$search}%"
                            );
                            break;

                        case 'claim_number':
                            $q->orWhere(
                                'claim_number',
                                'like',
                                "%{$search}%"
                            );
                            break;

                        case 'customer_feedback':
                            $q->orWhere(
                                'customer_feedback',
                                'like',
                                "%{$search}%"
                            );
                            break;

                        case 'complaint':
                            $q->orWhere(
                                'additional_comment',
                                'like',
                                "%{$search}%"
                            );
                            break;
                    }
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Extra Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['service_type'])) {
            $query->where('service_type', $this->filters['service_type']);
        }

        if (!empty($this->filters['job_type'])) {
            $query->where('job_type', $this->filters['job_type']);
        }

        if (!empty($this->filters['customer_rating'])) {
            $query->where(
                'customer_rating',
                $this->filters['customer_rating']
            );
        }

        if (!empty($this->filters['courier_in_id'])) {
            $query->where(
                'courier_in_id',
                $this->filters['courier_in_id']
            );
        }

        if (!empty($this->filters['courier_out_id'])) {
            $query->where(
                'courier_out_id',
                $this->filters['courier_out_id']
            );
        }

        if (!empty($this->filters['invoice_no'])) {
            $query->where(
                'invoice_no',
                'like',
                "%{$this->filters['invoice_no']}%"
            );
        }

        if (!empty($this->filters['ref'])) {
            $query->where(
                'ref',
                'like',
                "%{$this->filters['ref']}%"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Customer Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['customer_name'])) {
            $query->whereHas('customer', function ($q) {
                $q->where(function ($q2) {
                    $q2->where(
                        'customer_name',
                        'like',
                        "%{$this->filters['customer_name']}%"
                    )
                    ->orWhere(
                        'contact_person',
                        'like',
                        "%{$this->filters['customer_name']}%"
                    );
                });
            });
        }

        if (!empty($this->filters['customer_email'])) {
            $query->whereHas('customer', function ($q) {
                $q->where(
                    'email',
                    'like',
                    "%{$this->filters['customer_email']}%"
                );
            });
        }

        if (!empty($this->filters['customer_phone'])) {
            $query->whereHas('customer', function ($q) {
                $q->where(
                    'phone',
                    'like',
                    "%{$this->filters['customer_phone']}%"
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Product Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['product_serial'])) {
            $query->whereHas('product', function ($q) {
                $q->where(
                    'product_serial',
                    'like',
                    "%{$this->filters['product_serial']}%"
                );
            });
        }

        if (!empty($this->filters['model_no'])) {
            $query->whereHas('product', function ($q) {
                $q->where(
                    'model_no',
                    'like',
                    "%{$this->filters['model_no']}%"
                );
            });
        }

        if (!empty($this->filters['item_description'])) {
            $query->whereHas('product', function ($q) {
                $q->where(
                    'item_description',
                    'like',
                    "%{$this->filters['item_description']}%"
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Final Data
        |--------------------------------------------------------------------------
        */

        $claims = $query
            ->orderByDesc('id')
            ->get();

        return $claims->map(function ($claim) {

            return [
                'Claim Number'       => $claim->claim_number,
                'Status'             => $claim->status,
                'Claim Date'         => $claim->claim_date,
                'Customer Name'      => $claim->customer?->customer_name,
                'Customer Email'     => $claim->customer?->email,
                'Customer Phone'     => $claim->customer?->phone,
                'Customer City'      => $claim->customer?->city?->name,
                'Product Serial'     => $claim->product?->product_serial,
                'Product Name'       => $claim->product?->product_name,
                'Brand'              => $claim->product?->brand?->name,
                'Service Center'     => $claim->serviceCenter?->title,
                'Problem Description'=> $claim->problem_description,
                'Created By'         => trim(
                    ($claim->creator?->first_name ?? '') . ' ' .
                    ($claim->creator?->last_name ?? '')
                ),
                'Created At'         => $claim->created_at?->format('Y-m-d H:i:s'),
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
