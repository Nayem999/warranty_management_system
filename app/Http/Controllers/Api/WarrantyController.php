<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warranty\StoreWarrantyRequest;
use App\Http\Requests\Warranty\UpdateWarrantyRequest;
use App\Models\ActivityLog;
use App\Models\Warranty;
use App\Traits\ApiResponse;
use App\Traits\UserAccessFilter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarrantyController extends Controller
{
    use ApiResponse, UserAccessFilter;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Warranty::query()->with(['brand', 'category', 'subCategory', 'creator']);

        $this->applyBrandFilter($query, $user);

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->has('is_void')) {
            $query->where('is_void', $request->is_void);
        }

        if ($request->has('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'expired') {
                $query->expired();
            } elseif ($status === 'void') {
                $query->void();
            }
        }

        if ($request->has('date_from')) {
            $query->where('start_date', '>=', Carbon::parse($request->date_from));
        }

        if ($request->has('date_to')) {
            $query->where('end_date', '<=', Carbon::parse($request->date_to));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('product_serial', 'like', "%{$request->search}%")
                    ->orWhere('product_name', 'like', "%{$request->search}%");
            });
        }

        $warranties = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($warranties);
    }

    public function store(StoreWarrantyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $warranty = Warranty::create($data);

        ActivityLog::log(
            $request->user()->id,
            'created',
            'Warranty',
            $warranty->product_serial,
            $warranty->id
        );

        return $this->created($warranty->load(['brand', 'category', 'subCategory']), 'Warranty created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $user = request()->user();
        $warranty = Warranty::with(['brand', 'category', 'subCategory', 'creator'])
            ->when(! $user->is_admin, fn ($q) => $q->whereIn('brand_id', $this->getAccessibleBrandIds($user)))
            ->find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        return $this->success($warranty);
    }

    public function update(UpdateWarrantyRequest $request, int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        $oldData = $warranty->toArray();
        $data = $request->validated();

        $warranty->update($data);

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'Warranty',
            $warranty->product_serial,
            $warranty->id,
            ['old' => $oldData, 'new' => $warranty->toArray()]
        );

        return $this->success($warranty->load(['brand', 'category', 'subCategory']), 'Warranty updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        if ($warranty->claims()->count() > 0) {
            return $this->error('Cannot delete warranty with associated claims.');
        }

        ActivityLog::log(
            request()->user()->id,
            'deleted',
            'Warranty',
            $warranty->product_serial,
            $warranty->id
        );

        $warranty->delete();

        return $this->deleted('Warranty deleted successfully.');
    }

    public function checkSerial(string $serial): JsonResponse
    {
        $warranty = Warranty::with(['brand', 'category', 'subCategory', 'claims'])->where('product_serial', $serial)->first();

        if (! $warranty) {
            return $this->error('Warranty not found for this serial number.', 404);
        }

        return $this->success([
            'warranty' => $warranty,
            'warranty_status' => $warranty->warranty_status,
            'claim_count' => $warranty->claims()->count(),
        ]);
    }

    public function void(Request $request, int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        $data = $request->validate([
            'void_reason' => 'required|string',
        ]);

        $warranty->update([
            'is_void' => 'YES',
            'void_reason' => $data['void_reason'],
        ]);

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'Warranty',
            $warranty->product_serial,
            $warranty->id,
            ['action' => 'voided', 'reason' => $data['void_reason']]
        );

        return $this->success($warranty, 'Warranty voided successfully.');
    }

    public function unvoid(int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        $warranty->update([
            'is_void' => 'NO',
            'void_reason' => null,
        ]);

        ActivityLog::log(
            request()->user()->id,
            'updated',
            'Warranty',
            $warranty->product_serial,
            $warranty->id,
            ['action' => 'unvoided']
        );

        return $this->success($warranty, 'Warranty unvoided successfully.');
    }

    public function claims(int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        $claims = $warranty->claims()->with(['serviceCenter', 'creator'])->orderBy('id', 'desc')->paginate(15);

        return $this->success($claims);
    }

    public function expiringSoon(Request $request): JsonResponse
    {
        $days = $request->days ?? 30;

        $warranties = Warranty::with(['brand', 'category', 'subCategory'])
            ->expiringSoon($days)
            ->orderBy('end_date', 'asc')
            ->paginate($request->limit ?? 15);

        return $this->success($warranties);
    }
}
