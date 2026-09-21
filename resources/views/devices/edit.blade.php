@extends('layouts.app')

@section('title', 'Edit '.$device->name)

@section('content')
    <div class="view-head">
        <div>
            <h2>Edit Device</h2>
            <p class="sub">{{ $device->name }} · {{ $device->device_code }} · {{ ucfirst($device->status) }}</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('devices.show', $device) }}" class="btn btn-ghost">← View device</a>
            <a href="{{ route('devices.index') }}" class="btn btn-ghost">Back to devices</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Device Information</h3>
            <span class="tag {{ $device->status === 'active' ? 'tag-green' : ($device->status === 'pending' ? 'tag-gold' : 'tag-grey') }}">{{ ucfirst($device->status) }}</span>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('devices.update', $device) }}" data-device-form>
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="field"><label>Device name</label><input type="text" name="name" value="{{ old('name', $device->name) }}" required></div>
                    <div class="field"><label>Branch</label><input type="text" name="branch" value="{{ old('branch', $device->branch) }}"></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Phone number</label><input type="text" name="phone_number" value="{{ old('phone_number', $device->phone_number) }}" placeholder="07xxxxxxxx"></div>
                    <div class="field"><label>SIM number</label><input type="text" name="sim_number" value="{{ old('sim_number', $device->sim_number) }}"></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Model</label><input type="text" name="model" value="{{ old('model', $device->model) }}" placeholder="e.g. Samsung A15"></div>
                    <div class="field"><label>Networks</label>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; padding:8px; border:1.5px solid var(--line); border-radius:8px; background:var(--white);">
                            @foreach($networks as $network)
                                <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                                    <input type="checkbox" name="network_ids[]" value="{{ $network->id }}" {{ $device->networks->contains($network->id) || $device->network_id == $network->id ? 'checked' : '' }}>
                                    <span class="net-dot" style="background:{{ $network->color }};"></span> {{ $network->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('devices.show', $device) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Device Status</h3>
        </div>
        <div class="panel-body">
            <div class="detail-grid">
                <div class="detail-item"><div class="dk">Device Code</div><div class="dv" style="font-family:monospace; font-weight:700;">{{ $device->device_code }}</div></div>
                <div class="detail-item"><div class="dk">Status</div><div class="dv"><span class="tag {{ $device->status === 'active' ? 'tag-green' : 'tag-grey' }}">{{ ucfirst($device->status) }}</span> @if($device->isOffline()) <span class="tag tag-red">Offline</span> @endif</div></div>
                <div class="detail-item"><div class="dk">Agent</div><div class="dv">{{ $device->agent?->name ?? '—' }}</div></div>
                <div class="detail-item"><div class="dk">Last heartbeat</div><div class="dv">{{ $device->last_heartbeat_at?->diffForHumans() ?? 'Never' }} · {{ $device->last_heartbeat_at?->format('d M Y H:i') ?? '' }}</div></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-device-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                // Use POST with _method=PUT hidden input (more reliable for FormData than fetch PUT)
                submitForm(form, { method: 'POST', done: () => { toast('Device updated.', 'success'); setTimeout(() => window.location.href = '{{ route('devices.show', $device) }}', 600); } });
            });
        });
    </script>
@endsection
