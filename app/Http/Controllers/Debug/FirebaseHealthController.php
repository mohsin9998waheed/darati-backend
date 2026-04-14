<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class FirebaseHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $key = (string) request()->query('key', '');
        $expected = (string) env('DEBUG_HEALTH_KEY', '');
        if (! app()->hasDebugModeEnabled() && ($expected === '' || ! hash_equals($expected, $key))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $projectId = (string) config('services.fcm.project_id', '');
        $raw = (string) config('services.fcm.service_account_json', '');
        $decoded = $this->decodeServiceAccount($raw);

        $requiredKeys = [
            'type',
            'project_id',
            'private_key',
            'client_email',
            'token_uri',
        ];
        $missingKeys = [];
        if (is_array($decoded)) {
            foreach ($requiredKeys as $k) {
                if (! array_key_exists($k, $decoded) || empty($decoded[$k])) {
                    $missingKeys[] = $k;
                }
            }
        }

        $opensslOk = false;
        if (is_array($decoded) && isset($decoded['private_key'])) {
            $pkey = @openssl_pkey_get_private((string) $decoded['private_key']);
            $opensslOk = $pkey !== false;
        }

        $tokenExchange = [
            'attempted' => false,
            'ok' => false,
            'status' => null,
            'error' => null,
        ];

        if ($projectId !== '' && is_array($decoded) && empty($missingKeys) && $opensslOk) {
            $tokenExchange = $this->tryTokenExchange($decoded);
        }

        Artisan::call('schedule:list');
        $scheduleList = trim(Artisan::output());

        $checks = [
            'project_id_present' => $projectId !== '',
            'project_id' => $projectId,
            'service_account_json_present' => $raw !== '',
            'service_account_json_valid' => is_array($decoded),
            'service_account_missing_keys' => $missingKeys,
            'private_key_parses' => $opensslOk,
            'token_exchange' => $tokenExchange,
            'scheduler' => [
                'schedule_list_has_notify_command' => str_contains($scheduleList, 'notify:continue-listening'),
                'schedule_list_excerpt' => mb_substr($scheduleList, 0, 2000),
            ],
        ];

        $healthy = $checks['project_id_present']
            && $checks['service_account_json_valid']
            && empty($checks['service_account_missing_keys'])
            && $checks['private_key_parses']
            && ($checks['token_exchange']['ok'] ?? false);

        return response()->json([
            'ok' => $healthy,
            'checks' => $checks,
            'now_utc' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ], $healthy ? 200 : 500);
    }

    private function decodeServiceAccount(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Support accidentally wrapped quotes in env values.
        if ((str_starts_with($raw, "'") && str_ends_with($raw, "'")) ||
            (str_starts_with($raw, '"') && str_ends_with($raw, '"'))) {
            $raw = substr($raw, 1, -1);
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function tryTokenExchange(array $sa): array
    {
        try {
            $now = time();
            $audience = 'https://oauth2.googleapis.com/token';
            $scope = 'https://www.googleapis.com/auth/firebase.messaging';

            $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64url(json_encode([
                'iss' => $sa['client_email'],
                'sub' => $sa['client_email'],
                'aud' => $audience,
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => $scope,
            ]));
            $sigInput = "{$header}.{$claims}";

            $privateKey = openssl_pkey_get_private((string) $sa['private_key']);
            if (! $privateKey) {
                return ['attempted' => true, 'ok' => false, 'status' => null, 'error' => 'invalid_private_key'];
            }

            $signature = '';
            openssl_sign($sigInput, $signature, $privateKey, 'SHA256');
            $jwt = $sigInput . '.' . $this->base64url($signature);

            $res = Http::asForm()->timeout(12)->post($audience, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return [
                'attempted' => true,
                'ok' => $res->successful() && ! empty($res->json('access_token')),
                'status' => $res->status(),
                'error' => $res->successful() ? null : $res->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'attempted' => true,
                'ok' => false,
                'status' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

