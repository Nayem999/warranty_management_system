<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%")
                    ->orWhere('contact_person', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        $customers = $query->orderBy('customer_name')->paginate($request->limit ?? 15);

        return $this->success($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();

        $customer = Customer::create($data);

        return $this->created($customer, 'Customer created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['claims'])->find($id);

        if (! $customer) {
            return $this->notFound('Customer not found.');
        }

        return $this->success($customer);
    }

    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return $this->notFound('Customer not found.');
        }

        $data = $request->validated();
        $customer->update($data);

        return $this->success($customer, 'Customer updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return $this->notFound('Customer not found.');
        }

        if ($customer->claims()->count() > 0) {
            return $this->error('Cannot delete customer with associated claims.');
        }

        $customer->delete();

        return $this->deleted('Customer deleted successfully.');
    }
}