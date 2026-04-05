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
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = WorkOrder::query()->with(['claim.warranty.brand', 'serviceCenter', 'creator']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('service_center_id')) {
            $query->where('service_center_id', $request->service_center_id);
        }

        if ($request->has('brand_id')) {
            $query->whereHas('claim.warranty', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        if ($request->has('date_from')) {
            $query->where('wo_assigned_date', '>=', Carbon::parse($request->date_from));
        }

        if ($request->has('date_to')) {
            $query->where('wo_assigned_date', '<=', Carbon::parse($request->date_to));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('wo_number', 'like', "%{$request->search}%")
                    ->orWhereHas('claim', function ($q2) use ($request) {
                        $q2->where('claim_number', 'like', "%{$request->search}%");
                    });
            });
        }

        $workOrders = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($workOrders);
    }

    public function show(int $id): JsonResponse
    {
        $workOrder = WorkOrder::with([
            'claim.warranty.brand',
            'claim.warranty.category',
            'serviceCenter',
            'creator',
            'assignedBy',
        ])->find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        return $this->success($workOrder);
    }

    public function update(UpdateWorkOrderRequest $request, int $id): JsonResponse
    {
        $workOrder = WorkOrder::find($id);

        if (! $workOrder) {
            return $this->notFound('Work order not found.');
        }

        $data = $request->validated();

        $oldData = $workOrder->toArray();

        if (isset($data['wo_closed_date']) && $data['wo_closed_date']) {
            $data['tat'] = Carbon::parse($workOrder->wo_assigned_date)->diffInDays(Carbon::parse($data['wo_closed_date']));
        }

        $workOrder->update($data);

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'WorkOrder',
            $workOrder->wo_number,
            $workOrder->id,
            ['old' => $oldData, 'new' => $workOrder->toArray()]
        );

        return $this->success($workOrder->load(['claim.warranty.brand', 'serviceCenter']), 'Work order updated successfully.');
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
            'updated',
            'WorkOrder',
            $workOrder->wo_number,
            $workOrder->id,
            ['action' => 'assigned_service_center', 'service_center_id' => $data['service_center_id']]
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
            'status' => 'required|in:Pending,In Progress,Completed,Delivered',
            'replace_serial' => 'nullable|string|max:255',
        ]);

        $previousStatus = $workOrder->status;

        $statusFlow = ['Pending', 'In Progress', 'Completed', 'Delivered'];
        $currentIndex = array_search($workOrder->status, $statusFlow);
        $newIndex = array_search($data['status'], $statusFlow);

        if ($newIndex < $currentIndex && $workOrder->status !== $data['status']) {
            return $this->error('Invalid status transition. Cannot go back to previous status.');
        }

        $updateData = ['status' => $data['status']];

        if ($data['status'] === 'Completed') {
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
                        'updated',
                        'Warranty',
                        $existingReplacedWarranty->product_serial,
                        $existingReplacedWarranty->id,
                        ['action' => 'serial_updated', 'old_serial' => $oldSerial]
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
                    'is_void' => 'NO',
                    'void_reason' => null,
                    'created_by' => $request->user()->id,
                ]);

                ActivityLog::log(
                    $request->user()->id,
                    'created',
                    'Warranty',
                    $newWarranty->product_serial,
                    $newWarranty->id,
                    ['action' => 'replaced', 'original_serial' => $originalWarranty->product_serial]
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
        }

        $workOrder->update($updateData);

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'WorkOrder',
            $workOrder->wo_number,
            $workOrder->id,
            ['action' => 'status_changed', 'new_status' => $data['status']]
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
}
