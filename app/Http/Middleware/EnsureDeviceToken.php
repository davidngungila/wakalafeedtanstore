<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceToken
{
    /**
     * Authenticate a connected phone using its hashed API token.
     * The route is still allowed to check the resulting device status.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Missing device API token.'], 401);
        }

        $device = Device::query()
            ->where('api_token_hash', hash('sha256', $token))
            ->first();

        if (! $device) {
            return response()->json(['message' => 'Unknown device. This device is not authorized.'], 401);
        }

        if (in_array($device->status, ['blocked', 'revoked'], true)) {
            return response()->json(['message' => 'Device is not allowed to access the system.'], 403);
        }

        $request->attributes->set('device', $device);
        $request->merge(['__device' => $device]);

        return $next($request);
    }
}
