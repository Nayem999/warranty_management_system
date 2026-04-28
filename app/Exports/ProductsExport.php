<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Product::with(['brand', 'category', 'creator']);

        if (! empty($this->filters['brand_id'])) {
            $query->where('brand_id', $this->filters['brand_id']);
        }

        if (! empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (! empty($this->filters['status'])) {
            $status = $this->filters['status'];
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'expired') {
                $query->expired();
            }
        }

        if (! empty($this->filters['date_from'])) {
            $query->where('start_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->where('end_date', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('model_no', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('serial_number', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('item_description', 'like', '%'.$this->filters['search'].'%');
            });
        }

        $products = $query->orderBy('id', 'desc')->get();

        return $products->map(function ($product) {
            return [
                'Model No' => $product->model_no,
                'Serial Number' => $product->serial_number,
                'Item Description' => $product->item_description,
                'Brand' => $product->brand?->name,
                'Category' => $product->category?->name,
                'Sub Category' => $product->subCategory?->name,
                'Is Countable' => $product->is_countable ? 'Yes' : 'No',
                'Start Date' => $product->start_date,
                'End Date' => $product->end_date,
                'Status' => $product->product_status,
                'Created By' => $product->creator?->first_name.' '.$product->creator?->last_name,
                'Created At' => $product->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Model No',
            'Serial Number',
            'Item Description',
            'Brand',
            'Category',
            'Sub Category',
            'Is Countable',
            'Start Date',
            'End Date',
            'Status',
            'Created By',
            'Created At',
        ];
    }
}