<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLine;
use App\Models\Network;
use App\Models\SmsMessage;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Device::with(['agent', 'network', 'networks', 'lines.network']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('network') && $request->input('network') !== 'all') {
            $query->where(function ($q) use ($request) {
                $q->whereHas('networks', fn ($w) => $w->whereKey($request->input('network')))
                    ->orWhere('network_id', $request->input('network'));
            });
        }

        $devices = $query->latest()->get();
        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        $todayStats = SmsMessage::query()
            ->whereDate('server_received_at', today())
            ->selectRaw('device_id, processing_status, COUNT(*) as total')
            ->groupBy('device_id', 'processing_status')
            ->get()
            ->groupBy('device_id')
            ->map(function ($rows) {
                return [
                    'received' => (int) $rows->sum('total'),
                    'processed' => (int) $rows->where('processing_status', 'RECORDED')->sum('total'),
                    'failed' => (int) $rows->where('processing_status', 'FAILED')->sum('total'),
                    'pending' => (int) $rows->whereIn('processing_status', ['RECEIVED', 'PARSED', 'NEEDS_REVIEW', 'DUPLICATE'])->sum('total'),
                ];
            });

        return view('devices.index', [
            'devices' => $devices,
            'networks' => $networks,
            'todayStats' => $todayStats,
            'filters' => $request->only(['status', 'network']),
            'credentialsFlash' => session()->pull('credentials_flash'),
        ]);
    }

    public function show(Request $request, Device $device): View
    {
        $device->load(['agent', 'network', 'networks', 'lines.network', 'phones']);

        $activeTab = $request->input('tab', 'messages');
        $activeTab = in_array($activeTab, ['messages', 'transactions'], true) ? $activeTab : 'messages';

        $sms = SmsMessage::with(['transaction'])
            ->where('device_id', $device->id)
            ->latest('server_received_at')
            ->paginate(50)
            ->withQueryString();

        $transactions = Transaction::with(['network', 'operator'])
            ->whereHas('smsMessages', fn ($q) => $q->where('device_id', $device->id))
            ->latest()
            ->limit(200)
            ->get();

        return view('devices.show', [
            'device' => $device,
            'sms' => $sms,
            'transactions' => $transactions,
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'credentialsFlash' => session()->pull('credentials_flash'),
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Live connection status for every handset paired with this device code.
     * Polled by the device page to drive the online/offline LEDs.
     */
    public function phonesStatus(Request $request, Device $device): JsonResponse
    {
        $phones = $device->phones()
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn ($phone) => [
                'id' => $phone->id,
                'model' => $phone->model,
                'online' => $phone->isOnline(),
                'last_seen_at' => $phone->last_seen_at?->diffForHumans(),
                'last_seen_raw' => $phone->last_seen_at?->toIso8601String(),
            ]);

        return response()->json(['phones' => $phones]);
    }

    /**
     * Step-by-step registration wizard. Starts on the details step, then walks
     * through connecting the phone (QR / device code) and authorizing it. An
     * in-flight registration can be resumed with ?device=<encrypted id>.
     */
    public function register(Request $request): View|RedirectResponse
    {
        abort_unless(is_admin(), 403);

        if (cash_point() === null) {
            return redirect()->route('cash-point.index')
                ->with('error', 'Set up the cash point first before registering devices.');
        }

        $device = null;
        if ($request->filled('device')) {
            try {
                $device = Device::find((int) Crypt::decryptString($request->query('device')));
            } catch (\Throwable) {
                $device = null;
            }
        }

        $phone = $device?->phones()->first();

        return view('devices.register', [
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'resume' => $device ? [
                'id' => $device->id,
                'name' => $device->name,
                'device_code' => $device->device_code,
                'status' => $device->status,
                'connected' => $phone !== null,
                'connect_status_url' => route('devices.connect-status', $device),
                'approve_url' => route('devices.approve', $device),
                'device_page_url' => route('devices.show', $device),
            ] : null,
        ]);
    }

    /**
     * Polled by the registration wizard so it can detect when the phone has
     * paired (the app reports its real model/version details on bootstrap).
     */
    public function connectStatus(Request $request, Device $device): JsonResponse
    {
        $phone = $device->phones()->first();

        return response()->json([
            'connected' => $phone !== null,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'device_code' => $device->device_code,
                'status' => $device->status,
                'model' => $device->model,
                'device_uid' => $device->device_uid,
            ],
            'phone' => $phone ? [
                'device_uid' => $phone->device_uid,
                'model' => $phone->model,
                'android_version' => $phone->android_version,
                'app_version' => $phone->app_version,
                'ip' => $phone->ip,
                'first_seen_at' => $phone->first_seen_at?->toIso8601String(),
                'last_seen_at' => $phone->last_seen_at?->toIso8601String(),
                'online' => $phone->isOnline(),
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'network_id' => ['nullable', 'exists:networks,id'],
            'network_ids' => ['array'],
            'network_ids.*' => ['exists:networks,id'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'sim_number' => ['nullable', 'string', 'max:60'],
            'branch' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'android_version' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);

        $networkIds = $this->resolveNetworkIds($validated);

        if ($networkIds === []) {
            return response()->json(['success' => false, 'message' => 'Select at least one network.'], 422);
        }

        if (cash_point() === null) {
            $message = 'Set up the cash point first before registering devices.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cash-point.index')->with('error', $message);
        }

        $deviceCode = Device::generateDeviceCode();

        $device = Device::create([
            'name' => $validated['name'],
            'agent_id' => cash_point()->id,
            'network_id' => $networkIds[0],
            'device_code' => $deviceCode,
            'status' => 'pending',
            'phone_number' => $validated['phone_number'] ?? null,
            'sim_number' => $validated['sim_number'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'model' => $validated['model'] ?? null,
            'android_version' => $validated['android_version'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
        ]);

        $device->networks()->sync($networkIds);

        $this->recordAudit('Device registered', 'Device', $device->id, [
            'name' => $device->name,
            'network_ids' => $networkIds,
        ]);

        session()->flash('credentials_flash', [
            'device_id' => $device->id,
            'device_code' => $deviceCode,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Device registered. Credentials generated.',
                'device_id' => $device->id,
                'device_code' => $deviceCode,
                'connect_status_url' => route('devices.connect-status', $device),
                'approve_url' => route('devices.approve', $device),
                'device_page_url' => route('devices.show', $device),
            ]);
        }

        return redirect()->route('devices.index')->with('status', 'Device registered.');
    }

    public function update(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'network_id' => ['nullable', 'exists:networks,id'],
            'network_ids' => ['array'],
            'network_ids.*' => ['exists:networks,id'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'sim_number' => ['nullable', 'string', 'max:60'],
            'branch' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'android_version' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);

        $networkIds = $this->resolveNetworkIds($validated);

        if ($networkIds === []) {
            return response()->json(['success' => false, 'message' => 'Select at least one network.'], 422);
        }

        $device->update([
            'name' => $validated['name'],
            'network_id' => $networkIds[0],
            'phone_number' => $validated['phone_number'] ?? $device->phone_number,
            'sim_number' => $validated['sim_number'] ?? $device->sim_number,
            'branch' => $validated['branch'] ?? $device->branch,
            'model' => $validated['model'] ?? $device->model,
            'android_version' => $validated['android_version'] ?? $device->android_version,
            'app_version' => $validated['app_version'] ?? $device->app_version,
        ]);

        $device->networks()->sync($networkIds);

        $this->recordAudit('Device updated', 'Device', $device->id, [
            'name' => $device->name,
            'network_ids' => $networkIds,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Device updated successfully.']);
        }

        return back()->with('status', 'Device updated.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, int>
     */
    private function resolveNetworkIds(array $validated): array
    {
        $ids = array_values(array_filter(array_map(
            'intval',
            $validated['network_ids'] ?? []
        )));

        if ($ids === [] && ! empty($validated['network_id'])) {
            $ids = [(int) $validated['network_id']];
        }

        return array_unique($ids);
    }

    public function storeLine(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'sim_slot' => ['required', 'integer', 'between:1,4'],
            'network_id' => ['required', 'exists:networks,id'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'subscription_id' => ['nullable', 'string', 'max:30'],
        ]);

        $line = $device->lines()->updateOrCreate(
            ['sim_slot' => $validated['sim_slot']],
            [
                'network_id' => $validated['network_id'],
                'phone_number' => $validated['phone_number'] ?? null,
                'subscription_id' => $validated['subscription_id'] ?? null,
            ],
        );

        $this->recordAudit('Device line saved', 'Device', $device->id, [
            'device_line_id' => $line->id,
            'sim_slot' => $line->sim_slot,
            'network_id' => $line->network_id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'SIM line saved.']);
        }

        return back()->with('status', 'SIM line saved.');
    }

    public function destroyLine(Request $request, Device $device, DeviceLine $line): JsonResponse|RedirectResponse
    {
        abort_unless($line->device_id === $device->id, 403, 'Line does not belong to this device.');

        $line->delete();

        $this->recordAudit('Device line removed', 'Device', $device->id, ['sim_slot' => $line->sim_slot]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'SIM line removed.']);
        }

        return back()->with('status', 'SIM line removed.');
    }

    public function approve(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        if (in_array($device->status, ['blocked', 'revoked'], true)) {
            return response()->json(['success' => false, 'message' => 'Blocked or revoked devices cannot be re-activated.'], 422);
        }

        $device->approve();

        $this->recordAudit('Device approved', 'Device', $device->id, ['status' => 'active']);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Device approved and activated.']);
        }

        return back()->with('status', 'Device approved and activated.');
    }

    public function suspend(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $device->suspend();

        $this->recordAudit('Device suspended', 'Device', $device->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Device suspended.']);
        }

        return back()->with('status', 'Device suspended.');
    }

    public function block(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $device->block();

        $this->recordAudit('Device blocked', 'Device', $device->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Device blocked.']);
        }

        return back()->with('status', 'Device blocked.');
    }

    public function revoke(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $device->revoke();

        $this->recordAudit('Device revoked', 'Device', $device->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Device revoked.']);
        }

        return back()->with('status', 'Device revoked.');
    }

    public function regenerateCode(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $deviceCode = Device::generateDeviceCode();

        $device->forceFill(['device_code' => $deviceCode])->save();

        $this->recordAudit('Device code regenerated', 'Device', $device->id);

        session()->flash('credentials_flash', [
            'device_id' => $device->id,
            'device_code' => $deviceCode,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'New device code generated.', 'device_code' => $deviceCode]);
        }

        return back()->with('status', 'New device code generated.');
    }

    public function destroy(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($device) {
            $device->smsMessages()->delete();
            $device->delete();
        });

        $this->recordAudit('Device removed', 'Device', null, ['name' => $device->name]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Device removed.']);
        }

        return redirect()->route('devices.index')->with('status', 'Device removed.');
    }
}
