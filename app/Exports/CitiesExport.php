<?php

namespace App\Exports;

use App\Models\City;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CitiesExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = City::query();

        if (! empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('code', 'like', '%'.$this->filters['search'].'%');
            });
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        $cities = $query->orderBy('id', 'desc')->get();

        return $cities->map(function ($city) {
            return [
                'Name' => $city->name,
                'Code' => $city->code,
                'Status' => $city->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Code',
            'Status',
        ];
    }
}
