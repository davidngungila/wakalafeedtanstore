@extends('layouts.app')

@section('title', 'Messages')

@section('content')
    <style>
        .row-click{cursor:pointer;}
        .row-click:hover td{background:var(--sand-100);}
        .box-alert{background:var(--danger-100);border-left:4px solid var(--danger);border-radius:10px;padding:12px 14px;font-size:13.5px;font-weight:600;color:var(--coffee-900);margin-bottom:18px;}
    </style>

    <div class="view-head">
        <div>
            <h2>Messages</h2>
            <p class="sub">Every SMS captured from all connected phones — live, automatic, no manual entry.</p>
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
            <div class="stat-value" data-stat-key="received">{{ $today['received'] }}</div>
            <div class="stat-label">Received today</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"></path><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div></div>
            <div class="stat-value" data-stat-key="processed">{{ $today['processed'] }}</div>
            <div class="stat-label">Processed to transactions</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div></div>
            <div class="stat-value" data-stat-key="pending">{{ $today['pending'] }}</div>
            <div class="stat-label">Pending processing</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
            </div></div>
            <div class="stat-value" data-stat-key="stored">{{ $today['stored'] }}</div>
            <div class="stat-label">Stored · no transaction</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div></div>
            <div class="stat-value" data-stat-key="failed">{{ $today['failed'] }}</div>
            <div class="stat-label">Failed processing</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 12H2"></path><path d="M14 8s2 4 4 4-4 4-4 4"></path><path d="M6 6a10 10 0 1 1 0 12"></path></svg>
            </div></div>
            <div class="stat-value" data-stat-key="duplicates">{{ $today['duplicates'] }}</div>
            <div class="stat-label">Duplicates skipped</div>
        </div>
    </div>

    <form method="GET" action="{{ route('sms.index') }}" id="smsFilterForm">
        <div class="table-card">
            <div class="table-toolbar" style="border:none;">
                <div class="tabs" style="margin:0;border:none;background:transparent;padding:0;box-shadow:none;" role="tablist">
                    @foreach ([
                        'all' => 'All',
                        'processed' => 'Processed',
                        'pending' => 'Pending',
                        'stored' => 'Stored',
                        'failed' => 'Failed',
                        'duplicate' => 'Duplicates',
                    ] as $st => $label)
                        <button type="button" class="tab-btn {{ ($filters['status'] ?? 'all') === $st ? 'active' : '' }}" onclick="setFilter('status','{{ $st }}')">{{ $label }} <span class="tab-count" data-count-key="{{ $st }}">{{ $counts[$st] }}</span></button>
                    @endforeach
                </div>
            </div>
            <div class="table-toolbar" style="border-bottom:none;padding-top:6px;">
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;width:100%;">
                    <select name="device" onchange="this.form.submit()" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                        <option value="all">All devices</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->getRouteKey() }}" {{ (isset($filters['device']) && ((new App\Models\Device)->resolveRouteBinding($filters['device'])?->id ?? $filters['device']) == $device->id) ? 'selected' : '' }}>{{ $device->name }}</option>
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
                        <th>Line</th>
                        <th>Sender</th>
                        <th>Network</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="smsBody">
                    @forelse ($messages as $message)
                        <tr data-id="{{ $message->id }}" data-sms-id="{{ $message->id }}" class="row-click" onclick="window.location='{{ route('sms.show', $message) }}'" style="cursor:pointer;">
                            <td class="cell-sub">{{ $message->server_received_at->format('H:i:s') }}</td>
                            <td>
                                <a href="{{ route('devices.show', $message->device) }}" class="cell-title" onclick="event.stopPropagation()">{{ $message->device?->name ?? '—' }}</a>
                            </td>
                            <td>
                                @if ($message->deviceLine)
                                    <div class="cell-sub">{{ $message->deviceLine->displayName() }} · {{ $message->deviceLine->network?->name ?? '—' }}</div>
                                @else
                                    <span class="cell-sub">—</span>
                                @endif
                            </td>
                            <td>{{ $message->sender }}</td>
                            <td>{!! $message->network
                                ? '<span style="display:inline-flex;align-items:center;gap:7px;"><span style="width:9px;height:9px;border-radius:50%;background:'.$message->network->color.';display:inline-block;"></span>'.$message->network->name.'</span>'
                                : '—' !!}</td>
                            <td>
                                <span class="tag {{ sms_status_badge($message->processing_status, $message->is_duplicate, $message->processing_error) }}">
                                    {{ ucfirst(sms_status_label($message->processing_status, $message->is_duplicate, $message->processing_error)) }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="{{ route('sms.show', $message) }}" class="btn btn-ghost" style="padding:6px 10px;font-size:12px;" onclick="event.stopPropagation()">View</a>
                                    @if(!$message->transaction_id)
                                        <a href="{{ route('sms.show', $message) }}" class="btn btn-primary" style="padding:6px 10px;font-size:12px;" onclick="event.stopPropagation()" title="Force compute">Force</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="smsEmptyRow"><td colspan="7" class="empty-state"><h4>No SMS captured yet</h4><p>Messages from connected devices appear here in real time.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SMS details drawer -->
    <div class="modal-backdrop" id="smsDetailsDrawer">
        <div class="modal" style="max-width:560px;">
            <div class="modal-head">
                <h3>SMS details</h3>
                <button class="modal-close" onclick="closeModal('smsDetailsDrawer')">✕</button>
            </div>
            <div class="modal-body">
                <div id="smsDetailsError" class="box-alert" style="display:none;"></div>
                <div class="detail-grid" id="smsDetailsGrid"></div>
                <div class="receipt">
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;margin-bottom:8px;">Full message</div>
                    <pre id="smsDetailsBody" style="white-space:pre-wrap;word-break:break-word;font-family:inherit;font-size:13.5px;line-height:1.6;margin:0;color:var(--coffee-700);"></pre>
                </div>

            </div>
            <div class="modal-foot" id="smsDetailsFoot" style="display:none;">
                <button class="btn btn-primary" onclick="closeModal('smsDetailsDrawer')">Close</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('partials._live-sms')
    <script>
        const smsRowCache = window.smsCache;
        @foreach ($messages as $message)
            @php
                $label = sms_status_label($message->processing_status, $message->is_duplicate, $message->processing_error);
                $row = [
                    'id' => $message->id,
                    'time' => $message->server_received_at->format('H:i:s'),
                    'datetime' => $message->server_received_at->format('D, j M Y · H:i:s'),
                    'device' => $message->device?->name,
                    'device_id' => $message->device_id,
                    'line' => $message->deviceLine?->displayName(),
                    'sender' => $message->sender,
                    'network' => $message->network?->name,
                    'network_color' => $message->network?->color,
                    'type' => $message->transaction_type ? txn_type_label($message->transaction_type) : '—',
                    'amount' => $message->amount ? money($message->amount) : '—',
                    'customer' => $message->customer_name ?? '—',
                    'customer_phone' => $message->customer_phone ?? '',
                    'reference' => $message->transaction_reference ?? '—',
                    'txn_reference' => $message->transaction?->reference,
                    'label' => ucfirst($label),
                    'status' => $label,
                    'status_key' => $label,
                    'is_today' => $message->server_received_at->isToday(),
                    'badge' => sms_status_badge($message->processing_status, $message->is_duplicate, $message->processing_error),
                    'error' => $message->processing_error,
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

        let currentSmsId = null;
        function openMessage(id) {
            const row = smsRowCache.get(Number(id));
            if (!row) return;
            currentSmsId = id;

            const errBox = document.getElementById('smsDetailsError');
            if (row.error) {
                errBox.style.display = 'block';
                errBox.textContent = row.error;
            } else {
                errBox.style.display = 'none';
            }

            const entries = [
                ['Time (EAT)', row.datetime],
                ['Sender', row.sender],
                ['Line', row.line || '—'],
                ['Network', row.network && row.network !== '—' ? {__html: '<span style="display:inline-flex;align-items:center;gap:7px;"><span style="width:9px;height:9px;border-radius:50%;background:' + row.network_color + ';display:inline-block;"></span>' + row.network + '</span>'} : '—'],
                ['Type', row.type],
                ['Amount', row.amount],
                ['Customer', row.customer],
                ['Phone', row.customer_phone || '—'],
                ['Reference', row.reference],
                ['Transaction', row.txn_reference ? {__html: '<a href="/transactions?q=' + encodeURIComponent(row.txn_reference) + '">' + row.txn_reference + '</a>'} : '—'],
                ['Status', {__html: '<span class="tag ' + row.badge + '">' + row.label + '</span>'}],
            ];

            const grid = document.getElementById('smsDetailsGrid');
            grid.innerHTML = '';
            entries.forEach(([label, value]) => {
                const item = document.createElement('div');
                item.className = 'detail-item';
                const k = document.createElement('div');
                k.className = 'dk';
                k.textContent = label;
                const v = document.createElement('div');
                v.className = 'dv';
                if (value && value.__html) v.innerHTML = value.__html;
                else v.textContent = value == null || value === '' ? '—' : value;
                item.appendChild(k);
                item.appendChild(v);
                grid.appendChild(item);
            });

            document.getElementById('smsDetailsBody').textContent = row.body || '—';

            openModal('smsDetailsDrawer');
        }

        const totalCounts = @json($counts);
        const todayCounts = @json($today);
        const todayKeyMap = { processed: 'processed', pending: 'pending', stored: 'stored', failed: 'failed', duplicate: 'duplicates' };

        const liveStatus = {{ json_encode($filters['status'] ?? 'all') }};
        const liveDevice = {{ json_encode($filters['device'] ?? '') }};
        const liveNetwork = {{ json_encode($filters['network'] ?? '') }};
        const liveQ = {{ json_encode($filters['q'] ?? '') }};

        function liveFilter(data) {
            if (liveStatus !== 'all' && data.status_key !== liveStatus) return false;
            if (liveDevice && Number(data.device_id) !== Number(liveDevice)) return false;
            if (liveNetwork && Number(data.network_id) !== Number(liveNetwork)) return false;
            if (liveQ) {
                const needle = String(liveQ).toLowerCase();
                const hay = [data.sender, data.reference, data.customer, data.customer_phone, data.body, data.device].join(' ').toLowerCase();
                if (!hay.includes(needle)) return false;
            }
            return true;
        }

        function adjustCounts(data, prev) {
            const newKey = data.status_key || 'other';
            const oldKey = prev ? prev.status_key : null;

            if (prev) {
                if (oldKey !== newKey) {
                    if (oldKey && totalCounts[oldKey] !== undefined) totalCounts[oldKey]--;
                    if (totalCounts[newKey] !== undefined) totalCounts[newKey]++;
                    if (prev.is_today && todayCounts[todayKeyMap[oldKey]] !== undefined) todayCounts[todayKeyMap[oldKey]]--;
                    if (data.is_today && todayCounts[todayKeyMap[newKey]] !== undefined) todayCounts[todayKeyMap[newKey]]++;
                }
            } else {
                totalCounts.all = (totalCounts.all || 0) + 1;
                if (totalCounts[newKey] !== undefined) totalCounts[newKey]++;
                if (data.is_today) {
                    todayCounts.received++;
                    if (todayCounts[todayKeyMap[newKey]] !== undefined) todayCounts[todayKeyMap[newKey]]++;
                }
            }
        }

        function renderCounts() {
            document.querySelectorAll('[data-count-key]').forEach(el => {
                const k = el.dataset.countKey;
                if (totalCounts[k] !== undefined) el.textContent = totalCounts[k];
            });
            document.querySelectorAll('[data-stat-key]').forEach(el => {
                const k = el.dataset.statKey;
                if (todayCounts[k] !== undefined) el.textContent = todayCounts[k];
            });
        }

        smsLiveStart({
            stream: '{{ route('sms.stream') }}',
            since: {{ $messages->first()?->id ?? 0 }},
            updated: '{{ now()->subMinutes(2)->toIso8601String() }}',
            body: '#smsBody',
            maxRows: 300,
            rowClick: (data) => openMessage(data.sms_id),
            openBody: (data) => openMessage(data.sms_id),
            cells: (data) => [
                smsTd(smsEsc(data.time), 'cell-sub'),
                smsDeviceCell(data),
                smsTd(smsEsc(data.line || '—'), 'cell-sub'),
                smsTd(smsEsc(data.sender)),
                smsNetworkCell(data),
                smsStatusCell(data),
                smsViewCell(),
            ],
            onMessage: (data, prev) => {
                adjustCounts(data, prev);
                renderCounts();
                return liveFilter(data);
            },
        });
    </script>
@endsection