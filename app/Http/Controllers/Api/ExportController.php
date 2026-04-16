<?php

namespace App\Http\Controllers\Api;

use App\Exports\ClaimsExport;
use App\Exports\WarrantiesExport;
use App\Exports\WorkOrdersExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function downloadClaims(Request $request)
    {
        $filters = $request->only([
            'status',
            'warranty_id',
            'service_center_id',
            'brand_id',
            'date_from',
            'date_to',
        ]);

        $filename = 'claims-'.now()->format('Y-m-d-H-i-s').'.xlsx';

        return Excel::download(new ClaimsExport($filters), $filename);
    }

    public function downloadWarranties(Request $request)
    {
        $filters = $request->only([
            'brand_id',
            'category_id',
            'status',
            'is_active',
            'date_from',
            'date_to',
            'search',
        ]);

        $filename = 'warranties-'.now()->format('Y-m-d-H-i-s').'.xlsx';

        return Excel::download(new WarrantiesExport($filters), $filename);
    }

    public function downloadWorkOrders(Request $request)
    {
        $filters = $request->only([
            'status', 'service_center_id', 'courier_in_id', 'courier_out_id', 'engineer_id', 'brand_id', 'category_id', 'sub_category_id', 'claim_id', 'claim_status', 'warranty_id', 'service_type', 'job_type', 'invoice_no', 'ref', 'wo_assigned_date', 'wo_closed_date',  'wo_delivery_date', 'invoice_date', 'customer_phone', 'customer_name', 'product_serial', 'product_name',  'wo_number', 'claim_number', 'part_id', 'part_description'
        ]);

        $filename = 'work-orders-'.now()->format('Y-m-d-H-i-s').'.xlsx';

        return Excel::download(new WorkOrdersExport($filters), $filename);
    }
}
