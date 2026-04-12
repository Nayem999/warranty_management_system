<?php

namespace App\Exports;

use App\Models\Warranty;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarrantiesExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Warranty::with(['brand', 'category', 'creator']);

        if (! empty($this->filters['brand_id'])) {
            $query->where('brand_id', $this->filters['brand_id']);
        }

        if (! empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('is_void', $this->filters['status'] === 'active' ? 'NO' : 'YES');
        }

        if (! empty($this->filters['is_active'])) {
            $query->where('is_active', $this->filters['is_active'] === 'true' || $this->filters['is_active'] === true);
        }

        if (! empty($this->filters['date_from'])) {
            $query->where('start_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->where('end_date', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('product_serial', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('product_name', 'like', '%'.$this->filters['search'].'%');
            });
        }

        $warranties = $query->orderBy('id', 'desc')->get();

        return $warranties->map(function ($warranty) {
            return [
                'Serial Number' => $warranty->product_serial,
                'Product Name' => $warranty->product_name,
                'Product Info' => $warranty->product_info,
                'Brand' => $warranty->brand?->name,
                'Category' => $warranty->category?->name,
                'Start Date' => $warranty->start_date,
                'End Date' => $warranty->end_date,
                'Status' => $warranty->isActive() ? 'Active' : 'Inactive',
                'Is Void' => $warranty->is_void,
                'Created By' => $warranty->creator?->first_name.' '.$warranty->creator?->last_name,
                'Created At' => $warranty->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Serial Number',
            'Product Name',
            'Product Info',
            'Brand',
            'Category',
            'Start Date',
            'End Date',
            'Status',
            'Is Void',
            'Created By',
            'Created At',
        ];
    }
}
