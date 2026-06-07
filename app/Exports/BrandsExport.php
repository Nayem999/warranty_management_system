<?php

namespace App\Exports;

use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandsExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Brand::query();

        if (! empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('short_name', 'like', '%'.$this->filters['search'].'%');
            });
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        $brands = $query->orderBy('id', 'desc')->get();

        return $brands->map(function ($brand) {
            return [
                'Name' => $brand->name,
                'Short Name' => $brand->short_name,
                'Description' => $brand->description,
                'Status' => $brand->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Short Name',
            'Description',
            'Status',
        ];
    }
}
