<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerAuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! $customer->password || ! Hash::check($request->password, $customer->password)) {
            return $this->error('Invalid email or password.');
        }

        $token = $customer->createToken('customer-token')->plainTextToken;
        $expiresAt = now()->addMinutes(config('sanctum.expiration', 4320));

        return $this->success([
            'customer' => $customer,
            'token' => $token,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'expires_in' => config('sanctum.expiration', 4320) * 60,
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout successful.');
    }

    public function claims(Request $request): JsonResponse
    {
        $customer = $request->user();

        $query = $customer->claims()
            ->with([
                'product.brand',
                'product.category',
                'serviceCenter',
                'workOrder',
            ])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->where('claim_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->where('claim_date', '<=', $request->date_to))
            ->orderBy('created_at', 'desc');

        $claims = $query->paginate($request->limit ?? 15);

        return $this->success($claims);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $customer = $request->user();

        $totalClaims = $customer->claims()->count();
        $openClaims = $customer->claims()->where('status', 'Open')->count();
        $inProgressClaims = $customer->claims()->where('status', 'In Progress')->count();
        $closedClaims = $customer->claims()->where('status','like', 'Closed%')->count();
        $deliveredClaims = $customer->claims()->where('status', 'Delivered')->count();
        // $closedClaims = $customer->claims()->closed()->count();

        $recentClaims = $customer->claims()
            ->with(['product.brand', 'serviceCenter', 'workOrder'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $this->success([
            'total_claims' => $totalClaims,
            'open_claims' => $openClaims,
            'in_progress_claims' => $inProgressClaims,
            'closed_claims' => $closedClaims,
            'deliveredClaims' => $deliveredClaims,
            'recent_claims' => $recentClaims,
        ]);
    }
}
