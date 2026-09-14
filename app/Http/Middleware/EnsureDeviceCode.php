<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceCode
{
    /**
     * Authenticate a connected phone using its device code.
     * The device code header is required on every request:
     *   - X-Device-Code: <short code>
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $deviceCode = $request->header('X-Device-Code');

        if (! $deviceCode) {
            return response()->json(['message' => 'Missing credentials.'], 401);
        }

        $device = Device::query()
            ->where('device_code', strtoupper(trim($deviceCode)))
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
