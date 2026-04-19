<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse, FileUpload;

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $loginField = $credentials['login'];
        $password = $credentials['password'];

        $user = User::where('email', $loginField)
            ->orWhere('phone', $loginField)
            ->first();

        if (! $user) {
            return $this->error('Invalid credentials.', 401);
        }

        if ($user->status === 'inactive') {
            return $this->error('Your account is inactive.', 403);
        }

        if ($user->disable_login) {
            return $this->error('Login is disabled for this user.', 403);
        }

        if (! Hash::check($password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        $user->last_online = now();
        $user->save();

        $token = $user->createToken('auth-token')->plainTextToken;
        $expiresAt = now()->addMinutes(config('sanctum.expiration', 14400));

        return $this->success([
            'token' => $token,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'expires_in' => config('sanctum.expiration', 4320) * 60,
            'user' => $user,
            'permissions' => $user->permissions,
        ], 'Login successful.');
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
        ], [
            'login.required' => 'Please provide email or phone number.',
        ]);

        $login = $request->login;

        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (! $user) {
            return $this->error('User not found with this email or phone.', 404);
        }

        if ($user->status === 'inactive') {
            return $this->error('Your account is inactive.', 403);
        }

        if ($user->disable_login) {
            return $this->error('Login is disabled for this user.', 403);
        }

        $otp = env('SMS_STATUS') ? str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT) : 123456;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $otp,
                'created_at' => now(),
            ]
        );

        return $this->success([
            // 'otp' => $otp,
            'user_id' => $user->id,
            'login' => $login,
        ], 'OTP sent successfully. Use this OTP to login.');
    }

    public function loginWithOtp(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $login = $request->login;
        $otp = $request->otp;

        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (! $user) {
            return $this->error('User not found.', 404);
        }

        if ($user->status === 'inactive') {
            return $this->error('Your account is inactive.', 403);
        }

        if ($user->disable_login) {
            return $this->error('Login is disabled for this user.', 403);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (! $record || $record->token !== $otp) {
            return $this->error('Invalid or expired OTP.', 400);
        }

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $user->last_online = now();
        $user->save();

        $token = $user->createToken('auth-token')->plainTextToken;
        $expiresAt = now()->addMinutes(config('sanctum.expiration', 14400));

        return $this->success([
            'token' => $token,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'expires_in' => config('sanctum.expiration', 4320) * 60,
            'user' => $user,
            'permissions' => $user->permissions,
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout successful.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        $user = User::where('email', $email)->first();

        if (! $user) {
            return $this->error('We could not find a user with that email address.', 404);
        }

        $otp = env('SMS_STATUS') ? str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT) : 123456;

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $otp,
                'created_at' => now(),
            ]
        );

        return $this->success([
            // 'otp' => $otp,
            'email' => $email,
        ], 'Password reset OTP has been sent to your email.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $record = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (! $record || $record->token !== $data['otp']) {
            return $this->error('Invalid or expired OTP.', 400);
        }

        $user = User::where('email', $data['email'])->first();
        $user->password = Hash::make($data['password']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        $user->tokens()->delete();

        return $this->success(null, 'Password reset successfully.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated()['current_password'], $user->password)) {
            return $this->error('Current password is incorrect.', 400);
        }

        $user->password = Hash::make($request->validated()['password']);
        $user->save();

        return $this->success(null, 'Password changed successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role']);

        return $this->success([
            'user' => $user,
            'permissions' => $user->permissions,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        // dd($request->all());
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'language' => 'nullable|string|max:10',
            'image' => 'nullable',
        ]);

        if ($request->hasFile('image')) {
            if ($user->image) {
                $this->deleteFile($user->image);
            }
            $validated['image'] = $this->uploadFile($request->file('image'), 'users');
        } elseif (! empty($validated['image']) && is_string($validated['image'])) {
            if ($user->image !== $validated['image']) {
                if ($user->image) {
                    $this->deleteFile($user->image);
                }
                $validated['image'] = $this->handleImageUpload($validated['image'], 'users');
            }
        }

        $user->update($validated);

        return $this->success($user, 'Profile updated successfully.');
    }

    protected function handleImageUpload(string $base64Data, string $folder): string
    {
        if (empty($base64Data)) {
            return '';
        }

        if (str_starts_with($base64Data, 'data:')) {
            $ext = 'jpg';
            if (preg_match('/data:image\/(\w+);/', $base64Data, $matches)) {
                $ext = $matches[1];
            }

            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
            $base64Data = base64_decode($base64Data);

            if ($base64Data === false) {
                return '';
            }

            $filename = Str::uuid().'.'.$ext;
            $path = "uploads/{$folder}/{$filename}";
            Storage::disk('public')->put($path, $base64Data);

            return $path;
        }

        return $base64Data;
    }

}
