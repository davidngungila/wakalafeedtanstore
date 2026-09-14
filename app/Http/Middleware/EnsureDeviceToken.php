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
     * Authenticate a connected phone using its device code and authorization token.
     * Both headers are required on every request:
     *   - Authorization: Bearer <long token>
     *   - X-Device-Code: <short code>
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $token = $request->bearerToken();
        $deviceCode = $request->header('X-Device-Code');

        if (! $token || ! $deviceCode) {
            return response()->json(['message' => 'Missing credentials.'], 401);
        }

        $device = Device::query()
            ->where('device_code', strtoupper(trim($deviceCode)))
            ->first();

        if (! $device) {
            return response()->json(['message' => 'Unknown device. This device is not authorized.'], 401);
        }

        if (! $device->hasAuthorizationToken($token)) {
            return response()->json(['message' => 'Invalid authorization token.'], 401);
        }

        if (in_array($device->status, ['blocked', 'revoked'], true)) {
            return response()->json(['message' => 'Device is not allowed to access the system.'], 403);
        }

        $request->attributes->set('device', $device);
        $request->merge(['__device' => $device]);

        return $next($request);
    }
}
