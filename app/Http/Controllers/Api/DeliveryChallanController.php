<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryChallan\StoreDeliveryChallanRequest;
use App\Http\Resources\DeliveryChallanResource;
use App\Models\Claim;
use App\Models\DeliveryChallan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $data = $request->validated();

        $data['delivery_number'] = DeliveryChallan::generateDeliveryNumber();

        $claimIds = $data['claim_ids'];
        unset($data['claim_ids']);

        if (! isset($data['customer_id'])) {
            $firstClaim = Claim::with('customer')->find($claimIds[0]);
            $data['customer_id'] = $firstClaim->customer_id;
            $data['service_center_id'] = $firstClaim->service_center_id;
        }

        $challan = DeliveryChallan::create($data);

        foreach ($claimIds as $claimId) {
            Claim::where('id', $claimId)->update([
                'courier_out_id' => $data['courier_out_id'],
                'courier_slip_outward' => $data['courier_slip_outward'],
                'delivered_date_time' => $data['delivered_date_time'],
                'delivered_remarks' => $data['delivered_remarks'],
                'status' => 'Delivered',
            ]);
        }

        return $this->created(
            $challan->load(['customer', 'courierOut', 'claims.product']),
            'Delivery challan created successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $challan = DeliveryChallan::with(['customer', 'courierOut', 'claims.product'])->find($id);

        if (! $challan) {
            return $this->notFound('Delivery challan not found.');
        }

        return $this->success($challan);
    }
}
