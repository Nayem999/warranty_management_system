<?php

namespace App\Http\Controllers\Api;

use App\Events\DeliveryChallanCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryChallan\StoreDeliveryChallanRequest;
use App\Http\Resources\DeliveryChallanResource;
use App\Models\Claim;
use App\Models\DeliveryChallan;
use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryChallanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = DeliveryChallan::with(['customer', 'courierOut']);

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('delivery_number', 'like', "%{$request->search}%")
                    ->orWhere('courier_slip_outward', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $challans = $query->orderBy('created_at', 'desc')->paginate($request->limit ?? 15);

        return $this->success($challans);
    }

    public function store(StoreDeliveryChallanRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $claimIds = $data['claim_ids'];

            if (empty($claimIds)) {
                return $this->error('Claim ids are required.');
            }

            $claims = Claim::whereIn('id', $claimIds)->get();

            if ($claims->count() !== count($claimIds)) {
                return $this->notFound('Some claims were not found.');
            }

            // Ensure same customer
            $customerIds = $claims->pluck('customer_id')->unique();

            if ($customerIds->count() > 1) {
                return $this->error('All claims must belong to the same customer.');
            }

            // Ensure same service center
            $serviceCenterIds = $claims->pluck('service_center_id')->unique();

            if ($serviceCenterIds->count() > 1) {
                return $this->error('All claims must belong to the same service center.');
            }

            $firstClaim = $claims->first();

            $data['delivery_number'] = DeliveryChallan::generateDeliveryNumber();
            $data['customer_id'] = $firstClaim->customer_id;
            $data['delivered_date_time'] = $data['delivered_date_time'] ?? Carbon::now()->format('Y-m-d H:i:s');
            $data['service_center_id'] = $firstClaim->service_center_id;

            $challan = DeliveryChallan::create($data);

            foreach ($claims as $claim) {

                $claim->update([
                    'is_delivered'    => 1,
                    'delivery_id'     => $challan->id,
                    // 'status'          => "Delivered",
                    // 'status_comment'  => $challan->delivered_remarks,
                    'wo_delivery_date'  =>  Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                ActivityLog::log(
                    $request->user()->id,
                    'updated',
                    'Claim',
                    $claim->claim_number,
                    $claim->id,
                    ['status' => $claim->status, 'comment' => $claim->status_comment]
                );
            }

            DB::commit();

            DeliveryChallanCreated::dispatch($challan);

            $challanResult = DeliveryChallan::with(['customer', 'courierOut'])->find($challan->id);

            $challanResult->setRelation('claims', $challanResult->claims()->get());

            return $this->created(
                $challanResult,
                'Delivery challan created successfully.'
            );
        } catch (\Throwable $e) {

            DB::rollBack();

            return $this->error($e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $challan = DeliveryChallan::with(['customer', 'courierOut'])->find($id);

        if (! $challan) {
            return $this->notFound('Delivery challan not found.');
        }

        $challan->setRelation('claims', $challan->claims()->get());

        return $this->success($challan);
    }
}
