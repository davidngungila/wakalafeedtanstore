@extends('layouts.app')

@section('title', 'Device — '.$device->name)

@section('content')
    @php
        $status = $device->displayedStatus();
        $badge = match ($status) {
            'active' => 'tag-green',
            'pending' => 'tag-gold',
            'offline', 'revoked' => 'tag-grey',
            default => 'tag-red',
        };
        $counts = $device->todayCounts();
    @endphp

    <div class="view-head">
        <div>
            <h2>{{ $device->name }} <span class="tag {{ $badge }}" style="vertical-align:middle;">{{ ucfirst($status) }}</span></h2>
            <p class="sub">{{ $device->model ?? $device->device_uid ?? '' }} · {{ $device->device_uid ? 'UID: '.$device->device_uid : '' }}</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('devices.index') }}" class="btn btn-ghost">← All devices</a>
            @if (is_admin())
                <button class="btn btn-primary" onclick="openModal('editDeviceModal')">Edit device</button>
            @endif
        </div>
    </div>

    @include('devices.partials.credentials-popup')

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 17h-3l-2 3-3-6-3 3H8"></path><path d="M2 6h20v11H8"></path></svg>
            </div></div>
            <div class="stat-value">{{ $counts['received'] }}</div>
            <div class="stat-label">SMS received today</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"></path><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div></div>
            <div class="stat-value">{{ $counts['processed'] }}</div>
            <div class="stat-label">Processed into transactions</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div></div>
            <div class="stat-value">{{ $counts['pending'] }}</div>
            <div class="stat-label">Pending processing</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div></div>
            <div class="stat-value">{{ $counts['failed'] }}</div>
            <div class="stat-label">Failed processing</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 12H2"></path><path d="M14 8s2 4 4 4-4 4-4 4"></path><path d="M6 6a10 10 0 1 1 0 12"></path></svg>
            </div></div>
            <div class="stat-value">{{ $counts['duplicate'] }}</div>
            <div class="stat-label">Duplicates skipped</div>
        </div>
    </div>

    <div class="table-card" style="margin-top:24px;">
        <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;padding:20px 22px;">
            @php
                $detailRows = [
                    ['Agent', $device->agent?->name ?? '—'],
                    ['Branch', $device->branch ?? '—'],
                    ['Device code', $device->device_code],
                    ['Networks', $device->networks->isNotEmpty()
                        ? $device->networks->map(fn ($n) => $n->name)->implode(', ')
                        : ($device->network?->name ?? '—')],
                    ['Phone number', $device->phone_number ?? '—'],
                    ['SIM number', $device->sim_number ?? '—'],
                    ['Android version', $device->android_version ?? '—'],
                    ['App version', $device->app_version ?? '—'],
                    ['Last SMS', $device->last_sms_at ? $device->last_sms_at->format('H:i:s · d M Y') : 'Never'],
                    ['Last sync', $device->last_sync_at ? $device->last_sync_at->format('H:i:s · d M Y') : 'Never'],
                    ['Last heartbeat', $device->last_heartbeat_at ? $device->last_heartbeat_at->format('H:i:s · d M Y') : 'Never'],
                    ['Registered', $device->created_at->format('d M Y H:i')],
                    ['Last IP', $device->last_ip ?? '—'],
                ];
            @endphp
            @foreach ($detailRows as $row)
                @php [$label, $value] = $row; @endphp
                <div>
                    <div style="font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--ink-soft);font-weight:700;margin-bottom:5px;">{{ $label }}</div>
                    <div style="font-size:14.5px;font-weight:700;color:var(--coffee-700);">
                        @if ($label === 'Device code')
                            <span style="display:inline-flex;align-items:center;gap:8px;">
                                <span style="font-family:monospace;letter-spacing:2px;">{{ $value }}</span>
                                <button type="button" class="btn btn-ghost" style="padding:4px 8px;font-size:11.5px;" onclick="copyText('{{ $value }}', 'Device code copied.')">Copy</button>
                            </span>
                        @else
                            {{ $value }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if (is_admin())
        <div class="table-card" style="margin-top:24px;">
            <div class="table-toolbar" style="border:none;padding:14px 20px;">
                <strong style="font-size:14px;">Device actions</strong>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    @if ($device->status === 'pending')
                        <form method="POST" action="{{ route('devices.approve', $device) }}" onsubmit="return confirm('Approve and activate this device?')">
                            @csrf
                            <button class="btn btn-primary">Approve &amp; activate</button>
                        </form>
                    @elseif ($device->status === 'suspended')
                        <form method="POST" action="{{ route('devices.approve', $device) }}" onsubmit="return confirm('Reactivate this suspended device?')">
                            @csrf
                            <button class="btn btn-primary">Re-activate</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('devices.code', $device) }}" onsubmit="return confirm('Generate a new device code? The old code stops working immediately.')">
                        @csrf
                        <button class="btn btn-ghost">Regenerate code</button>
                    </form>
                    @if ($device->status !== 'suspended')
                        <form method="POST" action="{{ route('devices.suspend', $device) }}" onsubmit="return confirm('Suspend this device?')">
                            @csrf
                            <button class="btn btn-ghost">Suspend</button>
                        </form>
                    @endif
                    @if ($device->status !== 'blocked')
                        <form method="POST" action="{{ route('devices.block', $device) }}" onsubmit="return confirm('Block this device?')">
                            @csrf
                            <button class="btn btn-ghost">Block</button>
                        </form>
                    @endif
                    @if ($device->status !== 'revoked')
                        <form method="POST" action="{{ route('devices.revoke', $device) }}" onsubmit="return confirm('Revoke this device permanently? Its device code stops working immediately.')">
                            @csrf
                            <button class="btn btn-danger">Revoke</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('Delete this device and all its SMS records?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="view-head" style="margin-top:28px;">
        <div>
            <h2>All SMS</h2>
            <p class="sub">Every message captured from this phone, most recent first.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('sms.index', ['device' => $device->id]) }}" class="btn btn-ghost">Open SMS monitor</a>
        </div>
    </div>

    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Sender</th>
                        <th>Network</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Customer</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sms as $message)
                        <tr>
                            <td class="cell-sub">{{ $message->server_received_at->format('H:i:s') }}</td>
                            <td>{{ $message->sender }}</td>
                            <td>{!! $message->network
                                ? '<span style="display:inline-flex;align-items:center;gap:7px;"><span style="width:9px;height:9px;border-radius:50%;background:'.$message->network->color.';display:inline-block;"></span>'.$message->network->name.'</span>'
                                : '—' !!}</td>
                            <td>{{ $message->transaction_type ? txn_type_label($message->transaction_type) : '—' }}</td>
                            <td>{{ $message->amount ? money($message->amount) : '—' }}</td>
                            <td>
                                <div class="cell-title">{{ $message->customer_name ?? '—' }}</div>
                                <div class="cell-sub">{{ $message->customer_phone ?? '' }}</div>
                            </td>
                            <td>
                                <span class="cell-title">{{ $message->transaction_reference ?? '—' }}</span>
                                @if ($message->transaction)
                                    <div class="cell-sub">→ <a href="{{ route('transactions.index', ['q' => $message->transaction->reference]) }}">{{ $message->transaction->reference }}</a></div>
                                @endif
                            </td>
                            <td><span class="tag {{ status_badge($message->processing_status === 'processed' ? 'completed' : $message->processing_status) }}">{{ ucfirst($message->processing_status) }}</span></td>
                            <td>
                                <button class="btn btn-ghost" style="padding:6px 10px;font-size:12px;" onclick="openMessage('{{ addslashes($message->message_body) }}')">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-state"><h4>No SMS yet</h4><p>Once the app connects and the device is active, captured SMS will appear here automatically.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $sms->links('pagination.pager') }}
    </div>

    @if (is_admin())
        <!-- Edit device modal -->
        <div class="modal-backdrop" id="editDeviceModal">
            <div class="modal">
                <div class="modal-head">
                    <h3>Edit device</h3>
                    <button class="modal-close" onclick="closeModal('editDeviceModal')">✕</button>
                </div>
                <form id="editDeviceForm" data-edit-device-form action="{{ route('devices.update', $device) }}">
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="field">
                                <label>Device name</label>
                                <input type="text" name="name" value="{{ $device->name }}" required>
                            </div>
                            <div class="field">
                                <label>Networks (access)</label>
                                <div style="display:flex;flex-direction:column;gap:6px;max-height:180px;overflow-y:auto;padding:10px 12px;border:1.5px solid var(--line);border-radius:var(--radius-sm);background:var(--white);">
                                    @php $assignedIds = $device->networks->pluck('id')->toArray() ?: ($device->network_id ? [$device->network_id] : []); @endphp
                                    @foreach ($networks ?? [] as $network)
                                        <label style="display:flex;align-items:center;gap:9px;font-size:13.5px;font-weight:600;color:var(--coffee-700);cursor:pointer;">
                                            <input type="checkbox" name="network_ids[]" value="{{ $network->id }}" {{ in_array($network->id, $assignedIds, true) ? 'checked' : '' }} style="accent-color:var(--terracotta-600);">
                                            <span class="net-dot" style="background:{{ $network->color }};"></span>
                                            {{ $network->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label>Phone number</label>
                                <input type="text" name="phone_number" value="{{ $device->phone_number ?? '' }}">
                            </div>
                            <div class="field">
                                <label>SIM number</label>
                                <input type="text" name="sim_number" value="{{ $device->sim_number ?? '' }}">
                            </div>
                        </div>
                        <div class="field">
                            <label>Branch</label>
                            <input type="text" name="branch" value="{{ $device->branch ?? '' }}">
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button type="button" class="btn btn-ghost" onclick="closeModal('editDeviceModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- SMS body modal -->
    <div class="modal-backdrop" id="smsBodyModal">
            <div class="modal" style="max-width:560px;">
                <div class="modal-head">
                    <h3>SMS content</h3>
                    <button class="modal-close" onclick="closeModal('smsBodyModal')">✕</button>
                </div>
                <div class="modal-body">
                    <pre id="smsBodyText" style="white-space:pre-wrap;word-break:break-word;font-family:inherit;font-size:13.5px;line-height:1.6;background:var(--paper);border:1px solid var(--line);border-radius:10px;padding:14px;margin:0;color:var(--coffee-700);"></pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function copyFlash(id, label) {
            const el = document.getElementById(id);
            navigator.clipboard.writeText(el.textContent.trim()).then(() => toast(label, 'success'));
        }
        function copyText(value, label) {
            navigator.clipboard.writeText(value).then(() => toast(label, 'success'));
        }
        function openMessage(body) {
            const el = document.getElementById('smsBodyText');
            if (el) {
                el.textContent = body;
                openModal('smsBodyModal');
            } else {
                console.warn('SMS body modal not available for this role.');
            }
        }
        document.querySelectorAll('[data-edit-device-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: () => setTimeout(() => location.reload(), 600) });
            });
        });
    </script>
@endsection