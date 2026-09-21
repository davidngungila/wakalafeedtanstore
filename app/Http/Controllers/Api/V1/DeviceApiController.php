<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DevicePhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceApiController extends Controller
{
    /**
     * First contact from the mobile app: pair the phone with the device the
     * administrator registered. Confirms identity and updates device
     * information reported by the handset.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_uid' => ['required', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:120'],
            'android_version' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);

        /** @var Device $device */
        $device = $request->attributes->get('device');

        // Advanced: if a different phone is already online for this device, reject new pairing
        $existingOnline = $device->phones()->where('device_uid', '!=', $validated['device_uid'])->get()->filter(fn ($p) => $p->isOnline());
        if ($existingOnline->isNotEmpty()) {
            return response()->json([
                'message' => 'A phone is already connected to this device. Disconnect the existing phone before pairing a new one.',
                'connected_phones' => $existingOnline->map(fn ($p) => ['device_uid' => $p->device_uid, 'model' => $p->model, 'last_seen_at' => $p->last_seen_at?->toIso8601String()])->values(),
            ], 409);
        }

        $device->update([
            'device_uid' => $validated['device_uid'],
            'model' => $validated['model'] ?? $device->model,
            'android_version' => $validated['android_version'] ?? $device->android_version,
            'app_version' => $validated['app_version'] ?? $device->app_version,
            'last_ip' => $request->ip(),
        ]);

        DevicePhone::markSeen($device, $validated['device_uid'], [
            'model' => $validated['model'] ?? null,
            'android_version' => $validated['android_version'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'device' => [
                'id' => $device->id,
                'device_code' => $device->device_code,
                'name' => $device->name,
                'status' => $device->status,
                'agent' => $device->agent?->name,
                'network' => $device->network?->code,
                'networks' => $device->networkCodes(),
                'branch' => $device->branch,
            ],
            'message' => $device->status === 'active' ? 'Device linked.' : 'Device linked. Awaiting approval or activation.',
        ]);
    }

    /**
     * Profile of the connected device, used by the app to show status.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        return response()->json([
            'device' => [
                'id' => $device->id,
                'device_code' => $device->device_code,
                'name' => $device->name,
                'status' => $device->status,
                'agent' => $device->agent?->name,
                'network' => $device->network?->code,
                'networks' => $device->networkCodes(),
                'branch' => $device->branch,
                'last_sms_at' => $device->last_sms_at?->toIso8601String(),
                'last_sync_at' => $device->last_sync_at?->toIso8601String(),
                'last_heartbeat_at' => $device->last_heartbeat_at?->toIso8601String(),
            ],
        ]);
    }
}
