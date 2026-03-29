<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrder\UpdateWorkOrderRequest;
use App\Http\Requests\WorkOrder\SubmitFeedbackRequest;
use App\Models\WorkOrder;
use App\Models\Claim;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

        $workOrders = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 15);

        return $this->success($workOrders);
    }

    public function show(int $id): JsonResponse
    {
        $workOrder = WorkOrder::with([
            'claim.warranty.brand',
            'claim.warranty.category',
            'serviceCenter',
            'creator',
            'assignedBy'
        ])->find($id);

        if (!$workOrder) {
            return $this->notFound('Work order not found.');
        }

        return $this->success($workOrder);
    }

    public function update(UpdateWorkOrderRequest $request, int $id): JsonResponse
    {
        $workOrder = WorkOrder::find($id);

        if (!$workOrder) {
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

        if (!$workOrder) {
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

        if (!$workOrder) {
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
        $workOrder = WorkOrder::find($id);

        if (!$workOrder) {
            return $this->notFound('Work order not found.');
        }

        $data = $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed,Delivered',
        ]);

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

        return $this->success($workOrder, 'Work order status updated successfully.');
    }

    public function getFeedbackLink(int $id): JsonResponse
    {
        $workOrder = WorkOrder::find($id);

        if (!$workOrder) {
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

        if (!$workOrder) {
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
            ->paginate($request->per_page ?? 15);

        return $this->success($workOrders);
    }

    public function overdue(Request $request): JsonResponse
    {
        $workOrders = WorkOrder::with(['claim.warranty.brand', 'serviceCenter'])
            ->overdue()
            ->orderBy('wo_assigned_date', 'asc')
            ->paginate($request->per_page ?? 15);

        return $this->success($workOrders);
    }
}
