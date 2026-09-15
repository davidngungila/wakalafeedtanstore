<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\SmsProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsApiController extends Controller
{
    public function __construct(private readonly SmsProcessor $processor) {}

    /**
     * Push captured SMS messages (single or batched for offline sync).
     * Only active devices may submit; each message is processed through the
     * parser and turned into a transaction.
     */
    public function ingest(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        if ($device->status !== 'active') {
            return response()->json([
                'message' => 'Device is not active. Approved devices only.',
                'device_status' => $device->status,
            ], 403);
        }

        $validated = $request->validate([
            'sms' => ['required', 'array', 'max:500'],
            'sms.*.sender' => ['required', 'string', 'max:30'],
            'sms.*.message' => ['required', 'string'],
            'sms.*.received_at' => ['nullable', 'date'],
        ]);

        $results = $this->processor->ingest($device, $validated['sms']);

        $processed = count(array_filter($results, fn (array $r) => ($r['ok'] ?? false) === true));
        $duplicates = count(array_filter($results, fn (array $r) => ($r['duplicate'] ?? false) === true));
        $ignored = count(array_filter($results, fn (array $r) => ($r['ignored_sender'] ?? false) === true));
        $failed = count($results) - $processed - $duplicates - $ignored;

        return response()->json([
            'summary' => [
                'received' => count($results),
                'processed' => $processed,
                'duplicates' => $duplicates,
                'ignored_senders' => $ignored,
                'failed' => $failed,
            ],
            'results' => $results,
        ]);
    }

    /**
     * Capture contract the Flutter app uses to decide which SMS to forward.
     * `capture_all` tells the phone to forward every SMS regardless of sender;
     * `senders` maps known mobile-money keywords to network codes for the
     * device, scoped to the networks this device is assigned to.
     */
    public function senders(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $networkCodes = $device->networkCodes();

        $watchlist = collect(config('sms.senders', []))
            ->filter(fn (string $code) => in_array($code, $networkCodes, true))
            ->map(fn (string $code, string $keyword) => ['keyword' => $keyword, 'network' => $code])
            ->values()
            ->all();

        return response()->json([
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'status' => $device->status,
                'network' => $device->network?->code,
                'networks' => $networkCodes,
            ],
            'capture_all' => true,
            'senders' => $watchlist,
            'ingest' => [
                'max_batch' => 500,
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Connectivity check from the phone. Updates heartbeat (and device
     * reported metadata) so the admin screen can show online/offline state.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validated = $request->validate([
            'device_uid' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:120'],
            'android_version' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);

        $device->forceFill([
            'last_heartbeat_at' => now(),
            'last_sync_at' => now(),
            'last_ip' => $request->ip(),
            'status' => $device->status === 'active' ? 'active' : $device->status,
        ])->save();

        if ($validated['device_uid'] ?? null) {
            $device->forceFill(['device_uid' => $validated['device_uid']])->save();
        }
        if ($validated['model'] ?? null) {
            $device->forceFill(['model' => $validated['model']])->save();
        }
        if ($validated['android_version'] ?? null) {
            $device->forceFill(['android_version' => $validated['android_version']])->save();
        }
        if ($validated['app_version'] ?? null) {
            $device->forceFill(['app_version' => $validated['app_version']])->save();
        }

        return response()->json([
            'ok' => true,
            'status' => $device->status,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
