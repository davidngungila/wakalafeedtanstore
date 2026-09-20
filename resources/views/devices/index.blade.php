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
                <a href="{{ route('devices.register') }}" class="btn btn-primary">+ Register device</a>
            </div>
        @endif
    </div>

    @include('devices.partials.credentials-popup')
    @include('devices.partials.connect-popup')

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
                        <th>App</th>
                        <th>Today</th>
                        <th>Last heartbeat</th>
                        <th>Last SMS</th>
                        <th>Action</th>
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
                            $deviceNetworks = $device->networks->isNotEmpty() ? $device->networks : collect([$device->network])->filter();
                            $stats = $todayStats[$device->id] ?? null;
                            $netsJson = $deviceNetworks->map(fn ($n) => ['name' => $n->name, 'color' => $n->color])->values();
                        @endphp
                        <tr data-id="{{ $device->id }}" data-name="{{ $device->name }}" data-agent="{{ $device->agent?->name ?? '—' }}"
                            data-code="{{ $device->device_code }}"
                            data-phone="{{ $device->phone_number ?? '—' }}"
                            data-sim="{{ $device->sim_number ?? '—' }}" data-model="{{ $device->model ?? '—' }}"
                            data-android="{{ $device->android_version ?? '—' }}" data-app="{{ $device->app_version ?? '—' }}"
                            data-branch="{{ $device->branch ?? '—' }}" data-uid="{{ $device->device_uid ?? '—' }}"
                            data-ip="{{ $device->last_ip ?? '—' }}" data-status="{{ ucfirst($status) }}"
                            data-nets="{{ $netsJson->toJson() }}"
                            data-heartbeat="{{ $device->last_heartbeat_at?->format('d M Y H:i') ?? 'Never' }}"
                            data-lastsync="{{ $device->last_sync_at?->format('d M Y H:i') ?? 'Never' }}"
                            data-registered="{{ $device->created_at->format('d M Y H:i') }}"
                            data-today="{{ $stats ? ($stats['processed'].' processed · '.$stats['received'].' received') : 'No SMS today' }}"
                            data-sms="{{ $device->last_sms_at?->format('d M Y H:i') ?? 'Never' }}"
                            data-regenerate-code-route="{{ route('devices.code', $device) }}"
                            data-approve-route="{{ route('devices.approve', $device) }}">
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
                                <div class="cell-title">{{ $device->app_version ?? '—' }}</div>
                                <div class="cell-sub">Android {{ $device->android_version ?? '—' }}</div>
                            </td>
                            <td class="cell-sub">{{ $stats ? ($stats['processed'].' / '.$stats['received']) : '—' }}</td>
                            <td class="cell-sub">{{ $device->last_heartbeat_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="cell-sub">{{ $device->last_sms_at?->diffForHumans() ?? 'Never' }}</td>
                            <td>
                                <a href="{{ route('devices.show', $device) }}" class="btn btn-primary btn-sm">View</a>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('devices.show', $device) }}" title="Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <button type="button" title="Connect phone (QR)" onclick="openConnectModal('{{ $device->device_code }}', '{{ url('/') }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                                    </button>
                                    <button type="button" title="View all SMS" onclick="window.location='{{ route('sms.index', ['device' => $device->id]) }}'">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state"><h4>No devices yet</h4><p>Register your first Android phone to start automatic SMS capture.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (is_admin())
        <!-- Regenerate code confirmation popup -->
        <div class="modal-backdrop" id="regenerateCodeModal">
            <div class="popup">
                <div class="modal-head">
                    <h3>Regenerate Device Code</h3>
                    <button class="modal-close" onclick="closeModal('regenerateCodeModal')">✕</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:14px;color:var(--coffee-700);margin:0 0 12px 0;">Are you sure you want to generate a new device code?</p>
                    <p style="font-size:13px;color:var(--danger);margin:0 0 8px 0;">⚠️ The old code will stop working immediately.</p>
                    <p style="font-size:12px;color:var(--ink-soft);margin:0;">The device will need to be updated with the new code to continue functioning.</p>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('regenerateCodeModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmRegenerateCode()">Regenerate Code</button>
                </div>
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
        function copyFlash(id, label) {
            const el = document.getElementById(id);
            navigator.clipboard.writeText(el.textContent.trim()).then(() => toast(label, 'success'));
        }
        function copyText(value, label) {
            navigator.clipboard.writeText(value).then(() => toast(label, 'success'));
        }
        bindRowClick('#devicesBody tr[data-id]', tr => {
            const st = tr.dataset.status.toLowerCase();
            const cls = st === 'active' ? 'tag-green' : (st === 'pending' ? 'tag-gold' : (st === 'offline' || st === 'revoked' ? 'tag-grey' : 'tag-red'));
            let nets = '';
            try {
                const arr = JSON.parse(tr.dataset.nets || '[]');
                nets = arr.map(n => `<span class="tag" style="background:${n.color || '#999'};color:#fff;">${n.name}</span>`).join(' ');
            } catch (e) { nets = '—'; }
            return [
                ['Device', tr.dataset.name],
                ['Device code', tr.dataset.code],
                ['Model', tr.dataset.model],
                ['Device UID', tr.dataset.uid],
                ['Agent', tr.dataset.agent],
                ['Branch', tr.dataset.branch],
                ['Networks', nets ? { __html: nets } : '—'],
                ['Phone', tr.dataset.phone],
                ['SIM', tr.dataset.sim],
                ['Android', tr.dataset.android],
                ['App version', tr.dataset.app],
                ['Today', tr.dataset.today],
                ['Status', { __html: '<span class="tag ' + cls + '">' + tr.dataset.status + '</span>' }],
                ['Last heartbeat', tr.dataset.heartbeat],
                ['Last sync', tr.dataset.lastsync],
                ['Last SMS', tr.dataset.sms],
                ['Last IP', tr.dataset.ip],
                ['Registered', tr.dataset.registered],
            ];
        }, 'Device details', tr => {
            const deviceId = tr.dataset.id;
            const isAdmin = {{ is_admin() ? 'true' : 'false' }};
            const isSupervisor = {{ is_supervisor() ? 'true' : 'false' }};
            const status = tr.dataset.status.toLowerCase();
            const actions = [];

            if ((isAdmin || isSupervisor) && status === 'pending') {
                actions.push({
                    label: 'Approve & activate',
                    action: () => approveDevice(deviceId, tr.dataset.approveRoute),
                    class: 'btn-primary'
                });
            }

            if (isAdmin) {
                actions.push({
                    label: 'Regenerate code',
                    action: () => regenerateDeviceCode(deviceId),
                    class: 'btn-ghost'
                });
            }

            return actions;
        });
        
        let currentDeviceId = null;
        
        function showRegenerateCodeModal(deviceId) {
            currentDeviceId = deviceId;
            openModal('regenerateCodeModal');
        }
        
        async function confirmRegenerateCode() {
            if (!currentDeviceId) return;
            
            closeModal('regenerateCodeModal');
            
            try {
                const row = document.querySelector(`tr[data-id="${currentDeviceId}"]`);
                const route = row ? row.dataset.regenerateCodeRoute : `/devices/${currentDeviceId}/code`;
                
                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    toast('New device code generated.', 'success');
                    closeModal('rowDetailsModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    toast(data.message || 'Failed to regenerate code.', 'error');
                }
            } catch (error) {
                console.error('Error regenerating code:', error);
                toast('Failed to regenerate code.', 'error');
            }
            
            currentDeviceId = null;
        }
        
        async function regenerateDeviceCode(deviceId) {
            showRegenerateCodeModal(deviceId);
        }

        async function approveDevice(deviceId, route) {
            if (!confirm('Approve and activate this device?')) return;

            try {
                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    toast('Device approved and activated.', 'success');
                    closeModal('rowDetailsModal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    toast(data.message || 'Failed to approve device.', 'error');
                }
            } catch (error) {
                console.error('Error approving device:', error);
                toast('Failed to approve device.', 'error');
            }
        }
    </script>
@endsection