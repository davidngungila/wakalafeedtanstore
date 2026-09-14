@extends('layouts.app')

@section('title', 'SMS Monitor')

@section('content')
    <div class="view-head">
        <div>
            <h2>SMS Monitor</h2>
            <p class="sub">Every message captured from connected devices — live, automatic, no manual entry.</p>
        </div>
        <div class="view-actions">
            <span class="tag tag-green" id="liveBadge">● Live</span>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><line x1="9" y1="10" x2="17" y2="10"></line></svg>
            </div></div>
            <div class="stat-value">{{ $today['received'] }}</div>
            <div class="stat-label">Received today</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"></path><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div></div>
            <div class="stat-value">{{ $today['processed'] }}</div>
            <div class="stat-label">Processed to transactions</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div></div>
            <div class="stat-value">{{ $today['pending'] }}</div>
            <div class="stat-label">Pending processing</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div></div>
            <div class="stat-value">{{ $today['failed'] }}</div>
            <div class="stat-label">Failed processing</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 12H2"></path><path d="M14 8s2 4 4 4-4 4-4 4"></path><path d="M6 6a10 10 0 1 1 0 12"></path></svg>
            </div></div>
            <div class="stat-value">{{ $today['duplicates'] }}</div>
            <div class="stat-label">Duplicates skipped</div>
        </div>
    </div>

    <form method="GET" action="{{ route('sms.index') }}" id="smsFilterForm">
        <div class="table-card">
            <div class="table-toolbar">
                <div class="chip-filters">
                    @foreach (['all', 'processed', 'received', 'failed', 'duplicate'] as $st)
                        <button type="button" class="chip {{ ($filters['status'] ?? 'all') === $st ? 'active' : '' }}" onclick="setFilter('status','{{ $st }}')">{{ ucfirst($st) }}</button>
                    @endforeach
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <select name="device" onchange="this.form.submit()" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                        <option value="all">All devices</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}" {{ ($filters['device'] ?? 'all') == $device->id ? 'selected' : '' }}>{{ $device->name }}</option>
                        @endforeach
                    </select>
                    <select name="network" onchange="this.form.submit()" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                        <option value="all">All networks</option>
                        @foreach ($networks as $network)
                            <option value="{{ $network->id }}" {{ ($filters['network'] ?? 'all') == $network->id ? 'selected' : '' }}>{{ $network->name }}</option>
                        @endforeach
                    </select>
                    <div class="table-search" style="min-width:220px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search message, sender, reference…">
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="table-card" style="margin-top:-24px;">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Device</th>
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
                <tbody id="smsBody">
                    @forelse ($messages as $message)
                        <tr data-id="{{ $message->id }}">
                            <td class="cell-sub">{{ $message->server_received_at->format('H:i:s') }}</td>
                            <td>
                                <a href="{{ route('devices.show', $message->device_id) }}" class="cell-title">{{ $message->device?->name ?? '—' }}</a>
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
                                    <div class="cell-sub">→ <a href="{{ route('transactions.index', ['q' => $message->transaction->reference]) }}">{{ $message->transaction->reference }}</a></div>
                                @endif
                            </td>
                            <td>
                                <span class="tag {{ status_badge($message->processing_status === 'processed' ? 'completed' : $message->processing_status) }}">
                                    {{ $message->is_duplicate ? 'Duplicate' : ucfirst($message->processing_status) }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-ghost" style="padding:6px 10px;font-size:12px;" onclick="openMessage({{ $message->id }})">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr id="smsEmptyRow"><td colspan="10" class="empty-state"><h4>No SMS captured yet</h4><p>Messages from connected devices appear here in real time.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

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
@endsection

@section('scripts')
    <script>
        const smsRowCache = new Map();
        @foreach ($messages as $message)
            @php
                $row = [
                    'id' => $message->id,
                    'time' => $message->server_received_at->format('H:i:s'),
                    'device' => $message->device?->name,
                    'sender' => $message->sender,
                    'network' => $message->network?->name,
                    'network_color' => $message->network?->color,
                    'type' => $message->transaction_type ? txn_type_label($message->transaction_type) : '—',
                    'amount' => $message->amount ? money($message->amount) : '—',
                    'customer' => $message->customer_name ?? '—',
                    'customer_phone' => $message->customer_phone ?? '',
                    'reference' => $message->transaction_reference ?? '—',
                    'status' => $message->is_duplicate ? 'duplicate' : $message->processing_status,
                    'body' => $message->message_body,
                ];
            @endphp
            smsRowCache.set({{ $message->id }}, @json($row));
        @endforeach

        function setFilter(param, value) {
            const form = document.getElementById('smsFilterForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = param;
            input.value = value;
            form.appendChild(input);
            form.submit();
        }

        function statusCls(status) {
            return status === 'processed' ? 'tag-green'
                : (status === 'failed' ? 'tag-red'
                : (status === 'duplicate' ? 'tag-terracotta' : 'tag-gold'));
        }

        function openMessage(id) {
            const row = smsRowCache.get(Number(id));
            if (!row) return;
            document.getElementById('smsBodyText').textContent = row.body;
            openModal('smsBodyModal');
        }

        function prependRow(data) {
            const empty = document.getElementById('smsEmptyRow');
            if (empty) empty.remove();
            const body = document.getElementById('smsBody');
            const tr = document.createElement('tr');
            tr.dataset.id = data.sms_id;
            tr.innerHTML = [
                '<td class="cell-sub">' + data.time + '</td>',
                '<td><span class="cell-title">' + (data.device || '—') + '</span></td>',
                '<td>' + data.sender + '</td>',
                '<td>' + (data.network ? '<span style="display:inline-flex;align-items:center;gap:7px;"><span style="width:9px;height:9px;border-radius:50%;background:' + data.network_color + ';display:inline-block;"></span>' + data.network + '</span>' : '—') + '</td>',
                '<td>' + (data.type || '—') + '</td>',
                '<td>' + (data.amount || '—') + '</td>',
                '<td><div class="cell-title">' + (data.customer || '—') + '</div><div class="cell-sub">' + (data.customer_phone || '') + '</div></td>',
                '<td><span class="cell-title">' + (data.reference || '—') + '</span></td>',
                '<td><span class="tag ' + statusCls(data.status) + '">' + data.status.charAt(0).toUpperCase() + data.status.slice(1) + '</span></td>',
                '<td><button class="btn btn-ghost" style="padding:6px 10px;font-size:12px;" onclick="openMessage(' + data.sms_id + ')">View</button></td>',
            ].join('');
            body.prepend(tr);
        }

        function connectStream(lastId) {
            const source = new EventSource('{{ route('sms.stream') }}?since=' + lastId);
            source.onmessage = (e) => {
                if (e.data === 'ping') return;
                const data = JSON.parse(e.data);
                if (data.sms_id <= lastId) return;
                lastId = data.sms_id;
                smsRowCache.set(data.sms_id, {
                    ...data,
                    time: new Date(data.server_received_at).toLocaleTimeString('en-GB', { hour12: false }),
                    device: data.device || '—',
                });
                prependRow(smsRowCache.get(data.sms_id));
            };
            source.onerror = () => {
                source.close();
                setTimeout(() => connectStream(lastId), 5000);
            };
        }

        const maxId = {{ $messages->first()?->id ?? 0 }};
        connectStream(maxId);
    </script>
@endsection