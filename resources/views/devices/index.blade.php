@extends('layouts.app')

@section('title', 'Devices')

@section('content')
    <div class="view-head">
        <div>
            <h2>Devices</h2>
            <p class="sub">Registered Android phones push mobile-money SMS straight into the system. No manual entry.</p>
        </div>
        @if (is_admin())
            <div class="view-actions">
                <button class="btn btn-primary" onclick="openModal('deviceModal')">+ Register device</button>
            </div>
        @endif
    </div>

    @if ($tokenFlash)
        <div class="status-banner" style="background:var(--acacia-100);color:var(--acacia-700);border-radius:10px;padding:16px;margin-bottom:20px;">
            <div style="font-weight:700;font-size:13.5px;margin-bottom:6px;">API token — copy it now (shown only once)</div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <code id="tokenFlashCode" style="background:#fff;border:1px solid var(--line);border-radius:8px;padding:8px 12px;font-size:13px;letter-spacing:.3px;">{{ $tokenFlash['token'] }}</code>
                <button class="btn btn-ghost" onclick="copyToken()">Copy</button>
            </div>
            <div style="font-size:12px;color:var(--acacia-700);margin-top:8px;opacity:.9;">Enter this token in the MobiControl app on the phone before approving this device. The device starts ingesting SMS once approved.</div>
        </div>
    @endif

    <form method="GET" action="{{ route('devices.index') }}">
        <div class="table-card">
            <div class="table-toolbar">
                <div class="chip-filters" id="statusChips">
                    @foreach (['all', 'pending', 'active', 'offline', 'suspended', 'blocked', 'revoked'] as $st)
                        <button type="button" class="chip {{ ($filters['status'] ?? 'all') === $st ? 'active' : '' }}"
                            onclick="setStatusFilter('{{ $st }}')" data-status="{{ $st }}">
                            {{ ucfirst($st) }}
                        </button>
                    @endforeach
                    <input type="hidden" name="status" id="fStatus" value="{{ $filters['status'] ?? 'all' }}">
                </div>
                <div class="table-search">
                    <select name="network" onchange="this.form.submit()" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                        <option value="all">All networks</option>
                        @foreach ($networks as $network)
                            <option value="{{ $network->id }}" {{ ($filters['network'] ?? 'all') == $network->id ? 'selected' : '' }}>{{ $network->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="table-card" style="margin-top:-24px;">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Agent</th>
                        <th>Network</th>
                        <th>Phone / SIM</th>
                        <th>App</th>
                        <th>Last heartbeat</th>
                        <th>Last SMS</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="devicesBody">
                    @forelse ($devices as $device)
                        @php
                            $status = $device->displayedStatus();
                            $badge = match ($status) {
                                'active' => 'tag-green',
                                'pending' => 'tag-gold',
                                'offline', 'revoked' => 'tag-grey',
                                default => 'tag-red',
                            };
                        @endphp
                        <tr data-id="{{ $device->id }}" data-name="{{ $device->name }}" data-agent="{{ $device->agent?->name ?? '—' }}"
                            data-network="{{ $device->network?->name ?? '—' }}" data-phone="{{ $device->phone_number ?? '—' }}"
                            data-sim="{{ $device->sim_number ?? '—' }}" data-model="{{ $device->model ?? '—' }}"
                            data-android="{{ $device->android_version ?? '—' }}" data-app="{{ $device->app_version ?? '—' }}"
                            data-branch="{{ $device->branch ?? '—' }}" data-status="{{ ucfirst($status) }}"
                            data-heartbeat="{{ $device->last_heartbeat_at?->format('d M Y H:i') ?? 'Never' }}"
                            data-sms="{{ $device->last_sms_at?->format('d M Y H:i') ?? 'Never' }}">
                            <td>
                                <div class="cell-main">
                                    <div class="avatar" style="background:var(--terracotta-100);color:var(--terracotta-600);">{{ strtoupper(substr($device->name, 0, 2)) }}</div>
                                    <div>
                                        <div class="cell-title">{{ $device->name }}</div>
                                        <div class="cell-sub">{{ $device->model ?? $device->device_uid ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">{{ $device->agent?->name ?? '—' }}</div>
                                <div class="cell-sub">{{ $device->branch ?? '' }}</div>
                            </td>
                            <td>
                                @if ($device->network)
                                    <span style="display:inline-flex;align-items:center;gap:7px;">
                                        <span style="width:9px;height:9px;border-radius:50%;background:{{ $device->network->color }};display:inline-block;"></span>
                                        {{ $device->network->name }}
                                    </span>
                                @else
                                    <span class="cell-sub">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="cell-title">{{ $device->phone_number ?? '—' }}</div>
                                <div class="cell-sub">{{ $device->sim_number ?? '' }}</div>
                            </td>
                            <td class="cell-sub">{{ $device->app_version ?? '—' }}</td>
                            <td class="cell-sub">{{ $device->last_heartbeat_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="cell-sub">{{ $device->last_sms_at?->diffForHumans() ?? 'Never' }}</td>
                            <td><span class="tag {{ $badge }}">{{ ucfirst($status) }}</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('devices.show', $device) }}" title="Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-state"><h4>No devices yet</h4><p>Register your first Android phone to start automatic SMS capture.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (is_admin())
        <!-- Register device modal -->
        <div class="modal-backdrop" id="deviceModal">
            <div class="modal">
                <div class="modal-head">
                    <h3>Register device</h3>
                    <button class="modal-close" onclick="closeModal('deviceModal')">✕</button>
                </div>
                <form id="deviceForm" data-device-form action="{{ route('devices.store') }}">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="field">
                                <label>Device name</label>
                                <input type="text" name="name" placeholder="e.g. Samsung A15" required>
                            </div>
                            <div class="field">
                                <label>Network</label>
                                <select name="network_id" required>
                                    @foreach ($networks as $network)
                                        <option value="{{ $network->id }}">{{ $network->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label>Phone number</label>
                                <input type="text" name="phone_number" placeholder="0712345678">
                            </div>
                            <div class="field">
                                <label>SIM number (ICCID)</label>
                                <input type="text" name="sim_number" placeholder="SIM ICCID or label">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label>Branch</label>
                                <input type="text" name="branch" placeholder="e.g. Moshi">
                            </div>
                            <div class="field">
                                <label>Model</label>
                                <input type="text" name="model" placeholder="e.g. SM-A156">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label>Android version</label>
                                <input type="text" name="android_version" placeholder="e.g. 14">
                            </div>
                            <div class="field">
                                <label>App version</label>
                                <input type="text" name="app_version" placeholder="e.g. 1.0.1">
                            </div>
                        </div>
                        <p style="font-size:12px;color:var(--ink-soft);margin:0;">The device starts as <b>Pending</b>. The API token is shown once — approve the device on the list to activate it.</p>
                    </div>
                    <div class="modal-foot">
                        <button type="button" class="btn btn-ghost" onclick="closeModal('deviceModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Register device</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        function setStatusFilter(st) {
            document.getElementById('fStatus').value = st;
            document.getElementById('fStatus').closest('form').submit();
        }
        function copyToken() {
            const el = document.getElementById('tokenFlashCode');
            navigator.clipboard.writeText(el.textContent.trim()).then(() => toast('Token copied.', 'success'));
        }
        document.querySelectorAll('[data-device-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, {
                    done: () => setTimeout(() => location.reload(), 600),
                });
            });
        });
        bindRowClick('#devicesBody tr[data-id]', tr => {
            const st = tr.dataset.status.toLowerCase();
            const cls = st === 'active' ? 'tag-green' : (st === 'pending' ? 'tag-gold' : (st === 'offline' || st === 'revoked' ? 'tag-grey' : 'tag-red'));
            return [
                ['Device', tr.dataset.name],
                ['Model', tr.dataset.model],
                ['Agent', tr.dataset.agent],
                ['Branch', tr.dataset.branch],
                ['Network', tr.dataset.network],
                ['Phone', tr.dataset.phone],
                ['SIM', tr.dataset.sim],
                ['Android', tr.dataset.android],
                ['App version', tr.dataset.app],
                ['Status', { __html: '<span class="tag ' + cls + '">' + tr.dataset.status + '</span>' }],
                ['Last heartbeat', tr.dataset.heartbeat],
                ['Last SMS', tr.dataset.sms],
            ];
        }, 'Device details');
    </script>
@endsection