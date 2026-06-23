<?php

namespace App\Http\Controllers\Api;

use App\Exports\BrandsExport;
use App\Exports\CategoriesExport;
use App\Exports\CitiesExport;
use App\Exports\ClaimsExport;
use App\Exports\CustomersExport;
use App\Exports\ProductsExport;
use App\Exports\WorkOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\City;
use App\Models\Claim;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function downloadClaims(Request $request)
    {
        $filters = $request->only([
            'search',
            'search_include',
            'brand_id','category_id','sub_category_id','wo_date','service_center_id','product_id','&engineer_id','case_date','order_date','received_date','install_date','return_date','part_id','part_description','part_status','part_qty_used','service_type','job_type','wo_delivery_date','customer_rating','courier_in_id','courier_out_id','attachment','status',
        ]);

        if ($request->input('format') === 'pdf') {
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
                'workOrder.parts.faultyPart'
            ]);

            if ($request->has('status') && $request->filled('status')) {
                $statuses = array_filter(array_map('trim', explode(',', $request->status)));
                if (!empty($statuses)) {
                    $query->whereIn('status', $statuses);
                }
            }

            if ($request->has('brand_id')) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('product', function ($q) use ($request) {
                        $q->where('brand_id', $request->brand_id);
                    });
                });
            }
            if ($request->has('category_id')) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('product', function ($q) use ($request) {
                        $q->where('category_id', $request->category_id);
                    });
                });
            }
            if ($request->has('sub_category_id')) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('product', function ($q) use ($request) {
                        $q->where('sub_category_id', $request->sub_category_id);
                    });
                });
            }

            if ($request->has('service_center_id')) {
                $query->where('service_center_id', $request->service_center_id);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('engineer_id')) {
                $query->where('engineer_id', $request->engineer_id);
            }

            if ($request->has('date_from')) {
                $query->where('claim_date', '>=', Carbon::parse($request->date_from));
            }

            if ($request->has('date_to')) {
                $query->where('claim_date', '<=', Carbon::parse($request->date_to));
            }

            if ($request->has('search') && $request->filled('search_include')) {
                $searchFields = explode(',', $request->search_include);
                $query->where(function ($q) use ($request, $searchFields) {
                    foreach ($searchFields as $field) {
                        $field = trim($field);
                        if (empty($field)) continue;

                        switch ($field) {
                            case 'wo_number':
                                $q->orWhereHas('workOrder', fn($q) => $q->where('wo_number', 'like', "%{$request->search}%"));
                                break;
                            case 'customer_name':
                                $q->orWhereHas('customer', fn($q) => $q->where('customer_name', 'like', "%{$request->search}%"));
                                break;
                            case 'customer_email':
                                $q->orWhereHas('customer', fn($q) => $q->where('email', 'like', "%{$request->search}%"));
                                break;
                            case 'customer_phone':
                                $q->orWhereHas('customer', fn($q) => $q->where('phone', 'like', "%{$request->search}%"));
                                break;
                            case 'product_serial':
                                $q->orWhereHas('product', fn($q) => $q->where('product_serial', 'like', "%{$request->search}%"));
                                break;
                            case 'problem':
                                $q->orWhere('problem_description', 'like', "%{$request->search}%");
                                break;
                            case 'case_id':
                                $q->orWhereHas('workOrder.parts', fn($q) => $q->where('case_id', 'like', "%{$request->search}%"));
                                break;
                            case 'order_id':
                                $q->orWhereHas('workOrder.parts', fn($q) => $q->where('order_id', 'like', "%{$request->search}%"));
                                break;
                            case 'part_return_comment':
                                $q->orWhereHas('workOrder.parts', fn($q) => $q->where('part_return_comment', 'like', "%{$request->search}%"));
                                break;
                            case 'replacement_item_description':
                                $q->orWhereHas('workOrder.replaceProduct', fn($q) => $q->where('item_description', 'like', "%{$request->search}%"));
                                break;
                            case 'replacement_item_serial':
                                $q->orWhereHas('workOrder', fn($q) => $q->where('replace_serial', 'like', "%{$request->search}%"));
                                break;
                            case 'work_done_comment':
                                $q->orWhere('work_done_comment', 'like', "%{$request->search}%");
                                break;
                            case 'claim_number':
                                $q->orWhere('claim_number', 'like', "%{$request->search}%");
                                break;
                            case 'customer_feedback':
                                $q->orWhere('customer_feedback', 'like', "%{$request->search}%");
                                break;
                            case 'complaint':
                                $q->orWhere('additional_comment', 'like', "%{$request->search}%");
                                break;
                            case 'aging':
                                $q->orWhere('aging', 'like', "%{$request->search}%");
                                break;
                        }
                    }
                });
            }

            if ($request->has('part_qty_used') && $request->filled('part_qty_used')) {
                $query->has('workOrder.parts', '=', $request->part_qty_used);
            }

            if ($request->has('service_type') && $request->filled('service_type')) {
                $query->where('service_type', $request->service_type);
            }

            if ($request->has('job_type') && $request->filled('job_type')) {
                $query->where('job_type', $request->job_type);
            }

            if ($request->has('wo_delivery_date') && $request->filled('wo_delivery_date')) {
                $query->whereDate('wo_delivery_date', Carbon::parse($request->wo_delivery_date));
            }

            if ($request->has('customer_rating') && $request->filled('customer_rating')) {
                $query->where('customer_rating', $request->customer_rating);
            }

            if ($request->has('courier_in_id') && $request->filled('courier_in_id')) {
                $query->where('courier_in_id', $request->courier_in_id);
            }

            if ($request->has('courier_out_id') && $request->filled('courier_out_id')) {
                $query->where('courier_out_id', $request->courier_out_id);
            }

            if ($request->has('attachment') && $request->filled('attachment')) {
                if (strtolower($request->attachment) === 'Yes') {
                    $query->whereNotNull('attachments')->where('attachments', '!=', '[]');
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('attachments')->orWhere('attachments', '[]');
                    });
                }
            }

            if ($request->has('doa')) {
                $query->where('doa', $request->doa);
            }

            if ($request->has('invoice_no')) {
                $query->where('invoice_no', 'like', "%{$request->invoice_no}%");
            }

            if ($request->has('ref')) {
                $query->where('ref', 'like', "%{$request->ref}%");
            }

            if ($request->has('wo_assigned_date')) {
                $query->whereDate('wo_assigned_date', Carbon::parse($request->wo_assigned_date));
            }

            if ($request->has('wo_closed_date')) {
                $query->whereDate('wo_closed_date', Carbon::parse($request->wo_closed_date));
            }

            if ($request->has('invoice_date')) {
                $query->whereDate('invoice_date', Carbon::parse($request->invoice_date));
            }

            if ($request->has('part_id') && $request->filled('part_id')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->where('part_id', $request->part_id);
                });
            }

            if ($request->has('part_description')) {
                $query->whereHas('workOrder.parts.part', function ($q) use ($request) {
                    $q->where('part_description', 'like', "%{$request->part_description}%");
                });
            }

            if ($request->has('case_id')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->where('case_id', 'like', "%{$request->case_id}%");
                });
            }
            if ($request->has('case_date')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->whereDate('case_date', Carbon::parse($request->case_date));
                });
            }
            if ($request->has('order_id')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->where('order_id', 'like', "%{$request->order_id}%");
                });
            }
            if ($request->has('order_date')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->whereDate('order_date', Carbon::parse($request->order_date));
                });
            }
            if ($request->has('received_date')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->whereDate('received_date', Carbon::parse($request->received_date));
                });
            }
            if ($request->has('install_date')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->whereDate('install_date', Carbon::parse($request->install_date));
                });
            }
            if ($request->has('return_date')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->whereDate('return_date', Carbon::parse($request->return_date));
                });
            }
            if ($request->has('part_status')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->where('part_status', 'like', "%{$request->part_status}%");
                });
            }
            if ($request->has('part_return_comment')) {
                $query->whereHas('workOrder.parts', function ($q) use ($request) {
                    $q->where('part_return_comment', 'like', "%{$request->part_return_comment}%");
                });
            }

            if ($request->has('work_done_comment')) {
                $query->where('work_done_comment', 'like', "%{$request->work_done_comment}%");
            }

            if ($request->has('city')) {
                $query->whereHas('customer.city', function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->city}%");
                });
            }
            if ($request->has('customer_phone')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('phone', 'like', "%{$request->customer_phone}%");
                });
            }
            if ($request->has('replace_serial')) {
                $query->whereHas('workOrder', function ($q) use ($request) {
                    $q->where('replace_serial', 'like', "%{$request->replace_serial}%");
                });
            }
            if ($request->has('replace_product_id')) {
                $query->whereHas('workOrder', function ($q) use ($request) {
                    $q->where('replace_product_id', $request->replace_product_id);
                });
            }

            if ($request->has('customer_name')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->where('customer_name', 'like', "%{$request->customer_name}%")
                            ->orWhere('contact_person', 'like', "%{$request->customer_name}%");
                    });
                });
            }
            if ($request->has('customer_email')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->where('email', 'like', "%{$request->customer_email}%");
                    });
                });
            }
            if ($request->has('customer_phone')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->where('phone', 'like', "%{$request->customer_phone}%");
                    });
                });
            }

            if ($request->has('product_serial')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('product_serial', 'like', "%{$request->product_serial}%");
                });
            }

            if ($request->has('model_no')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('model_no', 'like', "%{$request->model_no}%");
                });
            }
            if ($request->has('item_description')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('item_description', 'like', "%{$request->item_description}%");
                });
            }

            if ($request->has('wo_number')) {
                $query->whereHas('workOrder', function ($q) use ($request) {
                    $q->where('wo_number', 'like', "%{$request->wo_number}%");
                });
            }
            if ($request->has('wo_date')) {
                $query->whereDate('wo_assigned_date', Carbon::parse($request->wo_date));
            }

            $claims = $query->orderBy('id', 'desc')->get();
            $filename = 'claims-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            return Pdf::loadView('exports.claims-pdf', compact('claims'))->download($filename);
        }

        $filename = 'claims-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

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
                    $q->where('model_no', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('item_description', 'like', '%' . $filters['search'] . '%');
                });
            }

            $products = $query->orderBy('id', 'desc')->get();
            $filename = 'products-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            return Pdf::loadView('exports.products-pdf', compact('products'))->download($filename);
        }

        $filename = 'products-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new ProductsExport($filters), $filename);
    }

    public function downloadWorkOrders(Request $request)
    {
        $filters = $request->only([
            'status',
            'service_center_id',
            'courier_in_id',
            'courier_out_id',
            'engineer_id',
            'brand_id',
            'category_id',
            'sub_category_id',
            'claim_id',
            'claim_status',
            'warranty_id',
            'service_type',
            'job_type',
            'invoice_no',
            'ref',
            'wo_assigned_date',
            'wo_closed_date',
            'wo_delivery_date',
            'invoice_date',
            'customer_phone',
            'customer_name',
            'product_serial',
            'product_name',
            'wo_number',
            'claim_number',
            'part_id',
            'part_description'
        ]);

        $filename = 'work-orders-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new WorkOrdersExport($filters), $filename);
    }

    public function downloadCustomers(Request $request)
    {
        $filters = $request->only([
            'search',
            'city_id',
            'city',
        ]);

        if ($request->input('format') === 'pdf') {
            $query = Customer::with('city');

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('customer_name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('phone', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('contact_person', 'like', '%' . $filters['search'] . '%');
                });
            }

            if (!empty($filters['city_id'])) {
                $query->where('city_id', $filters['city_id']);
            }

            if (!empty($filters['city'])) {
                $query->whereHas('city', function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['city'] . '%');
                });
            }

            $customers = $query->orderBy('customer_name')->get();
            $filename = 'customers-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            return Pdf::loadView('exports.customers-pdf', compact('customers'))->download($filename);
        }

        $filename = 'customers-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new CustomersExport($filters), $filename);
    }

    public function downloadCategories(Request $request)
    {
        $filters = $request->only([
            'parent_id',
            'search',
            'status',
        ]);

        if ($request->input('format') === 'pdf') {
            $query = ProductCategory::query()->with('parent');

            if (!empty($filters['parent_id'])) {
                if ($filters['parent_id'] === 'null') {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $filters['parent_id']);
                }
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('short_name', 'like', '%' . $filters['search'] . '%');
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $categories = $query->orderBy('name')->get();
            $filename = 'categories-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            return Pdf::loadView('exports.categories-pdf', compact('categories'))->download($filename);
        }

        $filename = 'categories-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new CategoriesExport($filters), $filename);
    }

    public function downloadBrands(Request $request)
    {
        $filters = $request->only([
            'search',
            'status',
        ]);

        if ($request->input('format') === 'pdf') {
            $query = Brand::query();

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('short_name', 'like', '%' . $filters['search'] . '%');
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $brands = $query->orderBy('id', 'desc')->get();
            $filename = 'brands-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            return Pdf::loadView('exports.brands-pdf', compact('brands'))->download($filename);
        }

        $filename = 'brands-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new BrandsExport($filters), $filename);
    }

    public function downloadCities(Request $request)
    {
        $filters = $request->only([
            'search',
            'status',
        ]);

        if ($request->input('format') === 'pdf') {
            $query = City::query();

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('code', 'like', '%' . $filters['search'] . '%');
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $cities = $query->orderBy('id', 'desc')->get();
            $filename = 'cities-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            return Pdf::loadView('exports.cities-pdf', compact('cities'))->download($filename);
        }

        $filename = 'cities-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new CitiesExport($filters), $filename);
    }
}
