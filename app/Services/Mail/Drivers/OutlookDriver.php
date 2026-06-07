<?php

namespace App\Services\Mail\Drivers;

use App\Models\MicrosoftOAuthToken;
use App\Models\Setting;
use App\Services\Mail\Contracts\EmailDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutlookDriver implements EmailDriverInterface
{
    protected const GRAPH_URL = 'https://graph.microsoft.com/v1.0';

    protected function tokenUrl(): string
    {
        $tenant = Setting::get('outlook_tenant_id', env('OUTLOOK_TENANT_ID', 'common'));

        return 'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/token';
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $token = $this->getValidToken();

        if (!$token) {
            throw new \RuntimeException(
                'Outlook is not authorized. No access token found. ' .
                'Visit ' . url('/microsoft_api/redirect') . ' to authorize, or set email_driver to smtp.'
            );
        }

        $payload = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $body,
                ],
                'toRecipients' => [
                    ['emailAddress' => ['address' => $to]],
                ],
            ],
            'saveToSentItems' => true,
        ];

        $response = Http::withToken($token->access_token)
            ->post(self::GRAPH_URL . '/me/sendMail', $payload);

        if ($response->successful()) {
            return true;
        }

        throw new \RuntimeException(sprintf(
            'Outlook Graph API returned status %d: %s',
            $response->status(),
            $response->body()
        ));
    }

    protected function getValidToken(): ?MicrosoftOAuthToken
    {
        $token = MicrosoftOAuthToken::latest('id')->first();

        if (!$token) {
            return null;
        }

        if ($token->isExpired()) {
            return $this->refreshToken($token);
        }

        return $token;
    }

    protected function refreshToken(MicrosoftOAuthToken $token): ?MicrosoftOAuthToken
    {
        $clientId = Setting::get('outlook_client_id', env('OUTLOOK_CLIENT_ID'));
        $clientSecret = Setting::get('outlook_client_secret', env('OUTLOOK_CLIENT_SECRET'));

        $response = Http::asForm()->post($this->tokenUrl(), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
        ]);

        if (!$response->successful()) {
            Log::error('Outlook token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $data = $response->json();

        $token->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $token->refresh_token,
            'expires_at' => now()->addSeconds($data['expires_in']),
        ]);

        return $token->fresh();
    }
}
