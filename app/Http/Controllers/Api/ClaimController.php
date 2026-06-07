<?php

namespace App\Http\Controllers\Api;

use App\Events\ClaimCreated;
use App\Events\ClaimStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Claim\StoreClaimRequest;
use App\Http\Requests\WorkOrder\SubmitFeedbackRequest;
use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Traits\ApiResponse;
use App\Traits\EmailHelper;
use App\Traits\UserAccessFilter;
use App\Traits\FileUpload;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    use ApiResponse, EmailHelper, UserAccessFilter, FileUpload;

    private array $statuses = [
        'Not Assigned',
        'Assigned',
        'In Progress',
        'Waiting for Part',
        'Closed-Repaired',
        'Closed-Un Repaired',
        'Closed-Replaced',
        'Closed-Reimbursement',
        'Delivered',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Claim::query()->with([
            'product.brand',
            'product.category',
            'product.subCategory',
            'customer.city',
            'serviceCenter',
            'transferredFromServiceCenter',
            'engineer',
            'courierIn',
            'assignedByUser',
            'creator',
            'workOrder.replaceProduct',
            'workOrder.parts.part',
            'workOrder.parts.faultyPart',
        ]);

        if ($user->isBrandRestricted()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()));
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $query->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

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

        if ($request->has('transferred_from_service_center_id')) {
            $query->where('transferred_from_service_center_id', $request->transferred_from_service_center_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('engineer_id')) {
            $query->where('engineer_id', $request->engineer_id);
        }

        if ($request->has('date_from')) {
            $query->where('claim_date', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->has('date_to')) {
            $query->where('claim_date', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        if ($request->has('wo_closed_date_from')) {
            $query->where('wo_closed_date', '>=', Carbon::parse($request->wo_closed_date_from)->startOfDay());
        }

        if ($request->has('wo_closed_date_to')) {
            $query->where('wo_closed_date', '<=', Carbon::parse($request->wo_closed_date_to)->endOfDay());
        }

        if ($request->has('transferred_at_from')) {
            $query->where('transferred_at', '>=', Carbon::parse($request->transferred_at_from)->startOfDay());
        }

        if ($request->has('transferred_at_to')) {
            $query->where('transferred_at', '<=', Carbon::parse($request->transferred_at_to)->endOfDay());
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
                            $q->orWhere('serial_number', 'like', "%{$request->search}%");
                            break;
                        case 'model_no':
                            $q->orWhereHas('product', fn($q) => $q->where('model_no', 'like', "%{$request->search}%"));
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
                        case 'transfer_reason':
                            $q->orWhere('transfer_reason', 'like', "%{$request->search}%");
                            break;
                        case 'aging':
                            $q->orWhere('tat', $request->search);
                            break;
                    }
                }
            });
        } else if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->orWhereHas('workOrder', fn($q) => $q->where('wo_number', 'like', "%{$request->search}%"))
                    ->orWhereHas('customer', fn($q) => $q->where('customer_name', 'like', "%{$request->search}%"))
                    ->orWhereHas('customer', fn($q) => $q->where('email', 'like', "%{$request->search}%"))
                    ->orWhereHas('customer', fn($q) => $q->where('phone', 'like', "%{$request->search}%"))
                    ->orWhere('serial_number', 'like', "%{$request->search}%")
                    ->orWhereHas('product', fn($q) => $q->where('model_no', 'like', "%{$request->search}%"))
                    ->orWhereHas('product.brand', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                    ->orWhereHas('product.category', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                    ->orWhereHas('product.subCategory', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                    ->orWhere('problem_description', 'like', "%{$request->search}%")
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('case_id', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('order_id', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('good_part_serial', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('faulty_part_serial', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('part_status', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('part_return_comment', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('labour_claim_id', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('faulty_part_id', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.parts', fn($q) => $q->where('faulty_description', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder.replaceProduct', fn($q) => $q->where('item_description', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder', fn($q) => $q->where('replace_serial', 'like', "%{$request->search}%"))
                    ->orWhereHas('workOrder', fn($q) => $q->where('replace_ref', 'like', "%{$request->search}%"))
                    ->orWhere('work_done_comment', 'like', "%{$request->search}%")
                    ->orWhere('claim_number', 'like', "%{$request->search}%")
                    ->orWhere('customer_feedback', 'like', "%{$request->search}%")
                    ->orWhere('additional_comment', 'like', "%{$request->search}%")
                    ->orWhere('invoice_no', 'like', "%{$request->search}%")
                    ->orWhere('ref', 'like', "%{$request->search}%")
                    ->orWhere('service_type', 'like', "%{$request->search}%")
                    ->orWhere('job_type', 'like', "%{$request->search}%")
                    ->orWhere('job_remarks', 'like', "%{$request->search}%")
                    ->orWhere('accessories', 'like', "%{$request->search}%")
                    ->orWhere('status', 'like', "%{$request->search}%")
                    ->orWhere('transfer_reason', 'like', "%{$request->search}%")
                    ->orWhere('tat', $request->search);
            });
        }

        if ($request->has('part_qty_used') && $request->filled('part_qty_used')) {
            $query->whereHas('workOrder.parts', function ($q) use ($request) {
                $q->where('qty_used', $request->part_qty_used);
            });
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

        if ($request->has('delivery_id') && $request->filled('delivery_id')) {
            $query->where('delivery_id', $request->delivery_id);
        }

        if ($request->has('is_delivered') && $request->filled('is_delivered')) {
            $query->where('is_delivered', $request->is_delivered);
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

        $claims = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($claims);
    }

    public function store(StoreClaimRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;
            $serialNumber = $data['serial_number'] ?? null;

            if (! $product) {
                return $this->error('Product not found.');
            }

            if (! $product->isActive()) {
                return $this->error('Product is not active or has expired.');
            }
            if ($product->is_countable && !$serialNumber) {
                return $this->error('Serial Number Required For Claim Countable Product');
            }
            $existingClaim = 0;
            if ($product->is_countable && $serialNumber) {
                $existingClaim = Claim::where('serial_number', $serialNumber)
                    ->count();

                $prev_Claim = Claim::where('serial_number', $serialNumber)
                    ->whereIn('status', ['Not Assigned', 'Assigned', 'In Progress', 'Waiting for Part'])
                    ->first();

                if ($prev_Claim) {
                    return $this->error("A claim with status " . $prev_Claim->status . " already exists for this product. Claim Number: " . $prev_Claim->claim_number);
                }

                $existingClaim = $existingClaim + 1;
            }

            $data['claim_number'] = Claim::generateClaimNumber();
            $data['counter'] = $existingClaim;
            $data['created_by'] = $request->user()->id;
            $data['claim_date'] = isset($data['claim_date']) ? Carbon::parse($data['claim_date'])->format('Y-m-d H:i:s') : Carbon::now()->format('Y-m-d H:i:s');
            $data['status'] = $data['status'] ?? 'Not Assigned';

            if (! empty($data['attachments'])) {
                $attachments = $this->handleAttachments($data['attachments'], 'claims');
                $data['attachments'] = $attachments ? json_decode($attachments, true) : [];
            }

            $claim = Claim::create($data);

            ActivityLog::log(
                $request->user()->id,
                'created',
                'Claim',
                $claim->claim_number,
                $claim->id,
                ['status' => $claim->status, 'comment' => $claim->status_comment]
            );

            ClaimCreated::dispatch($claim);

            DB::commit();

            return $this->created($claim->load(['product.brand', 'customer', 'serviceCenter']), 'Claim created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function track(string $claimNumber): JsonResponse
    {
        $claim = Claim::with([
            'product.brand',
            'product.category',
            'customer.city',
            'serviceCenter',
            'workOrder',
        ])
            ->where(function ($query) use ($claimNumber) {
                $query->where('claim_number', $claimNumber)
                    ->orWhere('serial_number', $claimNumber);
            })
            ->first();

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        return $this->success([
            'claim_number' => $claim->claim_number,
            'serial_number' => $claim->serial_number,
            'status' => $claim->status,
            'status_comment' => $claim->status_comment,
            'is_delivered' => $claim->is_delivered,
            'completion_date' => $claim->wo_closed_date,
            'delivery_date' => $claim->wo_delivery_date,
            'claim_date' => $claim->claim_date,
            'problem_description' => $claim->problem_description,
            'product' => $claim->product,
            'customer' => $claim->customer,
            'service_center' => $claim->serviceCenter,
            'work_order' => $claim->workOrder,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        $claimQuery = Claim::with(['product.brand', 'product.category', 'product.subCategory', 'customer.city', 'serviceCenter', 'transferredFromServiceCenter', 'creator', 'assignedByUser', 'workOrder.replaceProduct', 'workOrder.parts.part', 'workOrder.parts.faultyPart', 'engineer', 'courierIn']);

        if ($user && $user->user_type === 'client') {
            $claimQuery->where('customer_id', $user->id);
        } else {
            if ($user->isBrandRestricted()) {
                $claimQuery->where(function ($q) use ($user) {
                    $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()));
                });
            }
            if ($user->isServiceCenterRestricted()) {
                $claimQuery->whereIn('service_center_id', $user->accessibleServiceCenterIds());
            }
        }

        $claim = $claimQuery->find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        if ($request->has('print')) {
            $claim->increment('view_count');
        }

        $activityTimeline = ActivityLog::with('user:id,first_name,last_name')
            ->where('log_type', 'Claim')
            ->where('log_type_id', $id)
            ->orderBy('id')
            ->get();

        $claim->activity_timeline = $activityTimeline;

        return $this->success($claim);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $claim = Claim::find($id);

            if (! $claim) {
                return $this->notFound('Claim not found.');
            }

            $statuses = implode(',', $this->statuses);
            $serviceTypes = implode(',', ['In Warranty', 'Warranty Void', 'DOA', 'OOW/Expired']);
            $jobTypes = implode(',', ['Carry In', 'On Site', 'Pick Up']);
            $open_status = array('Not Assigned', 'Assigned', 'In Progress', 'Waiting for Part');
            $close_status = array('Closed-Repaired', 'Closed-Un Repaired', 'Closed-Replaced', 'Closed-Reimbursement', 'Delivered');

            $data = $request->validate([
                'service_center_id' => 'required|exists:wms_service_centers,id',
                'problem_description' => 'nullable|string',
                'claim_date' => 'nullable|date_format:Y-m-d H:i:s',
                'status' => "required|in:{$statuses}",
                'engineer_id' => 'nullable|exists:users,id',
                'courier_in_id' => 'nullable|exists:wms_couriers,id',
                'courier_slip_inward' => 'nullable|string',
                'received_date_time' => 'nullable|date_format:Y-m-d H:i:s',
                'delivery_id' => 'nullable|exists:wms_delivery_challans,id',
                'is_delivered' => 'nullable|boolean',
                'counter' => 'nullable|integer|min:0',
                'wo_assigned_date' => 'nullable|date_format:Y-m-d H:i:s',
                'wo_closed_date' => 'nullable|date_format:Y-m-d H:i:s',
                'wo_delivery_date' => 'nullable|date_format:Y-m-d H:i:s',
                'tat' => 'nullable|integer|min:0',
                'doa' => 'nullable|boolean',
                'invoice_no' => 'nullable|string',
                'invoice_date' => 'nullable|date',
                'purchase_price' => 'nullable|numeric|min:0',
                'ref' => 'nullable|string',
                'web_wty_date' => 'nullable|date',
                'additional_comment' => 'nullable|string',
                'work_done_comment' => 'nullable|string',
                'customer_feedback' => 'nullable|string',
                'customer_rating' => 'nullable|integer|min:1|max:5',
                'status_comment' => 'nullable|string',
                'service_type' => "nullable|in:{$serviceTypes}",
                'job_type' => "nullable|in:{$jobTypes}",
                'assigned_by' => 'nullable|exists:users,id',

                'replace_serial' => 'nullable|string',
                'replace_product_id' => 'nullable|integer|exists:wms_products,id',
                'replace_ref' => 'nullable|string',
                'parts' => 'nullable|array',
                'parts.*.part_id' => 'required_with:parts|exists:wms_parts,id',
                'job_remarks' => 'nullable|string',
                'accessories' => 'nullable|string|max:500',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|string',
                'transferred_from_service_center_id' => 'nullable|exists:wms_service_centers,id',
                'transferred_at' => 'nullable|date_format:Y-m-d H:i:s',
                'transfer_reason' => 'nullable|string',
            ]);

            if ($claim->status == "Not Assigned" && $data['status'] == "Not Assigned" && !empty($data['engineer_id'])) {
                $data['assigned_by'] = $request->user()->id;
                $data['wo_assigned_date'] = $data['wo_assigned_date'] ?? Carbon::now()->format('Y-m-d H:i:s');
                $data['status'] = "Assigned";
            } else if (empty($claim->engineer_id) && !empty($data['engineer_id'])) {
                $data['assigned_by'] = $request->user()->id;
                $data['wo_assigned_date'] = $data['wo_assigned_date'] ?? Carbon::now()->format('Y-m-d H:i:s');
            }


            if (in_array($claim->status, $open_status) && in_array($data['status'], $close_status) && empty($data['wo_closed_date'])) {
                $data['wo_closed_date'] = $data['wo_closed_date'] ?? Carbon::now()->format('Y-m-d H:i:s');
            }

            if (in_array($claim->status, $open_status)) {
                $data['tat'] = Carbon::parse($claim->claim_date)->diffInDays(now());
            }

            $workOrder = $claim->workOrder;
            if (! $workOrder && isset($data['parts']) && $claim->status == $data['status']) {
                $data['status'] = "Waiting for Part";
            }

            if (! empty($data['attachments'])) {
                $existingAttachments = $claim->attachments ?? [];
                $newAttachments = $this->handleAttachments($data['attachments'], 'claims');
                $newAttachmentsArray = $newAttachments ? json_decode($newAttachments, true) : [];
                $data['attachments'] = array_merge($existingAttachments, $newAttachmentsArray);
            }

            if (!empty($data['transferred_from_service_center_id'])) {
                if ($data['transferred_from_service_center_id'] == $data['service_center_id']) {
                    return $this->error('Transferred service center cannot be the same as the current service center.');
                }
                if (empty($data['transferred_at'])) {
                    $data['transferred_at'] = Carbon::now()->format('Y-m-d H:i:s');
                }
            }


            $previousStatus = $claim->status;
            if (! $claim->is_delivered) {
                // DB::rollBack();
                // return $this->error("Claim already delivered, Claim can not update");
                // $claim->update($data);
                // DB::enableQueryLog();
                $claim->update($data);
                // dd(DB::getQueryLog());
                $claim->refresh();
                if ($previousStatus !== $claim->status) {
                    ClaimStatusUpdated::dispatch($claim, $previousStatus);
                }
            }

            if (
                !empty($data['replace_serial']) ||
                !empty($data['replace_product_id']) ||
                !empty($data['replace_ref']) ||
                !empty($data['parts'])
            ) {

                if (! $workOrder) {
                    $workOrder = WorkOrder::create([
                        'wo_number' => WorkOrder::generateWoNumber(),
                        'claim_id' => $claim->id,
                        'product_id' => $claim->product_id,
                        'service_center_id' => $claim->service_center_id,
                        'status' => 'Closed',
                        'created_by' => $request->user()->id,
                    ]);
                }

                $workOrder->update([
                    'replace_serial' => $data['replace_serial'] ?? null,
                    'replace_product_id' => $data['replace_product_id'] ?? null,
                    'replace_ref' => $data['replace_ref'] ?? null,
                ]);

                if ($workOrder->parts()) {
                    $workOrder->parts()->delete();
                }

                if (isset($data['parts'])) {
                    foreach ($data['parts'] as $partData) {
                        $workOrder->parts()->create($partData);
                    }
                }
            } else {
                if ($claim->workOrder) {
                    if ($claim->workOrder->parts()) {
                        $claim->workOrder->parts()->delete();
                    }
                    $claim->workOrder->delete();
                }
            }

            ActivityLog::log(
                $request->user()->id,
                'updated',
                'Claim',
                $claim->claim_number,
                $claim->id,
                ['status' => $claim->status, 'comment' => $claim->status_comment]
            );

            DB::commit();

            return $this->success($claim->load([
                'product.brand',
                'customer',
                'serviceCenter',
                'transferredFromServiceCenter',
                'engineer',
                'courierIn',
                'assignedByUser',
                'workOrder.parts.part',
            ]), 'Claim updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function transfer(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $claim = Claim::find($id);

            if (! $claim) {
                return $this->notFound('Claim not found.');
            }

            $close_status = array('Closed-Repaired', 'Closed-Un Repaired', 'Closed-Replaced', 'Closed-Reimbursement', 'Delivered');

            $data = $request->validate([
                'service_center_id' => 'required|exists:wms_service_centers,id',
                'transferred_at' => 'nullable|date_format:Y-m-d H:i:s',
                'transfer_reason' => 'nullable|string',
            ]);

            if ($claim->is_delivered) {
                return $this->error("This Claim already delivered. So it's can't transfere");
            }

            if (in_array($claim->status, $close_status)) {
                return $this->error("This Claim already closed. So it's can't transfere");
            }

            if ($claim->service_center_id == $data['service_center_id']) {
                return $this->error('Transferred service center cannot be the same as the current service center.');
            }

            if (empty($data['transferred_at'])) {
                $data['transferred_at'] = Carbon::now()->format('Y-m-d H:i:s');
            }
            $data['transferred_from_service_center_id'] = $claim->service_center_id;

            $claim->update($data);
            $claim->refresh();

            DB::commit();

            return $this->success($claim->load([
                'product.brand',
                'customer',
                'serviceCenter',
                'transferredFromServiceCenter',
                'engineer',
                'courierIn',
                'assignedByUser',
                'workOrder.parts.part',
            ]), 'Claim updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        if ($claim->workOrder) {
            return $this->error('Cannot delete claim with associated work order.');
        }

        ActivityLog::log(
            request()->user()->id,
            'deleted',
            'Claim',
            $claim->claim_number,
            $claim->id,
            ['status' => $claim->status, 'comment' => $claim->status_comment]
        );

        $claim->delete();

        return $this->deleted('Claim deleted successfully.');
    }

    public function close(int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        $statuses = implode(',', $this->statuses);
        $status = request()->status ?? 'Closed (Repaired)';

        if (! in_array($status, $this->statuses)) {
            return $this->error('Invalid status. Allowed: ' . $statuses);
        }

        $previousStatus = $claim->status;

        $claim->update([
            'status' => $status,
            'wo_closed_date' => now()->toDateString(),
        ]);

        ClaimStatusUpdated::dispatch($claim, $previousStatus);

        ActivityLog::log(
            request()->user()->id,
            'updated',
            'Claim',
            $claim->claim_number,
            $claim->id,
            ['status' => $claim->status, 'comment' => $claim->status_comment]
        );

        return $this->success($claim, 'Claim closed successfully.');
    }

    public function getFeedbackLink(int $id): JsonResponse
    {
        $workOrder = WorkOrder::find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        if (! $workOrder->feedback_preference) {
            return $this->error('Feedback preference is disabled for this work order.');
        }

        $baseUrl = config('app.frontend_url', 'http://localhost:3000');
        $feedbackUrl = "{$baseUrl}/feedback/{$workOrder->feedback_token}";

        return $this->success([
            'feedback_url' => $feedbackUrl,
            'feedback_token' => $workOrder->feedback_token,
        ]);
    }

    public function submitFeedback(SubmitFeedbackRequest $request, string $token): JsonResponse
    {
        $claim = Claim::where('feedback_token', $token)->first();

        if (! $claim) {
            return $this->notFound('Work order not found.');
        }

        if (! $claim->feedback_preference) {
            return $this->error('Feedback preference is disabled for this work order.');
        }

        $data = $request->validated();

        $claim->update([
            'customer_feedback' => $data['customer_feedback'],
            'customer_rating' => $data['customer_rating'],
        ]);

        return $this->success($claim, 'Feedback submitted successfully.');
    }

    public function deleteAttachment(Request $request, int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        $fileName = $request->input('file_name');

        if (! $fileName) {
            return $this->error('File name is required.');
        }

        $attachments = $claim->attachments ?? [];

        if (! in_array($fileName, $attachments)) {
            return $this->error('File not found in attachments.');
        }

        $claim->attachments = array_values(array_filter($attachments, fn($file) => $file !== $fileName));
        $claim->save();

        $filePath = storage_path('app/public/claims/' . $fileName);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return $this->success($claim, 'Attachment deleted successfully.');
    }

    public function activityTimeline(int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        $activityLogs = ActivityLog::with('user:id,first_name,last_name,email')
            ->where('log_type', 'Claim')
            ->where('log_type_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(request('limit', 15));

        $activityLogs->getCollection()->transform(function ($log) {
            if ($log->user) {
                $log->user->name = $log->user->first_name . ' ' . $log->user->last_name;
            }

            if ($log->changes && isset($log->changes['old']) && isset($log->changes['new'])) {
                $oldData = $log->changes['old'];
                $newData = $log->changes['new'];
                $filteredChanges = [];

                foreach ($newData as $key => $value) {
                    if (! array_key_exists($key, $oldData) || $oldData[$key] !== $value) {
                        $filteredChanges[$key] = [
                            'old' => array_key_exists($key, $oldData) ? $oldData[$key] : null,
                            'new' => $value,
                        ];
                    }
                }

                $log->changes = $filteredChanges;
            }

            return $log;
        });

        return $this->success($activityLogs);
    }


    public function getDeliveryList(int $id): JsonResponse
    {
        $user = auth()->user();

        $claimQuery = Claim::with(['product.brand']);

        if ($user->isBrandRestricted()) {
            $claimQuery->whereHas('product', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $claimQuery->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

        $claim = $claimQuery->find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        $claimListQuery = Claim::with(['product.brand'])
            ->where('customer_id', $claim->customer_id)
            ->where('service_center_id', $claim->service_center_id)
            ->where('is_delivered', 0)
            ->whereIn('status', [
                'Closed-Repaired',
                'Closed-Un Repaired',
                'Closed-Replaced',
                'Closed-Reimbursement'
            ]);

        // Apply same restrictions for delivery list
        if ($user->isBrandRestricted()) {
            $claimListQuery->whereHas('product', function ($q) use ($user) {
                $q->whereIn('brand_id', $user->accessibleBrandIds());
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $claimListQuery->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

        $claimList = $claimListQuery->get();

        if ($claimList->isEmpty()) {
            return $this->notFound('Claim not found for delivery.');
        }

        return $this->success($claimList);
    }
}
