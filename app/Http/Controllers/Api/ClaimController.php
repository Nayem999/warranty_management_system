<?php

namespace App\Http\Controllers\Api;

use App\Events\ClaimCreated;
use App\Events\WorkOrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Claim\ConvertToWorkOrderRequest;
use App\Http\Requests\Claim\StoreClaimRequest;
use App\Mail\ClientWelcomeEmail;
use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WorkOrder;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClaimController extends Controller
{
    use ApiResponse, EmailHelper;

    public function index(Request $request): JsonResponse
    {
        $query = Claim::query()->with(['warranty.brand', 'serviceCenter', 'creator', 'workOrder']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('brand_id')) {
            $query->whereHas('warranty', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        if ($request->has('service_center_id')) {
            $query->where('service_center_id', $request->service_center_id);
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
                    ->orWhere('customer_firstname', 'like', "%{$request->search}%")
                    ->orWhere('customer_lastname', 'like', "%{$request->search}%")
                    ->orWhere('customer_phone', 'like', "%{$request->search}%")
                    ->orWhere('customer_email', 'like', "%{$request->search}%");
            });
        }

        $claims = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($claims);
    }

    public function store(StoreClaimRequest $request): JsonResponse
    {
        $data = $request->validated();

        $warranty = Warranty::find($data['warranty_id']);

        if (! $warranty) {
            return $this->error('Warranty not found.');
        }

        if (! $warranty->isActive()) {
            return $this->error('Warranty is not active or has expired.');
        }

        $customerEmail = $data['customer_email'] ?? null;
        $customerUserId = null;

        if ($customerEmail) {
            $customerUser = User::where('email', $customerEmail)->first();

            if (! $customerUser) {
                $password = Str::random(12);
                $customerUser = User::create([
                    'first_name' => $data['customer_firstname'] ?? 'Customer',
                    'last_name' => $data['customer_lastname'] ?? '',
                    'email' => $customerEmail,
                    'password' => Hash::make($password),
                    'user_type' => 'client',
                    'role_id' => 4,
                    'phone' => $data['customer_phone'] ?? null,
                    'status' => 'active',
                ]);

                $this->sendEmail(new ClientWelcomeEmail($customerUser, $password), $customerUser->email, 'Welcome to Warranty Management System');
            }

            $customerUserId = $customerUser->id;
        }

        $data['claim_number'] = Claim::generateClaimNumber();
        $data['created_by'] = $request->user()->id;
        $data['claim_date'] = $data['claim_date'] ?? Carbon::today();
        $data['customer_user_id'] = $customerUserId;

        $claim = Claim::create($data);

        ActivityLog::log(
            $request->user()->id,
            'created',
            'Claim',
            $claim->claim_number,
            $claim->id
        );

        ClaimCreated::dispatch($claim);

        return $this->created($claim->load(['warranty.brand', 'serviceCenter']), 'Claim created successfully.');
    }

    public function publicStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warranty_id' => 'required|exists:wms_warranties,id',
            'problem_description' => 'required|string',
            'customer_firstname' => 'required|string|max:255',
            'customer_lastname' => 'nullable|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string|max:20',
            'customer_city' => 'nullable|string|max:100',
            'customer_address' => 'nullable|string',
            'service_center_id' => 'nullable|exists:wms_service_centers,id',
            'claim_date' => 'nullable|date',
        ]);

        $warranty = Warranty::find($data['warranty_id']);

        if (! $warranty) {
            return $this->error('Warranty not found.');
        }

        if (! $warranty->isActive()) {
            return $this->error('Warranty is not active or has expired.');
        }

        $customerEmail = $data['customer_email'];
        $customerUserId = null;
        $password = null;

        $customerUser = User::where('email', $customerEmail)->first();

        if (! $customerUser) {
            $password = Str::random(12);
            $customerUser = User::create([
                'first_name' => $data['customer_firstname'],
                'last_name' => $data['customer_lastname'] ?? '',
                'email' => $customerEmail,
                'password' => Hash::make($password),
                'user_type' => 'client',
                'role_id' => 4,
                'phone' => $data['customer_phone'] ?? null,
                'status' => 'active',
            ]);

            $this->sendEmail(new ClientWelcomeEmail($customerUser, $password), $customerUser->email, 'Welcome to Warranty Management System');
        }

        $customerUserId = $customerUser->id;

        $data['claim_number'] = Claim::generateClaimNumber();
        $data['created_by'] = $customerUserId;
        $data['claim_date'] = $data['claim_date'] ?? Carbon::today();
        $data['customer_user_id'] = $customerUserId;
        $data['status'] = 'Open';

        $claim = Claim::create($data);

        return $this->created($claim->load(['warranty.brand', 'serviceCenter']), 'Claim created successfully');
    }

    public function track(string $claimNumber): JsonResponse
    {
        $claim = Claim::with([
            'warranty.brand',
            'warranty.category',
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
            'warranty' => $claim->warranty,
            'service_center' => $claim->serviceCenter,
            'work_order' => $claim->workOrder,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $claim = Claim::with(['warranty.brand', 'warranty.category', 'serviceCenter', 'creator', 'workOrder'])->find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        return $this->success($claim);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        $data = $request->validate([
            'service_center_id' => 'sometimes|nullable|exists:wms_service_centers,id',
            'problem_description' => 'sometimes|string',
            'customer_firstname' => 'sometimes|string',
            'customer_lastname' => 'sometimes|string',
            'customer_email' => 'sometimes|email',
            'customer_phone' => 'sometimes|string',
            'customer_city' => 'sometimes|string',
            'customer_address' => 'sometimes|string',
            'claim_date' => 'sometimes|date',
            'status' => 'sometimes|in:Open,Closed,Converted',
        ]);

        $oldData = $claim->toArray();
        $claim->update($data);

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'Claim',
            $claim->claim_number,
            $claim->id,
            ['old' => $oldData, 'new' => $claim->toArray()]
        );

        return $this->success($claim->load(['warranty.brand', 'serviceCenter']), 'Claim updated successfully.');
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

    public function convertToWorkOrder(ConvertToWorkOrderRequest $request, int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        if ($claim->status !== 'Open') {
            return $this->error('Only open claims can be converted to work orders.');
        }

        if ($claim->workOrder) {
            return $this->error('Claim already has a work order.');
        }

        $data = $request->validated();

        return DB::transaction(function () use ($claim, $data, $request) {
            $warranty = $claim->warranty;
            $counter = WorkOrder::whereHas('claim', function ($query) use ($warranty) {
                $query->where('warranty_id', $warranty->id);
            })->count() + 1;

            $feedbackPreference = $data['feedback_preference'] ?? false;
            $feedbackToken = $feedbackPreference ? WorkOrder::generateFeedbackToken() : null;

            $workOrder = WorkOrder::create([
                'wo_number' => WorkOrder::generateWoNumber(),
                'claim_id' => $claim->id,
                'service_center_id' => $data['service_center_id'] ?? null,
                'engineer_id' => $data['engineer_id'] ?? null,
                'feedback_preference' => $feedbackPreference,
                'feedback_token' => $feedbackToken,
                'counter' => $counter,
                'wo_assigned_date' => now(),
                'additional_comment' => $data['additional_comment'] ?? null,
                'status' => 'Progress',
                'created_by' => $request->user()->id,
                'assigned_by' => $request->user()->id,
            ]);

            $claim->update([
                'status' => 'Converted',
                'service_center_id' => $data['service_center_id'] ?? $claim->service_center_id,
            ]);

            ActivityLog::log(
                $request->user()->id,
                'created',
                'WorkOrder',
                $workOrder->wo_number,
                $workOrder->id,
                ['claim_id' => $claim->id, 'claim_number' => $claim->claim_number]
            );

            WorkOrderCreated::dispatch($workOrder->load(['claim.warranty', 'claim', 'serviceCenter']));

            return $this->success($workOrder->load(['claim.warranty.brand', 'serviceCenter']), 'Work order created successfully.', 201);
        });
    }

    public function close(int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        $claim->update(['status' => 'Closed']);

        ActivityLog::log(
            request()->user()->id,
            'updated',
            'Claim',
            $claim->claim_number,
            $claim->id,
            ['action' => 'closed']
        );

        return $this->success($claim, 'Claim closed successfully.');
    }

    public function workOrder(int $id): JsonResponse
    {
        $claim = Claim::find($id);

        if (! $claim) {
            return $this->notFound('Claim not found.');
        }

        $workOrder = $claim->workOrder;

        if (! $workOrder) {
            return $this->notFound('Work order not found for this claim.');
        }

        return $this->success($workOrder->load(['serviceCenter', 'claim.warranty.brand']));
    }

    public function myClaims(Request $request): JsonResponse
    {
        $user = $request->user();

        $claims = Claim::with(['warranty.brand', 'serviceCenter', 'workOrder'])
            ->where('customer_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->limit ?? 15);

        return $this->success($claims);
    }
}
