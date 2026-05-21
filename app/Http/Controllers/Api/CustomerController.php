<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Mail\CustomerWelcomeEmail;
use App\Models\Customer;
use App\Traits\ApiResponse;
use App\Traits\EmailHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    use ApiResponse, EmailHelper;

    public function index(Request $request): JsonResponse
    {
        $query = Customer::with('city');

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%")
                    ->orWhere('contact_person', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('city')) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->city}%");
            });
        }

        $customers = $query->orderBy('customer_name')->paginate($request->limit ?? 15);

        return $this->success($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $password = Str::random(12);
            $data['password'] = $password;

            $customer = Customer::create($data);

            $this->sendEmail(
                new CustomerWelcomeEmail($customer, $password),
                $customer->email,
                'Welcome to ' . config('app.name') . ' - Your Account Details'
            );

            DB::commit();
            $customer = Customer::with(["city"])->find($customer->id);

            return $this->created($customer, 'Customer created successfully. Password: ' . $password);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
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