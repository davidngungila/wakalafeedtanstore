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
        @php $hasOnlinePhone = $device->phones->contains(fn($p) => $p->isOnline()); @endphp
        <div class="view-actions">
            <a href="{{ route('devices.index') }}" class="btn btn-ghost">← All devices</a>
            @if($hasOnlinePhone)
                <button class="btn btn-ghost" disabled title="A phone is already connected — disconnect it first" style="opacity:.6; cursor:not-allowed;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><rect x="7" y="2" width="10" height="20" rx="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    Phone connected
                </button>
            @else
                <button class="btn btn-ghost" onclick="openConnectModal('{{ $device->device_code }}', '{{ url('/') }}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><rect x="7" y="2" width="10" height="20" rx="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                    Connect phone
                </button>
            @endif
            @if (is_admin())
                <a href="{{ route('devices.edit', $device) }}" class="btn btn-primary">Edit device</a>
            @endif
        </div>
    </div>

    @include('devices.partials.credentials-popup')
    @include('devices.partials.connect-popup')

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

    <div class="table-card" style="margin-top:24px;">
        <div class="table-toolbar" style="border:none;padding:14px 20px;">
            <strong style="font-size:14px;">SIM lines (chips)</strong>
            <div style="font-size:12.5px;color:var(--ink-soft);">Each SIM slot maps to a network; the app reports which slot each SMS arrived on.</div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>SIM slot</th>
                        <th>Network</th>
                        <th>Phone number</th>
                        <th>Subscription ID</th>
                        @if (is_admin())
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($device->lines as $line)
                        <tr>
                            <td><div class="cell-title">{{ $line->displayName() }}</div></td>
                            <td>
                                @if ($line->network)
                                    <span style="display:inline-flex;align-items:center;gap:7px;"><span style="width:9px;height:9px;border-radius:50%;background:{{ $line->network->color }};display:inline-block;"></span>{{ $line->network->name }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $line->phone_number ?? '—' }}</td>
                            <td><span class="cell-sub">{{ $line->subscription_id ?? '—' }}</span></td>
                            @if (is_admin())
                                <td>
                                    <div class="row-actions">
                                        <form method="POST" action="{{ route('devices.lines.destroy', [$device, $line]) }}" onsubmit="return confirm('Remove this SIM line?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding:5px 10px;font-size:12px;">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ is_admin() ? 5 : 4 }}" class="empty-state"><h4>No SIM lines configured</h4><p>Add the SIM chips installed in this phone and the network each one serves.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (is_admin())
            <form method="POST" action="{{ route('devices.lines.store', $device) }}" class="table-toolbar" style="border:none;border-top:1px solid var(--line);gap:10px;flex-wrap:wrap;align-items:end;">
                @csrf
                <div class="field" style="min-width:120px;">
                    <label>SIM slot</label>
                    <select name="sim_slot" required style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                        @foreach ([1, 2, 3, 4] as $slot)
                            <option value="{{ $slot }}">{{ $slot }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="min-width:180px;">
                    <label>Network</label>
                    <select name="network_id" required style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                        @foreach ($networks as $network)
                            <option value="{{ $network->id }}">{{ $network->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Phone number</label>
                    <input type="text" name="phone_number" placeholder="07…" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;background:var(--white);color:var(--coffee-700);">
                </div>
                <div class="field">
                    <label>Subscription ID (optional)</label>
                    <input type="text" name="subscription_id" placeholder="Android sub id" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;background:var(--white);color:var(--coffee-700);">
                </div>
                <button type="submit" class="btn btn-primary">Add line</button>
            </form>
        @endif
    </div>

    @if ($device->phones->isNotEmpty() || ($device->status === 'active' || $device->status === 'pending'))
        <div class="table-card" style="margin-top:24px;">
            <div class="table-toolbar" style="border:none;padding:14px 20px;">
                <strong style="font-size:14px;">Connected phones</strong>
                <div style="font-size:12.5px;color:var(--ink-soft);">Handsets paired with this device code. Green LED pulses while the phone is connected.</div>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th style="width:60px;">Status</th>
                            <th>Phone</th>
                            <th>App</th>
                            <th>Last seen</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($device->phones as $phone)
                            <tr>
                                <td>
                                    <span class="led {{ $phone->isOnline() ? 'led-on' : 'led-off' }}" data-phone-led="{{ $phone->id }}" title="{{ $phone->isOnline() ? 'Connected' : 'Offline' }}"></span>
                                </td>
                                <td>
                                    <div class="cell-title">{{ $phone->model ?? 'Phone' }}</div>
                                    <div class="cell-sub" style="font-family:monospace;font-size:11.5px;">{{ $phone->device_uid }}</div>
                                </td>
                                <td>
                                    <div class="cell-title">{{ $phone->app_version ?? '—' }}</div>
                                    <div class="cell-sub">Android {{ $phone->android_version ?? '—' }}</div>
                                </td>
                                <td>
                                    <span class="cell-title" data-phone-seen="{{ $phone->id }}">{{ $phone->last_seen_at?->diffForHumans() ?? 'Never' }}</span>
                                    <div class="cell-sub">{{ $phone->last_seen_at?->format('H:i:s · d M Y') ?? '' }}</div>
                                </td>
                                <td class="cell-sub">{{ $phone->ip ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state"><h4>No phone has connected yet</h4><p>Scan the QR above with the MobiControl app to pair this phone.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if (is_admin() || is_supervisor())
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
                    @if (is_admin())
                        <form method="POST" action="{{ route('devices.code', $device) }}" onsubmit="return confirm('Generate a new device code? The old code stops working immediately.')">
                            @csrf
                            <button class="btn btn-ghost">Regenerate code</button>
                        </form>
                    @endif
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
                    @if (is_admin())
                        <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('Delete this device and all its SMS records?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="tabs" style="margin-top:28px;" role="tablist">
        <button type="button" class="tab-btn {{ $activeTab === 'messages' ? 'active' : '' }}" data-tab="messages" onclick="setDeviceTab('messages', this)">
            Messages <span class="tab-count">{{ $sms->total() }}</span>
        </button>
        <button type="button" class="tab-btn {{ $activeTab === 'transactions' ? 'active' : '' }}" data-tab="transactions" onclick="setDeviceTab('transactions', this)">
            Transactions <span class="tab-count">{{ $transactions->count() }}</span>
        </button>
        <a href="{{ route('sms.index', ['device' => $device->id]) }}" class="btn btn-ghost btn-sm" style="margin-left:auto;">Open Messages</a>
    </div>

    <div id="dpan-messages" class="tab-panel {{ $activeTab === 'messages' ? '' : 'hidden' }}">
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
                    <tbody id="deviceMsgBody">
                        @forelse ($sms as $message)
                            <tr data-sms-id="{{ $message->id }}" class="row-click" onclick="window.location='{{ route('sms.show', $message) }}'" style="cursor:pointer;">
                                <td class="cell-sub">
                                    {{ $message->server_received_at->format('H:i:s') }}
                                    @if ($message->sim_slot)
                                        <div style="margin-top:3px;">SIM {{ $message->sim_slot }}</div>
                                    @endif
                                </td>
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
                                        <div class="cell-sub">→ <a href="{{ route('transactions.index', ['q' => $message->transaction->reference]) }}" onclick="event.stopPropagation()">{{ $message->transaction->reference }}</a></div>
                                    @endif
                                </td>
                                <td><span class="tag {{ status_badge($message->processing_status === 'RECORDED' ? 'completed' : strtolower($message->processing_status)) }}">{{ ucfirst(strtolower($message->processing_status)) }}</span></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('sms.show', $message) }}" title="View full SMS" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);" onclick="event.stopPropagation()">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        </a>
                                    </div>
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
    </div>

    <div id="dpan-transactions" class="tab-panel {{ $activeTab === 'transactions' ? '' : 'hidden' }}">
        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Network</th>
                            <th>Amount</th>
                            <th>Commission</th>
                            <th>Status</th>
                            <th>Operator</th>
                        </tr>
                    </thead>
                    <tbody id="devTxnBody">
                        @forelse ($transactions as $txn)
                            <tr data-id="{{ $txn->id }}">
                                <td>
                                    <div class="cell-title">{{ $txn->reference }}</div>
                                    <div class="cell-sub">{{ $txn->created_at->format('d M Y · H:i') }}</div>
                                </td>
                                <td>
                                    <div class="cell-title">{{ $txn->customer_name ?? '—' }}</div>
                                    <div class="cell-sub">{{ $txn->customer_phone }}</div>
                                </td>
                                <td>
                                    <div class="cell-title">{{ txn_type_label($txn->type) }}</div>
                                    <div class="cell-sub">{{ $txn->provider_reference ?? '—' }}</div>
                                </td>
                                <td>
                                    @if ($txn->network)
                                        <span style="display:inline-flex;align-items:center;gap:7px;"><span style="width:9px;height:9px;border-radius:50%;background:{{ $txn->network->color }};display:inline-block;"></span>{{ $txn->network->name }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="cell-title">@money($txn->amount)</td>
                                <td>@money($txn->commission)</td>
                                <td><span class="tag {{ status_badge($txn->status) }}">{{ ucfirst($txn->status) }}</span></td>
                                <td>
                                    <div class="cell-sub">{{ $txn->operator?->name ?? '—' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty-state"><h4>No transactions from this device</h4><p>Your captured SMS messages are automatically analyzed. When a message matches a supported financial template, the transaction is extracted and recorded automatically.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
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
                            @php $assignedIds = $device->networks->pluck('id')->toArray() ?: ($device->network_id ? [$device->network_id] : []); @endphp
                            @include('devices.partials.network-picker', ['networks' => $networks ?? [], 'selectedIds' => $assignedIds, 'pickerKey' => 'edit'])
                            <div class="field">
                                <label>Device name</label>
                                <input type="text" name="name" value="{{ $device->name }}" required>
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
    @include('partials._live-sms')
    <script>
        const TYPE_LABEL = {
            deposit: 'Customer Deposit', withdrawal: 'Customer Withdrawal', send_money: 'Send Money',
            bill_payment: 'Bill Payment', airtime: 'Airtime', data: 'Data Bundle',
            bank_to_wallet: 'Bank to Wallet', wallet_to_bank: 'Wallet to Bank',
        };
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
        function setDeviceTab(tab, btn) {
            document.querySelectorAll('.tabs .tab-btn').forEach(b => b.classList.toggle('active', b === btn));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('hidden', p.id !== 'dpan-' + tab));
            history.replaceState({}, '', window.location.href.split('?')[0].split('#')[0] + '#tab-' + tab);
        }
        (function applyHashTab() {
            const m = window.location.hash.match(/^#tab-(messages|transactions)$/);
            if (!m) return;
            const btn = document.querySelector('.tabs .tab-btn[data-tab="' + m[1] + '"]');
            if (btn) setDeviceTab(m[1], btn);
        })();
        function updatePhoneLeds() {
            fetch('{{ route('devices.phones.status', $device) }}', { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data || !Array.isArray(data.phones)) return;
                    data.phones.forEach(p => {
                        const led = document.querySelector('[data-phone-led="' + p.id + '"]');
                        if (led) {
                            led.className = 'led ' + (p.online ? 'led-on' : 'led-off');
                            led.title = p.online ? 'Connected' : 'Offline';
                        }
                        const seen = document.querySelector('[data-phone-seen="' + p.id + '"]');
                        if (seen) seen.textContent = p.last_seen_at;
                    });
                })
                .catch(() => {});
        }
        if (document.querySelector('[data-phone-led]')) {
            updatePhoneLeds();
            setInterval(updatePhoneLeds, 15000);
        }
        document.querySelectorAll('[data-edit-device-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: () => setTimeout(() => location.reload(), 600) });
            });
        });

        @php
            $deviceTxns = $transactions->map(fn ($t) => [
                'reference' => $t->reference,
                'provider_reference' => $t->provider_reference,
                'type' => $t->type,
                'customer_name' => $t->customer_name,
                'customer_phone' => $t->customer_phone,
                'network' => $t->network?->name,
                'amount' => (float) $t->amount,
                'fee' => (float) $t->fee,
                'commission' => (float) $t->commission,
                'status' => $t->status,
                'created_at' => $t->created_at->format('d M Y H:i'),
                'operator' => $t->operator?->name,
            ])->values();
        @endphp
        const deviceTxnsData = @json($deviceTxns);

        function devFmt(n) { return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }

        bindRowClick('#devTxnBody tr[data-id]', tr => {
            const t = deviceTxnsData.find(x => x.reference === tr.querySelector('.cell-title').textContent.trim());
            if (!t) return [];
            return [
                ['Reference', t.reference],
                ['Provider ref', t.provider_reference || '—'],
                ['Date', t.created_at],
                ['Type', TYPE_LABEL[t.type] || t.type],
                ['Network', t.network || '—'],
                ['Customer', t.customer_name || '—'],
                ['Phone', t.customer_phone],
                ['Amount', devFmt(t.amount)],
                ['Fee', devFmt(t.fee)],
                ['Commission', devFmt(t.commission)],
                ['Status', { __html: statusBadgeHtml(t.status) }],
                ['Operator', t.operator || '—'],
            ];
        }, 'Transaction details');

        if (document.getElementById('deviceMsgBody')) {
            smsLiveStart({
                stream: '{{ route('sms.stream') }}',
                since: {{ $sms->first()?->id ?? 0 }},
                updated: '{{ now()->subMinutes(2)->toIso8601String() }}',
                body: '#deviceMsgBody',
                maxRows: 120,
                filter: (data) => Number(data.device_id) === Number({{ $device->id }}),
                openBody: (data) => openMessage(data.body),
                cells: (data) => [
                    smsTd(smsEsc(data.time) + (data.sim_slot ? '<div style="margin-top:3px;">SIM ' + smsEsc(data.sim_slot) + '</div>' : ''), 'cell-sub'),
                    smsTd(smsEsc(data.sender)),
                    smsNetworkCell(data),
                    smsTd(smsEsc(data.type || '—')),
                    smsTd(smsEsc(data.amount || '—')),
                    smsCustomerCell(data),
                    smsRefCell(data),
                    smsStatusCell(data),
                    smsViewCell(),
                ],
            });
        }
    </script>
@endsection