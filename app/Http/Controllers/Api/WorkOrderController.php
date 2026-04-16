<?php

namespace App\Http\Controllers\Api;

use App\Events\WorkOrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrder\SubmitFeedbackRequest;
use App\Http\Requests\WorkOrder\UpdateWorkOrderRequest;
use App\Models\ActivityLog;
use App\Models\Warranty;
use App\Models\WorkOrder;
use App\Traits\ApiResponse;
use App\Traits\FileUpload;
use App\Traits\UserAccessFilter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    use ApiResponse, FileUpload, UserAccessFilter;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = WorkOrder::query()->with([
            'claim.warranty.brand',
            'claim.warranty.category',
            'claim.warranty.subCategory',
            'serviceCenter',
            'courierIn',
            'courierOut',
            'engineer',
            'creator',
            'parts.part',
        ]);

        if ($user->isBrandRestricted()) {
            $query->whereHas('claim.warranty', fn ($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()));
        }

        if ($user->isServiceCenterRestricted()) {
            $query->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('service_center_id')) {
            $query->where('service_center_id', $request->service_center_id);
        }

        if ($request->has('courier_in_id')) {
            $query->where('courier_in_id', $request->courier_in_id);
        }

        if ($request->has('courier_out_id')) {
            $query->where('courier_out_id', $request->courier_out_id);
        }

        if ($request->has('engineer_id')) {
            $query->where('engineer_id', $request->engineer_id);
        }

        if ($request->has('brand_id')) {
            $query->whereHas('claim.warranty', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        if ($request->has('category_id')) {
            $query->whereHas('claim.warranty', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->has('sub_category_id')) {
            $query->whereHas('claim.warranty', function ($q) use ($request) {
                $q->where('sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->has('claim_id')) {
            $query->where('claim_id', $request->claim_id);
        }

        if ($request->has('claim_status')) {
            $query->whereHas('claim', function ($q) use ($request) {
                $q->where('status', $request->claim_status);
            });
        }

        if ($request->has('warranty_id')) {
            $query->whereHas('claim', function ($q) use ($request) {
                $q->where('warranty_id', $request->warranty_id);
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
            $query->whereHas('parts.part', function ($q) use ($request) {
                $q->where('part_id', 'like', "%{$request->part_id}%");
            });
        }

        if ($request->has('part_description')) {
            $query->whereHas('parts.part', function ($q) use ($request) {
                $q->where('part_description', 'like', "%{$request->part_description}%");
            });
        }

        if ($request->has('customer_phone')) {
            $query->whereHas('claim', function ($q) use ($request) {
                $q->where('customer_phone', 'like', "%{$request->customer_phone}%");
            });
        }

        if ($request->has('customer_name')) {
            $query->whereHas('claim', function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('customer_firstname', 'like', "%{$request->customer_name}%")
                        ->orWhere('customer_lastname', 'like', "%{$request->customer_name}%");
                });
            });
        }

        if ($request->has('product_serial')) {
            $query->whereHas('claim.warranty', function ($q) use ($request) {
                $q->where('product_serial', 'like', "%{$request->product_serial}%");
            });
        }

        if ($request->has('product_name')) {
            $query->whereHas('claim.warranty', function ($q) use ($request) {
                $q->where('product_name', 'like', "%{$request->product_name}%");
            });
        }

        if ($request->has('wo_number')) {
            $query->where('wo_number', 'like', "%{$request->wo_number}%");
        }

        if ($request->has('claim_number')) {
            $query->where('claim', function ($q2) use ($request) {
                $q2->where('claim_number', 'like', "%{$request->search}%");
            });
        }

        $workOrders = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($workOrders);
    }

    public function show(int $id): JsonResponse
    {
        $user = request()->user();

        $workOrderQuery = WorkOrder::with([
            'claim.warranty.brand',
            'claim.warranty.category',
            'serviceCenter',
            'courierIn',
            'courierOut',
            'engineer',
            'creator',
            'assignedBy',
            'parts.part',
        ]);

        if ($user->isBrandRestricted()) {
            $workOrderQuery->whereHas('claim.warranty', fn ($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()));
        }

        if ($user->isServiceCenterRestricted()) {
            $workOrderQuery->whereIn('service_center_id', $user->accessibleServiceCenterIds());
        }

        $workOrder = $workOrderQuery->find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        $activityLogs = ActivityLog::with('user:id,first_name,last_name,email')
            ->where('log_type', 'WorkOrder')
            ->where('log_type_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $activityLogs->transform(function ($log) {
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

        return $this->success([
            'work_order' => $workOrder,
            'activity_timeline' => $activityLogs,
        ]);
    }

    public function update(UpdateWorkOrderRequest $request, int $id): JsonResponse
    {
        $workOrder = WorkOrder::find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        $data = $request->validated();

        $oldData = $workOrder->toArray();

        $previousStatus = $workOrder->status;
        if ($data['status'] != $previousStatus) {

            $statusFlow = ['Progress', 'Closed', 'Delivered'];
            $currentIndex = array_search($workOrder->status, $statusFlow);
            $newIndex = array_search($data['status'], $statusFlow);

            if ($newIndex < $currentIndex && $workOrder->status !== $data['status']) {
                return $this->error('Invalid status transition. Cannot go back to previous status.');
            }

            if ($data['status'] === 'Closed') {
                $data['wo_closed_date'] = now();
                $data['tat'] = $workOrder->wo_assigned_date
                    ? Carbon::parse($workOrder->wo_assigned_date)->diffInDays(now())
                    : null;
            }

            if ($data['status'] === 'Delivered') {
                $data['wo_delivery_date'] = now();
                $data['delivered_date_time'] = now();
            }
        }

        if (! isset($data['attachments']) || empty($data['attachments'])) {
            unset($data['attachments']);
        }

        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            $paths = $this->uploadFiles($files, 'work-orders');
            $existingAttachments = $workOrder->attachments ?? [];
            if (is_array($existingAttachments)) {
                $data['attachments'] = json_encode(array_merge($existingAttachments, $paths));
            } else {
                $data['attachments'] = json_encode($paths);
            }
        } elseif (isset($data['attachments']) && $data['attachments']) {
            $newAttachments = $this->handleAttachments($data['attachments'], 'work-orders');
            $existingAttachments = $workOrder->attachments ?? [];

            if (is_string($existingAttachments) && ! empty($existingAttachments)) {
                $existingAttachments = json_decode($existingAttachments, true) ?? [];
            }

            if (! empty($existingAttachments) && $newAttachments) {
                $decodedNew = json_decode($newAttachments, true);
                if ($decodedNew === null) {
                    $decodedNew = [$newAttachments];
                }
                $data['attachments'] = json_encode(array_merge($existingAttachments, $decodedNew));
            } else {
                $data['attachments'] = $newAttachments;
            }
        }

        if (isset($data['wo_closed_date']) && $data['wo_closed_date']) {
            $data['tat'] = Carbon::parse($workOrder->wo_assigned_date)->diffInDays(Carbon::parse($data['wo_closed_date']));
        }

        $parts = null;
        if (isset($data['parts'])) {
            $parts = $data['parts'];
            unset($data['parts']);
        }

        $workOrder->update($data);

        if ($parts) {
            $workOrder->parts()->delete();
            foreach ($parts as $partData) {
                $partData['work_order_id'] = $workOrder->id;
                $workOrder->parts()->create($partData);
            }
        }

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'WorkOrder',
            $workOrder->wo_number,
            $workOrder->id,
            ['old' => $oldData, 'new' => $workOrder->toArray()]
        );

        if ($workOrder->status != $previousStatus) {
            WorkOrderStatusUpdated::dispatch($workOrder->load(['claim', 'claim.warranty']), $previousStatus);
        }

        return $this->success($workOrder->load(['claim.warranty.brand', 'serviceCenter', 'courierIn', 'courierOut', 'engineer', 'parts.part']), 'Work order updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $workOrder = WorkOrder::find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        ActivityLog::log(
            request()->user()->id,
            'deleted',
            'WorkOrder',
            $workOrder->wo_number,
            $workOrder->id
        );

        $workOrder->delete();

        return $this->deleted('Work order deleted successfully.');
    }

    public function assignServiceCenter(Request $request, int $id): JsonResponse
    {
        $workOrder = WorkOrder::find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }
        $previousData = $workOrder;
        $data = $request->validate([
            'service_center_id' => 'required|exists:wms_service_centers,id',
        ]);

        $workOrder->update([
            'service_center_id' => $data['service_center_id'],
            'wo_assigned_date' => $workOrder->wo_assigned_date ?? now(),
            'assigned_by' => $request->user()->id,
        ]);

        ActivityLog::log(
            $request->user()->id,
            'assigned_service_center',
            'WorkOrder',
            $workOrder->wo_number,
            $workOrder->id,
            ['old' => $previousData, 'new' => $workOrder]
        );

        return $this->success($workOrder->load(['serviceCenter']), 'Service center assigned successfully.');
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $workOrder = WorkOrder::with('claim.warranty', 'replacedWarranty')->find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        $data = $request->validate([
            'status' => 'required|in:Progress,Closed,Delivered',
            'replace_serial' => 'nullable|string|max:255',
        ]);

        $previousData = $workOrder;
        $previousStatus = $workOrder->status;

        $statusFlow = ['Progress', 'Closed', 'Delivered'];
        $currentIndex = array_search($workOrder->status, $statusFlow);
        $newIndex = array_search($data['status'], $statusFlow);

        if ($newIndex < $currentIndex && $workOrder->status !== $data['status']) {
            return $this->error('Invalid status transition. Cannot go back to previous status.');
        }

        $updateData = ['status' => $data['status']];

        if ($data['status'] === 'Closed') {
            $updateData['wo_closed_date'] = now();
            $updateData['tat'] = $workOrder->wo_assigned_date
                ? Carbon::parse($workOrder->wo_assigned_date)->diffInDays(now())
                : null;
        }

        $replaceSerial = $data['replace_serial'] ?? null;
        $existingReplacedWarranty = $workOrder->replacedWarranty;

        if (! empty($replaceSerial) && $workOrder->claim?->warranty) {
            if ($existingReplacedWarranty) {
                if ($existingReplacedWarranty->product_serial !== $replaceSerial) {
                    $oldSerial = $existingReplacedWarranty->product_serial;
                    $existingReplacedWarranty->update(['product_serial' => $replaceSerial]);

                    ActivityLog::log(
                        $request->user()->id,
                        'serial_updated',
                        'Warranty',
                        $existingReplacedWarranty->product_serial,
                        $existingReplacedWarranty->id,
                        ['old' => $previousData, 'old_serial' => $existingReplacedWarranty]
                    );
                }
            } else {
                $originalWarranty = $workOrder->claim->warranty;

                $newWarranty = Warranty::create([
                    'product_serial' => $replaceSerial,
                    'product_name' => $originalWarranty->product_name,
                    'product_info' => $originalWarranty->product_info,
                    'brand_id' => $originalWarranty->brand_id,
                    'category_id' => $originalWarranty->category_id,
                    'sub_category_id' => $originalWarranty->sub_category_id,
                    'start_date' => $originalWarranty->start_date,
                    'end_date' => $originalWarranty->end_date,
                    'created_by' => $request->user()->id,
                ]);

                ActivityLog::log(
                    $request->user()->id,
                    'create',
                    'Warranty',
                    $newWarranty->product_serial,
                    $newWarranty->id
                );

                $updateData['replaced_warranty_id'] = $newWarranty->id;
            }

            $updateData['replace_serial'] = $replaceSerial;
        } elseif (empty($replaceSerial) && $existingReplacedWarranty) {
            $deletedWarrantySerial = $existingReplacedWarranty->product_serial;
            $deletedWarrantyId = $existingReplacedWarranty->id;
            $existingReplacedWarranty->delete();

            ActivityLog::log(
                $request->user()->id,
                'deleted',
                'Warranty',
                $deletedWarrantySerial,
                $deletedWarrantyId,
                ['action' => 'replaced_warranty_removed']
            );

            $updateData['replace_serial'] = null;
            $updateData['replaced_warranty_id'] = null;
        }

        if ($data['status'] === 'Delivered') {
            $updateData['wo_delivery_date'] = now();
            $updateData['delivered_date_time'] = now();
        }

        $workOrder->update($updateData);

        ActivityLog::log(
            $request->user()->id,
            'status_changed',
            'WorkOrder',
            $workOrder->wo_number,
            $workOrder->id,
            ['old' => $previousData, 'new' => $workOrder]
        );

        WorkOrderStatusUpdated::dispatch($workOrder->load(['claim', 'claim.warranty']), $previousStatus);

        return $this->success($workOrder->load(['claim.warranty.brand', 'claim.warranty.category', 'serviceCenter', 'replacedWarranty']), 'Work order status updated successfully.');
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
        $workOrder = WorkOrder::where('feedback_token', $token)->first();

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        if (! $workOrder->feedback_preference) {
            return $this->error('Feedback preference is disabled for this work order.');
        }

        $data = $request->validated();

        $workOrder->update([
            'customer_feedback' => $data['customer_feedback'],
            'customer_rating' => $data['customer_rating'],
        ]);

        return $this->success($workOrder, 'Feedback submitted successfully.');
    }

    public function pending(Request $request): JsonResponse
    {
        $workOrders = WorkOrder::with(['claim.warranty.brand', 'serviceCenter'])
            ->pending()
            ->orderBy('wo_assigned_date', 'asc')
            ->paginate($request->limit ?? 15);

        return $this->success($workOrders);
    }

    public function overdue(Request $request): JsonResponse
    {
        $workOrders = WorkOrder::with(['claim.warranty.brand', 'serviceCenter'])
            ->overdue()
            ->orderBy('wo_assigned_date', 'asc')
            ->paginate($request->limit ?? 15);

        return $this->success($workOrders);
    }

    public function activityTimeline(int $id): JsonResponse
    {
        $workOrder = WorkOrder::find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        $activityLogs = ActivityLog::with('user:id,first_name,last_name,email')
            ->where('log_type', 'WorkOrder')
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
