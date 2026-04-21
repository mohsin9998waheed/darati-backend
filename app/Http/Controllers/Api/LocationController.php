<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $ip = $request->ip();

        // Strip local/private IPs — geolocation won't work on them
        if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return response()->json(['ok' => true, 'city' => null]);
        }

        $cacheKey = "geo:{$ip}";
        $geo = Cache::remember($cacheKey, now()->addHours(24), function () use ($ip) {
            try {
                $res = Http::timeout(5)->get("http://ip-api.com/json/{$ip}?fields=status,city,country,countryCode");
                if ($res->ok() && ($res->json('status') === 'success')) {
                    return [
                        'city'    => $res->json('city'),
                        'country' => $res->json('country'),
                    ];
                }
            } catch (\Throwable $e) {
                Log::debug("LocationController: geo lookup failed for {$ip}: " . $e->getMessage());
            }
            return null;
        });

        if ($geo && Auth::check()) {
            Auth::user()->update([
                'city'    => $geo['city'],
                'country' => $geo['country'],
            ]);
        }

        return response()->json(['ok' => true, 'city' => $geo['city'] ?? null]);
    }
}
