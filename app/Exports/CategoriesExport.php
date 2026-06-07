<?php

namespace App\Exports;

use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoriesExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = ProductCategory::query()->with('parent');

        if (! empty($this->filters['parent_id'])) {
            if ($this->filters['parent_id'] === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $this->filters['parent_id']);
            }
        }

        if (! empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('short_name', 'like', '%'.$this->filters['search'].'%');
            });
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        $categories = $query->orderBy('name')->get();

        return $categories->map(function ($category) {
            return [
                'Name' => $category->name,
                'Short Name' => $category->short_name,
                'Parent Category' => $category->parent?->name,
                'Status' => $category->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Short Name',
            'Parent Category',
            'Status',
        ];
    }
}
