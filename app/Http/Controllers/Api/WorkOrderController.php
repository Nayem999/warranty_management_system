<?php

namespace App\Http\Controllers\Api;

use App\Events\WorkOrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrder\SubmitFeedbackRequest;
use App\Http\Requests\WorkOrder\UpdateWorkOrderRequest;
use App\Models\ActivityLog;
use App\Models\Product;
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
            'claim.product.brand',
            'claim.product.category',
            'claim.customer',
            'serviceCenter',
            'creator',
            'parts.part',
        ]);

        if ($user->isBrandRestricted()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('claim.product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()));
            });
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

        if ($request->has('brand_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('claim.product', function ($q) use ($request) {
                    $q->where('brand_id', $request->brand_id);
                });
            });
        }

        if ($request->has('category_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('claim.product', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            });
        }

        if ($request->has('sub_category_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('claim.product', function ($q) use ($request) {
                    $q->where('sub_category_id', $request->sub_category_id);
                });
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

        if ($request->has('product_id')) {
            $query->whereHas('claim', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        if ($request->has('product_description')) {
            $query->whereHas('claim.product', function ($q) use ($request) {
                $q->where('product_description', 'like', "%{$request->product_description}%");
            });
        }

        if ($request->has('product_serial')) {
            $query->whereHas('claim.product', function ($q) use ($request) {
                $q->where('product_serial', 'like', "%{$request->product_serial}%");
            });
        }

        if ($request->has('attachment')) {
            if ($request->attachment === 'yes') {
                $query->whereNotNull('attachment')
                    ->where('attachment', '!=', '');
            } elseif ($request->attachment === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('attachment')
                        ->orWhere('attachment', '');
                });
            }
        }

        if ($request->has('part_id')) {
            $query->whereHas('parts', function ($q) use ($request) {
                $q->where('part_id', $request->part_id);
            });
        }

        if ($request->has('part_description')) {
            $query->whereHas('parts.part', function ($q) use ($request) {
                $q->where('part_description', 'like', "%{$request->part_description}%");
            });
        }

        if ($request->has('case_id')) {
            $query->whereHas('parts', function ($q) use ($request) {
                $q->where('case_id', 'like', "%{$request->case_id}%");
            });
        }

        if ($request->has('customer_id')) {
            $query->whereHas('claim', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        if ($request->has('customer_name')) {
            $query->whereHas('claim.customer', function ($q) use ($request) {
                $q->where('customer_name', 'like', "%{$request->customer_name}%");
            });
        }

        if ($request->has('customer_phone')) {
            $query->whereHas('claim.customer', function ($q) use ($request) {
                $q->where('phone', 'like', "%{$request->customer_phone}%");
            });
        }

        if ($request->has('wo_number')) {
            $query->where('wo_number', 'like', "%{$request->wo_number}%");
        }

        if ($request->has('claim_number')) {
            $query->whereHas('claim', function ($q) use ($request) {
                $q->where('claim_number', 'like', "%{$request->claim_number}%");
            });
        }

        $workOrders = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($workOrders);
    }

    public function show(int $id): JsonResponse
    {
        $user = request()->user();

        $workOrderQuery = WorkOrder::with([
            'claim.product.brand',
            'claim.product.category',
            'claim.customer',
            'serviceCenter',
            'creator',
            'parts.part',
        ]);

        if ($user->isBrandRestricted()) {
            $workOrderQuery->whereHas('claim.product', fn($q) => $q->whereIn('brand_id', $user->accessibleBrandIds()));
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
            WorkOrderStatusUpdated::dispatch($workOrder->load(['claim', 'claim.product']), $previousStatus);
        }

        return $this->success($workOrder->load([
            'claim.product.brand',
            'serviceCenter',
            'parts.part',
        ]), 'Work order updated successfully.');
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

        $claim = $workOrder->claim;
        if ($claim) {
            $claim->update([
                'customer_feedback' => $data['customer_feedback'],
                'customer_rating' => $data['customer_rating'],
            ]);
        }

        $workOrder->update([
            'customer_feedback' => $data['customer_feedback'],
            'customer_rating' => $data['customer_rating'],
        ]);

        return $this->success($workOrder, 'Feedback submitted successfully.');
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
}
