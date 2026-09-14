<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Network;
use App\Models\SmsMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Device::with(['agent', 'network']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('network') && $request->input('network') !== 'all') {
            $query->where('network_id', $request->input('network'));
        }

        $devices = $query->latest()->get();
        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        return view('devices.index', [
            'devices' => $devices,
            'networks' => $networks,
            'filters' => $request->only(['status', 'network']),
            'tokenFlash' => session()->pull('api_token_flash'),
        ]);
    }

    public function show(Device $device): View
    {
        $device->load(['agent', 'network']);

        $sms = SmsMessage::with(['transaction'])
            ->where('device_id', $device->id)
            ->latest('server_received_at')
            ->limit(30)
            ->get();

        return view('devices.show', [
            'device' => $device,
            'sms' => $sms,
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'tokenFlash' => session()->pull('api_token_flash'),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'network_id' => ['required', 'exists:networks,id'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'sim_number' => ['nullable', 'string', 'max:60'],
            'branch' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'android_version' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:30'],
        ]);

        ['plain' => $plain, 'hash' => $hash] = Device::makeApiToken();

        $device = Device::create($validated + [
            'agent_id' => cash_point()->id,
            'api_token_hash' => $hash,
            'status' => 'pending',
        ]);

        $this->recordAudit('Device registered', 'Device', $device->id, [
            'name' => $device->name,
            'network_id' => $device->network_id,
        ]);

        session()->flash('api_token_flash', [
            'device_id' => $device->id,
            'token' => $plain,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Device registered. API token generated.',
                'token' => $plain,
            ]);
        }

        return redirect()->route('devices.index')->with('status', 'Device registered.');
    }

    public function update(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'network_id' => ['required', 'exists:networks,id'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'sim_number' => ['nullable', 'string', 'max:60'],
            'branch' => ['nullable', 'string', 'max:120'],
        ]);

        $device->update($validated);

        $this->recordAudit('Device updated', 'Device', $device->id, ['name' => $device->name]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Device updated successfully.']);
        }

        return back()->with('status', 'Device updated.');
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
            return response()->json(['success' => true, 'message' => 'Device revoked and token invalidated.']);
        }

        return back()->with('status', 'Device revoked and token invalidated.');
    }

    public function regenerateToken(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        ['plain' => $plain, 'hash' => $hash] = Device::makeApiToken();

        $device->forceFill(['api_token_hash' => $hash])->save();

        $this->recordAudit('Device API token regenerated', 'Device', $device->id);

        session()->flash('api_token_flash', [
            'device_id' => $device->id,
            'token' => $plain,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'New device token generated.', 'token' => $plain]);
        }

        return back()->with('status', 'New device token generated.');
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
