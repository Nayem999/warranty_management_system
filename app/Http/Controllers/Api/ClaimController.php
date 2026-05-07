<?php

namespace App\Http\Controllers\Api;

use App\Events\ClaimCreated;
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
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    use ApiResponse, EmailHelper, UserAccessFilter;

    private array $statuses = [
        'Not Assigned',
        'Open',
        'In Progress',
        'Closed(Repaired)',
        'Closed-(Without Repaired)',
        'Closed-(Replaced)',
        'Closed-(Reimbursed)',
        'Delivered',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Claim::query()->with([
            'product.brand',
            'product.category',
            'product.subCategory',
            'customer',
            'serviceCenter',
            'engineer',
            'courierIn',
            'courierOut',
            'assignedByUser',
            'creator',
            'workOrder.parts.part',
        ]);

        if ($user->isBrandRestricted()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('product', fn ($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()));
            });
        }

        if ($user->isServiceCenterRestricted()) {
            $query->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
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

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('claim_number', 'like', "%{$request->search}%")
                    ->orWhere('problem_description', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->has('job_type')) {
            $query->where('job_type', $request->job_type);
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

        if ($request->has('wo_delivery_date')) {
            $query->whereDate('wo_delivery_date', Carbon::parse($request->wo_delivery_date));
        }

        if ($request->has('invoice_date')) {
            $query->whereDate('invoice_date', Carbon::parse($request->invoice_date));
        }

        if ($request->has('part_id')) {
            $query->whereHas('workOrder.parts', function ($q) use ($request) {
                $q->where('part_id', 'like', "%{$request->part_id}%");
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
        if ($request->has('replace_product_name')) {
            $query->whereHas('workOrder', function ($q) use ($request) {
                $q->where('replace_product_name', 'like', "%{$request->replace_product_name}%");
            });
        }
        if ($request->has('replace_product_info')) {
            $query->whereHas('workOrder', function ($q) use ($request) {
                $q->where('replace_product_info', 'like', "%{$request->replace_product_info}%");
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
        $data = $request->validated();

        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;

        if (! $product) {
            return $this->error('Product not found.');
        }

        if (! $product->isActive()) {
            return $this->error('Product is not active or has expired.');
        }

        if ($product->is_countable) {
            $existingClaim = Claim::where('product_id', $data['product_id'])
                ->where('status', '!=', 'Delivered')
                ->first();

            if ($existingClaim) {
                return $this->error('A claim with status Open or Converted already exists for this product. Claim Number: '.$existingClaim->claim_number);
            }
        }

        $counter = Claim::where('product_id', $data['product_id'])->count() + 1;

        $data['claim_number'] = Claim::generateClaimNumber();
        $data['counter'] = $counter;
        $data['created_by'] = $request->user()->id;
        $data['claim_date'] = $data['claim_date'] ?? Carbon::today();
        $data['status'] = $data['status'] ?? 'Not Assigned';

        $claim = Claim::create($data);

        ActivityLog::log(
            $request->user()->id,
            'created',
            'Claim',
            $claim->claim_number,
            $claim->id
        );

        ClaimCreated::dispatch($claim);

        return $this->created($claim->load(['product.brand', 'customer', 'serviceCenter']), 'Claim created successfully.');
    }

    public function track(string $claimNumber): JsonResponse
    {
        $claim = Claim::with([
            'product.brand',
            'product.category',
            'customer',
            'serviceCenter',
            'workOrder',
        ])->where('claim_number', $claimNumber)->first();

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        return $this->success([
            'claim_number' => $claim->claim_number,
            'status' => $claim->status,
            'claim_date' => $claim->claim_date,
            'problem_description' => $claim->problem_description,
            'product' => $claim->product,
            'customer' => $claim->customer,
            'service_center' => $claim->serviceCenter,
            'work_order' => $claim->workOrder,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        $claimQuery = Claim::with(['product.brand', 'product.category', 'product.subCategory', 'customer', 'serviceCenter', 'creator', 'workOrder.parts.part', 'engineer', 'courierIn', 'courierOut']);

        if ($user && $user->user_type === 'client') {
            $claimQuery->where('customer_id', $user->id);
        } else {
            if ($user->isBrandRestricted()) {
                $claimQuery->where(function ($q) use ($user) {
                    $q->whereHas('product', fn ($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()));
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

        $activityTimeline = ActivityLog::with('user:id,first_name,last_name')
            ->where('log_type', 'Claim')
            ->where('log_type_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                if ($log->user) {
                    $log->user->name = $log->user->first_name.' '.$log->user->last_name;
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

        $claim->activity_timeline = $activityTimeline;

        return $this->success($claim);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        $statuses = implode(',', $this->statuses);
        $serviceTypes = implode(',', ['In Warranty', 'Warranty Void', 'DOA', 'OOW/Expired']);
        $jobTypes = implode(',', ['Carry In', 'On Site', 'Pick Up']);

        $data = $request->validate([
            'service_center_id' => 'nullable|exists:wms_service_centers,id',
            'problem_description' => 'nullable|string',
            'claim_date' => 'nullable|date',
            'status' => "nullable|in:{$statuses}",
            'engineer_id' => 'nullable|exists:users,id',
            'courier_in_id' => 'nullable|exists:wms_couriers,id',
            'courier_slip_inward' => 'nullable|string',
            'courier_out_id' => 'nullable|exists:wms_couriers,id',
            'courier_slip_outward' => 'nullable|string',
            'received_date_time' => 'nullable|date',
            'delivered_date_time' => 'nullable|date',
            'counter' => 'nullable|integer|min:0',
            'wo_assigned_date' => 'nullable|date',
            'wo_closed_date' => 'nullable|date',
            'wo_delivery_date' => 'nullable|date',
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
            'replace_product_name' => 'nullable|string',
            'replace_product_info' => 'nullable|string',
            'replace_ref' => 'nullable|string',
            'parts' => 'nullable|array',
            'job_remarks' => 'nullable|string',
            'accessories' => 'nullable|string|max:500',
        ]);

        $oldData = $claim->toArray();
        $claim->update($data);
        if (
            isset($data['replace_serial']) ||
            isset($data['replace_product_name']) ||
            isset($data['replace_product_info']) ||
            isset($data['replace_ref']) ||
            isset($data['parts'])
        ) {

            $workOrder = $claim->workOrder;

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
                'replace_product_name' => $data['replace_product_name'] ?? null,
                'replace_product_info' => $data['replace_product_info'] ?? null,
                'replace_ref' => $data['replace_ref'] ?? null,
            ]);

            if (isset($data['parts'])) {
                $workOrder->parts()->delete();

                foreach ($data['parts'] as $partData) {
                    $workOrder->parts()->create($partData);
                }
            }
        }

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'Claim',
            $claim->claim_number,
            $claim->id,
            ['old' => $oldData, 'new' => $claim->toArray()]
        );

        return $this->success($claim->load([
            'product.brand',
            'customer',
            'serviceCenter',
            'engineer',
            'courierIn',
            'courierOut',
            'assignedByUser',
            'workOrder.parts.part', // ✅ include parts
        ]), 'Claim updated successfully.');
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
            $claim->id
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
        $status = request()->status ?? 'Closed(Repaired)';

        if (! in_array($status, $this->statuses)) {
            return $this->error('Invalid status. Allowed: '.$statuses);
        }

        $claim->update([
            'status' => $status,
            'wo_closed_date' => now()->toDateString(),
        ]);

        ActivityLog::log(
            request()->user()->id,
            'updated',
            'Claim',
            $claim->claim_number,
            $claim->id,
            ['action' => 'closed', 'status' => $status]
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

        $claim->attachments = array_values(array_filter($attachments, fn ($file) => $file !== $fileName));
        $claim->save();

        $filePath = storage_path('app/public/claims/'.$fileName);
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
                $log->user->name = $log->user->first_name.' '.$log->user->last_name;
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
}
