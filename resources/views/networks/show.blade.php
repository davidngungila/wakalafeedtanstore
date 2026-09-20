@extends('layouts.app')

@section('title', $network->name.' · Overview')

@section('content')
    <div class="view-head">
        <div>
            <h2><span class="net-dot" style="background:{{ $network->color }};"></span> {{ $network->name }} <span class="tag tag-terracotta">{{ $network->code }}</span></h2>
            <p class="sub">Full operation for this network — transactions, SMS and devices, all in one place.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('networks.index') }}" class="btn btn-ghost">← All networks</a>
            <a href="{{ route('transactions.index', ['network' => $network->id]) }}" class="btn btn-ghost">Open transactions page</a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V8l9-5 9 5v13"></path><path d="M8 21v-8"></path><path d="M16 21v-4"></path></svg></div></div>
            <div class="stat-value">@money($stats['float'])</div>
            <div class="stat-label">Float balance</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19 7-7 3 3-7 7-3-3z"></path><path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path></svg></div></div>
            <div class="stat-value">@money($stats['today_volume'])</div>
            <div class="stat-label">Volume today</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"></path></svg></div></div>
            <div class="stat-value">{{ $stats['today_count'] }}</div>
            <div class="stat-label">Transactions today</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div></div>
            <div class="stat-value">{{ $stats['count'] }} <span style="font-size:14px;color:var(--ink-soft);">· {{ $stats['failed'] }} failed/reversed</span></div>
            <div class="stat-label">Total transactions</div>
        </div>
    </div>

    <div class="tabs" role="tablist">
        <button type="button" class="tab-btn {{ $activeTab === 'transactions' ? 'active' : '' }}" data-tab="transactions" onclick="setNetworkTab('transactions', this)">Transactions <span class="tab-count">{{ $stats['count'] }}</span></button>
        @if ($manageable)
            <button type="button" class="tab-btn {{ $activeTab === 'messages' ? 'active' : '' }}" data-tab="messages" onclick="setNetworkTab('messages', this)">Messages <span class="tab-count">{{ $messages->count() }}</span></button>
            <button type="button" class="tab-btn {{ $activeTab === 'devices' ? 'active' : '' }}" data-tab="devices" onclick="setNetworkTab('devices', this)">Devices <span class="tab-count">{{ $devices->count() }}</span></button>
        @endif
    </div>

    <div id="pan-transactions" class="tab-panel {{ $activeTab === 'transactions' ? '' : 'hidden' }}">
        <form method="GET" action="{{ route('networks.show', $network) }}" id="filterForm">
            <div class="table-card">
                <div class="table-toolbar">
                    <input type="hidden" name="status" id="fStatus" value="{{ $filters['status'] ?? 'all' }}">
                    <div class="chip-filters">
                        @foreach (['all', 'completed', 'pending', 'failed', 'reversed'] as $st)
                            <button type="button" class="chip {{ ($filters['status'] ?? 'all') === $st ? 'active' : '' }}" data-status="{{ $st }}" onclick="setStatusFilter('{{ $st }}')">{{ ucfirst($st) }}</button>
                        @endforeach
                    </div>
                    <div class="table-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" name="q" placeholder="Search reference, customer…" value="{{ $filters['q'] ?? '' }}">
                    </div>
                </div>
                <div class="table-toolbar" style="border-bottom:none;padding-top:6px;">
                    <select name="type" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:13px;background:var(--white);color:var(--coffee-700);font-weight:600;">
                        <option value="all" {{ ($filters['type'] ?? 'all') === 'all' ? 'selected' : '' }}>All types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" {{ ($filters['type'] ?? '') === $type ? 'selected' : '' }}>{{ txn_type_label($type) }}</option>
                        @endforeach
                    </select>
                    <label style="font-size:12.5px;font-weight:700;color:var(--ink-soft);">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" onchange="this.form.submit()" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:10px;font-size:13px;background:var(--white);color:var(--coffee-700);font-weight:600;">
                    <label style="font-size:12.5px;font-weight:700;color:var(--ink-soft);">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" onchange="this.form.submit()" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:10px;font-size:13px;background:var(--white);color:var(--coffee-700);font-weight:600;">
                </div>
            </div>
        </form>

        <div class="table-card" style="margin-top:-24px;">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Commission</th>
                            <th>Status</th>
                            <th>Operator</th>
                        </tr>
                    </thead>
                    <tbody id="txnBody">
                        @forelse ($transactions as $txn)
                            <tr data-id="{{ $txn->id }}" data-search="{{ strtolower(($txn->reference ?? '').' '.($txn->customer_name ?? '').' '.($txn->customer_phone ?? '')) }}">
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
                                <td class="cell-title">@money($txn->amount)</td>
                                <td>@money($txn->commission)</td>
                                <td><span class="tag {{ status_badge($txn->status) }}">{{ ucfirst($txn->status) }}</span></td>
                                <td>
                                    <div class="cell-sub">{{ $txn->operator?->name ?? '—' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-state"><h4>No transactions found</h4><p>Try a different filter or date range, or process a {{ $network->name }} transaction.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($manageable)
        <div id="pan-messages" class="tab-panel {{ $activeTab === 'messages' ? '' : 'hidden' }}">
            <div class="table-card">
                <div class="table-toolbar" style="border:none;">
                    <strong style="font-size:14px;">SMS on {{ $network->name }}</strong>
                    <span class="cell-sub">Every message tagged to this network, most recent first.</span>
                    <a href="{{ route('sms.index', ['network' => $network->id]) }}" class="btn btn-ghost btn-sm" style="margin-left:auto;">Open Messages</a>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Device</th>
                                <th>Sender</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Customer</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($messages as $message)
                                <tr>
                                    <td class="cell-sub">{{ $message->server_received_at->format('H:i:s · d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('devices.show', $message->device_id) }}" class="cell-title">{{ $message->device?->name ?? '—' }}</a>
                                    </td>
                                    <td>{{ $message->sender }}</td>
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
                                        <span class="tag {{ sms_status_badge($message->processing_status, $message->is_duplicate, $message->processing_error) }}">
                                            {{ ucfirst(sms_status_label($message->processing_status, $message->is_duplicate, $message->processing_error)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" title="View full SMS" onclick="openNetworkMessage('{{ addslashes($message->message_body) }}')">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="empty-state"><h4>No SMS on this network yet</h4><p>Messages tagged {{ $network->name }} will appear here automatically.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="pan-devices" class="tab-panel {{ $activeTab === 'devices' ? '' : 'hidden' }}">
            <div class="table-card">
                <div class="table-toolbar" style="border:none;">
                    <strong style="font-size:14px;">Devices on {{ $network->name }}</strong>
                    <span class="cell-sub">Phones allowed to ingest SMS for this network.</span>
                    <a href="{{ route('devices.index', ['network' => $network->id]) }}" class="btn btn-ghost btn-sm" style="margin-left:auto;">Open Devices</a>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Code</th>
                                <th>Agent</th>
                                <th>Networks</th>
                                <th>Phone / SIM</th>
                                <th>Last heartbeat</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($devices as $device)
                                @php
                                    $dStatus = $device->displayedStatus();
                                    $dBadge = match ($dStatus) {
                                        'active' => 'tag-green',
                                        'pending' => 'tag-gold',
                                        'offline', 'revoked' => 'tag-grey',
                                        default => 'tag-red',
                                    };
                                    $deviceNetworks = $device->networks->isNotEmpty() ? $device->networks : collect([$device->network])->filter();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="cell-main">
                                            <div class="avatar" style="background:var(--terracotta-100);color:var(--terracotta-600);">{{ strtoupper(substr($device->name, 0, 2)) }}</div>
                                            <div>
                                                <div class="cell-title">{{ $device->name }}</div>
                                                <div class="cell-sub">{{ $device->model ?? $device->device_uid ?? '—' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span style="font-family:monospace;font-size:13px;font-weight:700;letter-spacing:1px;color:var(--coffee-700);">{{ $device->device_code }}</span></td>
                                    <td>
                                        <div class="cell-title">{{ $device->agent?->name ?? '—' }}</div>
                                        <div class="cell-sub">{{ $device->branch ?? '' }}</div>
                                    </td>
                                    <td>
                                        @forelse ($deviceNetworks as $nw)
                                            <span class="tag" style="background:{{ $nw->color }};color:#fff;margin:1px 2px 1px 0;">{{ $nw->name }}</span>
                                        @empty
                                            <span class="cell-sub">—</span>
                                        @endforelse
                                        @if ($device->lines->isNotEmpty())
                                            <div style="font-size:11.5px;color:var(--ink-soft);margin-top:4px;font-weight:600;">
                                                @foreach ($device->lines as $line)
                                                    <span style="margin-right:8px;">SIM {{ $line->sim_slot }}<span style="opacity:.6;"> · {{ $line->network?->name ?? '—' }}</span></span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="cell-title">{{ $device->phone_number ?? '—' }}</div>
                                        <div class="cell-sub">{{ $device->sim_number ?? '' }}</div>
                                    </td>
                                    <td class="cell-sub">{{ $device->last_heartbeat_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td><span class="tag {{ $dBadge }}">{{ ucfirst($dStatus) }}</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('devices.show', $device) }}" title="Device details">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty-state"><h4>No devices on this network</h4><p>Register a phone and assign {{ $network->name }} to it to start SMS capture.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- SMS body modal -->
    <div class="modal-backdrop" id="netSmsBodyModal">
        <div class="modal" style="max-width:560px;">
            <div class="modal-head">
                <h3>SMS content</h3>
                <button class="modal-close" onclick="closeModal('netSmsBodyModal')">✕</button>
            </div>
            <div class="modal-body">
                <pre id="netSmsBodyText" style="white-space:pre-wrap;word-break:break-word;font-family:inherit;font-size:13.5px;line-height:1.6;background:var(--paper);border:1px solid var(--line);border-radius:10px;padding:14px;margin:0;color:var(--coffee-700);"></pre>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const TYPE_LABEL = {
            deposit: 'Customer Deposit', withdrawal: 'Customer Withdrawal', send_money: 'Send Money',
            bill_payment: 'Bill Payment', airtime: 'Airtime', data: 'Data Bundle',
            bank_to_wallet: 'Bank to Wallet', wallet_to_bank: 'Wallet to Bank',
        };

        function setStatusFilter(status) {
            document.getElementById('fStatus').value = status;
            document.getElementById('filterForm').submit();
        }

        function setNetworkTab(tab, btn) {
            document.querySelectorAll('.tabs .tab-btn').forEach(b => b.classList.toggle('active', b === btn));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('hidden', p.id !== 'pan-' + tab));
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            history.replaceState({}, '', url);
        }

        function openNetworkMessage(body) {
            const el = document.getElementById('netSmsBodyText');
            if (el) {
                el.textContent = body;
                openModal('netSmsBodyModal');
            }
        }

        @php
            $jsonTxns = $transactions->map(fn ($t) => [
                'reference' => $t->reference,
                'provider_reference' => $t->provider_reference,
                'type' => $t->type,
                'customer_name' => $t->customer_name,
                'customer_phone' => $t->customer_phone,
                'amount' => (float) $t->amount,
                'fee' => (float) $t->fee,
                'commission' => (float) $t->commission,
                'status' => $t->status,
                'notes' => $t->notes,
                'created_at' => $t->created_at->format('d M Y H:i'),
                'operator' => $t->operator?->name,
            ])->values();
        @endphp
        const txnsData = @json($jsonTxns);

        function fmt(n) { return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }

        const qInput = document.querySelector('[id="pan-transactions"] input[name="q"]');
        if (qInput) {
            qInput.addEventListener('input', () => {
                const q = qInput.value.toLowerCase();
                document.querySelectorAll('#txnBody tr[data-id]').forEach(tr => {
                    tr.style.display = (!q || tr.dataset.search.includes(q)) ? 'table-row' : 'none';
                });
            });
        }

        bindRowClick('#txnBody tr[data-id]', tr => {
            const t = txnsData.find(x => x.reference === tr.querySelector('.cell-title').textContent.trim());
            if (!t) return [];
            return [
                ['Reference', t.reference],
                ['Provider ref', t.provider_reference || '—'],
                ['Date', t.created_at],
                ['Type', TYPE_LABEL[t.type] || t.type],
                ['Customer', t.customer_name || '—'],
                ['Phone', t.customer_phone],
                ['Amount', fmt(t.amount)],
                ['Fee', fmt(t.fee)],
                ['Commission', fmt(t.commission)],
                ['Status', { __html: statusBadgeHtml(t.status) }],
                ...(t.notes ? [['Notes', t.notes]] : []),
                ['Operator', t.operator || '—'],
            ];
        }, 'Transaction details');
    </script>
@endsection