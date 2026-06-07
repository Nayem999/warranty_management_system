<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MicrosoftOAuthToken;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftApiController extends Controller
{
    use ApiResponse;

    protected function tenantId(): string
    {
        return Setting::get('outlook_tenant_id', env('OUTLOOK_TENANT_ID', 'common'));
    }

    protected function authUrl(): string
    {
        return 'https://login.microsoftonline.com/' . $this->tenantId() . '/oauth2/v2.0/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://login.microsoftonline.com/' . $this->tenantId() . '/oauth2/v2.0/token';
    }

    public function redirect(): RedirectResponse
    {
        $clientId = Setting::get('outlook_client_id', env('OUTLOOK_CLIENT_ID'));
        $redirectUri = Setting::get('outlook_redirect_uri', env('OUTLOOK_REDIRECT_URI'));

        $url = $this->authUrl() . '?' . http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => 'https://graph.microsoft.com/.default',
            'prompt' => 'consent',
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request): JsonResponse|RedirectResponse
    {
        $error = $request->query('error');
        if ($error) {
            Log::error('Outlook OAuth error', [
                'error' => $error,
                'description' => $request->query('error_description'),
            ]);

            return $this->error('Authorization failed: ' . $error, 400);
        }

        $code = $request->query('code');
        if (!$code) {
            return $this->error('Authorization code not provided', 400);
        }

        $clientId = Setting::get('outlook_client_id', env('OUTLOOK_CLIENT_ID'));
        $clientSecret = Setting::get('outlook_client_secret', env('OUTLOOK_CLIENT_SECRET'));
        $redirectUri = Setting::get('outlook_redirect_uri', env('OUTLOOK_REDIRECT_URI'));

        try {
            $response = Http::asForm()->post($this->tokenUrl(), [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
            ]);

            if (!$response->successful()) {
                Log::error('Outlook token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->error('Token exchange failed', 400);
            }

            $data = $response->json();

            MicrosoftOAuthToken::truncate();

            MicrosoftOAuthToken::create([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => now()->addSeconds($data['expires_in']),
            ]);

            Setting::set('outlook_authorized', '1', 'outlook');

            return $this->success(null, 'Outlook SMTP authorized successfully.');
        } catch (\Exception $e) {
            Log::error('Outlook token exchange exception', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('Token exchange failed: ' . $e->getMessage(), 500);
        }
    }

    public function status(): JsonResponse
    {
        $token = MicrosoftOAuthToken::latest('id')->first();

        if (!$token) {
            return $this->success([
                'authorized' => false,
                'message' => 'Not authorized. No tokens found.',
            ]);
        }

        return $this->success([
            'authorized' => !$token->isExpired(),
            'expires_at' => $token->expires_at?->toIso8601String(),
            'is_expired' => $token->isExpired(),
        ]);
    }

    public function revoke(): JsonResponse
    {
        MicrosoftOAuthToken::truncate();
        Setting::set('outlook_authorized', '0', 'outlook');

        return $this->success(null, 'Outlook authorization revoked.');
    }
}
