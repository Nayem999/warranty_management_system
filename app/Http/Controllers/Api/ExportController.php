<?php

namespace App\Http\Controllers\Api;

use App\Exports\ClaimsExport;
use App\Exports\ProductsExport;
use App\Exports\WorkOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
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

        if ($request->input('format') === 'pdf') {
            $query = Claim::with(['product.brand', 'product.category', 'product.subCategory', 'customer.city', 'serviceCenter', 'creator', 'workOrder.parts.part', 'engineer', 'courierIn', 'courierOut']);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['warranty_id'])) {
                $query->where('warranty_id', $filters['warranty_id']);
            }
            if (!empty($filters['service_center_id'])) {
                $query->where('service_center_id', $filters['service_center_id']);
            }
            if (!empty($filters['brand_id'])) {
                $query->whereHas('warranty', fn ($q) => $q->where('brand_id', $filters['brand_id']));
            }
            if (!empty($filters['date_from'])) {
                $query->where('claim_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('claim_date', '<=', $filters['date_to']);
            }

            $claims = $query->orderBy('id', 'desc')->get();
            $filename = 'claims-'.now()->format('Y-m-d-H-i-s').'.pdf';

            return Pdf::loadView('exports.claims-pdf', compact('claims'))->download($filename);
        }

        $filename = 'claims-'.now()->format('Y-m-d-H-i-s').'.xlsx';

        return Excel::download(new ClaimsExport($filters), $filename);
    }

    public function downloadProducts(Request $request)
    {
        $filters = $request->only([
            'brand_id',
            'category_id',
            'status',
            'date_from',
            'date_to',
            'search',
        ]);

        if ($request->input('format') === 'pdf') {
            $query = Product::with(['brand', 'category', 'creator']);

            if (!empty($filters['brand_id'])) {
                $query->where('brand_id', $filters['brand_id']);
            }
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            if (!empty($filters['status'])) {
                $status = $filters['status'];
                if ($status === 'active') {
                    $query->active();
                } elseif ($status === 'expired') {
                    $query->expired();
                }
            }
            if (!empty($filters['date_from'])) {
                $query->where('start_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('end_date', '<=', $filters['date_to']);
            }
            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('model_no', 'like', '%'.$filters['search'].'%')
                        ->orWhere('item_description', 'like', '%'.$filters['search'].'%');
                });
            }

            $products = $query->orderBy('id', 'desc')->get();
            $filename = 'products-'.now()->format('Y-m-d-H-i-s').'.pdf';

            return Pdf::loadView('exports.products-pdf', compact('products'))->download($filename);
        }

        $filename = 'products-'.now()->format('Y-m-d-H-i-s').'.xlsx';

        return Excel::download(new ProductsExport($filters), $filename);
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
