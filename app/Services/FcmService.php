<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging V1 HTTP API.
 *
 * Requires in .env / Railway environment variables:
 *   FIREBASE_SERVICE_ACCOUNT_JSON={"type":"service_account","project_id":"...","private_key":"...","client_email":"..."}
 *   FCM_PROJECT_ID=darati
 */
class FcmService
{
    private string $projectId;
    private ?array $serviceAccount;

    public function __construct()
    {
        $this->projectId    = config('services.fcm.project_id', '');
        $raw                = config('services.fcm.service_account_json', '');
        $this->serviceAccount = $raw ? json_decode($raw, true) : null;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public function sendToDevice(string $token, string $title, string $body, array $data = [], ?string $imageUrl = null): bool
    {
        $notification = ['title' => $title, 'body' => $body];
        $androidNotification = ['sound' => 'default'];
        if ($imageUrl) {
            $notification['image'] = $imageUrl;
            $androidNotification['image'] = $imageUrl;
        }

        return $this->sendV1([
            'token' => $token,
            'notification' => $notification,
            'android' => ['priority' => 'high', 'notification' => $androidNotification],
            'data' => $this->stringifyData(array_merge($data, ['click_action' => 'FLUTTER_NOTIFICATION_CLICK'])),
        ]);
    }

    public function sendToDevices(array $tokens, string $title, string $body, array $data = [], ?string $imageUrl = null): bool
    {
        if (empty($tokens)) return true;

        $success = true;
        // FCM V1 only supports single token per message; iterate in parallel where possible.
        foreach ($tokens as $token) {
            if (! $this->sendToDevice($token, $title, $body, $data, $imageUrl)) {
                $success = false;
            }
        }
        return $success;
    }

    // ── FCM V1 internals ──────────────────────────────────────────────────────

    private function sendV1(array $message): bool
    {
        if (! $this->serviceAccount || empty($this->projectId)) {
            Log::warning('FcmService: FIREBASE_SERVICE_ACCOUNT_JSON or FCM_PROJECT_ID not set.');
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (! $accessToken) return false;

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->timeout(15)->post($url, ['message' => $message]);

            if (! $response->successful()) {
                Log::error('FcmService: push failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FcmService: exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtain a short-lived OAuth 2.0 access token using the service account private key.
     * Valid for 1 hour; for production consider caching this in Redis/cache.
     */
    private function getAccessToken(): ?string
    {
        try {
            $sa       = $this->serviceAccount;
            $now      = time();
            $expiry   = $now + 3600;
            $audience = 'https://oauth2.googleapis.com/token';
            $scope    = 'https://www.googleapis.com/auth/firebase.messaging';

            // Build JWT header + claims
            $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64url(json_encode([
                'iss'   => $sa['client_email'],
                'sub'   => $sa['client_email'],
                'aud'   => $audience,
                'iat'   => $now,
                'exp'   => $expiry,
                'scope' => $scope,
            ]));

            $sigInput = "$header.$claims";

            // Sign with private key from service account JSON
            $privateKey = openssl_pkey_get_private($sa['private_key']);
            if (! $privateKey) {
                Log::error('FcmService: could not load private key from service account.');
                return null;
            }

            $signature = '';
            openssl_sign($sigInput, $signature, $privateKey, 'SHA256');
            $jwt = $sigInput . '.' . $this->base64url($signature);

            // Exchange JWT for access token
            $res = Http::asForm()->timeout(15)->post($audience, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (! $res->successful()) {
                Log::error('FcmService: token exchange failed', ['body' => $res->body()]);
                return null;
            }

            return $res->json('access_token');
        } catch (\Throwable $e) {
            Log::error('FcmService: getAccessToken exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** FCM V1 data values must all be strings. */
    private function stringifyData(array $data): array
    {
        return array_map('strval', $data);
    }
}
