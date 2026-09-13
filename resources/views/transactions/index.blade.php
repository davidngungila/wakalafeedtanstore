@extends('layouts.app')

@section('title', 'Transactions')

@section('content')
    <div class="view-head">
        <div>
            <h2>Transactions</h2>
            <p class="sub">Deposits, withdrawals, send money, bills, airtime and bank transfers across all networks.</p>
        </div>
        <div class="view-actions">
            <button class="btn btn-primary" onclick="openModal('processTxnModal')">+ Process transaction</button>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19 7-7 3 3-7 7-3-3z"></path><path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path></svg></div></div>
            <div class="stat-value">@money($todayTotals['deposits'])</div>
            <div class="stat-label">Deposits today</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 12 4l9 7"></path><path d="M5 10v10h14V10"></path></svg></div></div>
            <div class="stat-value">@money($todayTotals['withdrawals'])</div>
            <div class="stat-label">Withdrawals today</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money($todayTotals['volume'])</div>
            <div class="stat-label">Volume today</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg></div></div>
            <div class="stat-value">{{ $todayTotals['count'] }} <span style="font-size:14px;color:var(--ink-soft);">· {{ $todayTotals['failed'] }} failed/reversed</span></div>
            <div class="stat-label">Transactions today</div>
        </div>
    </div>

    <form method="GET" action="{{ route('transactions.index') }}" id="filterForm">
        <div class="table-card">
            <div class="table-toolbar">
                <input type="hidden" name="status" id="fStatus" value="{{ $filters['status'] ?? 'all' }}">
                <input type="hidden" name="type" id="fType" value="{{ $filters['type'] ?? 'all' }}">
                <div class="chip-filters" id="statusChips">
                    <button type="button" class="chip {{ ($filters['status'] ?? 'all') === 'all' ? 'active' : '' }}" data-status="all" onclick="setStatusFilter('all')">All</button>
                    <button type="button" class="chip {{ ($filters['status'] ?? '') === 'completed' ? 'active' : '' }}" data-status="completed" onclick="setStatusFilter('completed')">Completed</button>
                    <button type="button" class="chip {{ ($filters['status'] ?? '') === 'pending' ? 'active' : '' }}" data-status="pending" onclick="setStatusFilter('pending')">Pending</button>
                    <button type="button" class="chip {{ ($filters['status'] ?? '') === 'failed' ? 'active' : '' }}" data-status="failed" onclick="setStatusFilter('failed')">Failed</button>
                    <button type="button" class="chip {{ ($filters['status'] ?? '') === 'reversed' ? 'active' : '' }}" data-status="reversed" onclick="setStatusFilter('reversed')">Reversed</button>
                </div>
                <div class="table-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" placeholder="Search…" oninput="filterTransactionRows(this.value)">
                </div>
            </div>
            <div class="table-toolbar" style="border-bottom:none;padding-top:6px;">
                <select name="network" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:13px;background:var(--white);color:var(--coffee-700);font-weight:600;">
                    <option value="all" {{ ($filters['network'] ?? 'all') === 'all' ? 'selected' : '' }}>All networks</option>
                    @foreach ($combos['networks'] as $network)
                        <option value="{{ $network->id }}" {{ ($filters['network'] ?? '') == $network->id ? 'selected' : '' }}>{{ $network->name }}</option>
                    @endforeach
                </select>
                <select name="type" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:13px;background:var(--white);color:var(--coffee-700);font-weight:600;">
                    <option value="all" {{ ($filters['type'] ?? 'all') === 'all' ? 'selected' : '' }}>All types</option>
                    @foreach ($combos['types'] as $type)
                        <option value="{{ $type }}" {{ ($filters['type'] ?? '') === $type ? 'selected' : '' }}>{{ txn_type_label($type) }}</option>
                    @endforeach
                </select>
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
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="transactionsBody">
                    @forelse ($transactions as $txn)
                        <tr data-id="{{ $txn->id }}" data-search="{{ strtolower(($txn->reference ?? '').' '.($txn->customer_name ?? '').' '.($txn->customer_phone ?? '')) }}" data-status="{{ $txn->status }}">
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
                                <span class="net-dot" style="background:{{ $txn->network?->color }};"></span>
                                {{ $txn->network?->name }}
                            </td>
                            <td class="cell-title">@money($txn->amount)</td>
                            <td>@money($txn->commission)</td>
                            <td><span class="tag {{ status_badge($txn->status) }}">{{ ucfirst($txn->status) }}</span></td>
                            <td>
                                <div class="row-actions">
                                    <button onclick="viewTxn({{ $txn->id }})" title="View receipt">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"></path></svg>
                                    </button>
                                    @if (! is_cashier())
                                        <button class="warn" onclick="openReverseModal({{ $txn->id }}, '{{ $txn->reference }}')" title="Reverse">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 2 4 4-4 4"></path><path d="M3 11v-1a4 4 0 0 1 4-4h14"></path><path d="m7 22-4-4 4-4"></path><path d="M21 13v1a4 4 0 0 1-4 4H3"></path></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state"><h4>No transactions found</h4><p>Try a different filter or process a new transaction.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Process transaction modal -->
    <div class="modal-backdrop" id="processTxnModal">
        <div class="modal">
            <div class="modal-head">
                <h3>Process transaction</h3>
                <button class="modal-close" onclick="closeModal('processTxnModal')">✕</button>
            </div>
            <form action="{{ route('transactions.store') }}" method="POST" data-process-txn>
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="field">
                            <label>Network</label>
                            <select name="network_id" required>
                                @foreach ($combos['networks'] as $network)
                                    <option value="{{ $network->id }}">{{ $network->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Transaction type</label>
                            <select name="type">
                            <option value="deposit">Customer Deposit</option>
                            <option value="withdrawal">Customer Withdrawal</option>
                            <option value="send_money">Send Money</option>
                            <option value="bill_payment">Bill Payment</option>
                            <option value="airtime">Airtime</option>
                            <option value="data">Data Bundle</option>
                            <option value="bank_to_wallet">Bank to Wallet</option>
                            <option value="wallet_to_bank">Wallet to Bank</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Customer name</label>
                            <input type="text" name="customer_name" placeholder="e.g. Juma Athumani">
                        </div>
                        <div class="field">
                            <label>Customer phone</label>
                            <input type="text" name="customer_phone" placeholder="07xxxxxxxx" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Amount (TZS)</label>
                        <input type="number" name="amount" min="1" step="any" placeholder="e.g. 100000" required>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('processTxnModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process transaction</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Receipt modal -->
    <div class="modal-backdrop" id="receiptModal">
        <div class="modal" style="max-width:440px;">
            <div class="modal-head">
                <h3>Transaction receipt</h3>
                <button class="modal-close" onclick="closeModal('receiptModal')">✕</button>
            </div>
            <div class="modal-body" id="receiptBody"></div>
            <div class="modal-foot">
                <button class="btn btn-ghost" onclick="window.print()">Print</button>
                <button class="btn btn-primary" onclick="closeModal('receiptModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Reverse modal -->
    <div class="modal-backdrop" id="reverseModal">
        <div class="modal" style="max-width:440px;">
            <div class="modal-head">
                <h3>Reverse transaction</h3>
                <button class="modal-close" onclick="closeModal('reverseModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13.5px;color:var(--ink-soft);line-height:1.6;margin-bottom:14px;">Reversing <strong id="reverseRef" style="color:var(--coffee-900);"></strong> will restore the float and cash balances to their previous state.</p>
                <div class="field">
                    <label>Reason for reversal</label>
                    <select id="reverseReason" style="width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:var(--radius-sm);background:var(--white);font-size:14px;">
                        <option>Customer dispute - duplicate charge</option>
                        <option>Incorrect amount charged</option>
                        <option>Wrong network selected</option>
                        <option>Wong customer/counterfoil</option>
                        <option>System error</option>
                        <option>Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" onclick="closeModal('reverseModal')">Cancel</button>
                <button class="btn btn-danger" onclick="doReverse()">Reverse transaction</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const authUser = @json(auth()->user()->name);
        @php
            $jsonTxns = $transactions->map(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'type' => $t->type,
                'customer_name' => $t->customer_name,
                'customer_phone' => $t->customer_phone,
                'amount' => (float) $t->amount,
                'fee' => (float) $t->fee,
                'commission' => (float) $t->commission,
                'status' => $t->status,
                'provider_reference' => $t->provider_reference,
                'notes' => $t->notes,
                'reversal_reason' => $t->reversal_reason,
                'network' => $t->network?->name,
                'network_color' => $t->network?->color,
                'created_at' => $t->created_at->format('d M Y H:i'),
                'operator' => $t->operator?->name,
            ])->values();
        @endphp
        const transactionsData = @json($jsonTxns);

        let reverseTarget = null;

        function setStatusFilter(status) {
            document.getElementById('fStatus').value = status;
            document.getElementById('filterForm').submit();
        }

        function filterTransactionRows(q) {
            q = q.toLowerCase();
            document.querySelectorAll('#transactionsBody tr[data-id]').forEach(tr => {
                tr.style.display = (!q || tr.dataset.search.includes(q)) ? 'table-row' : 'none';
            });
        }

        const TYPE_LABEL = {
            deposit: 'Customer Deposit', withdrawal: 'Customer Withdrawal', send_money: 'Send Money',
            bill_payment: 'Bill Payment', airtime: 'Airtime', data: 'Data Bundle',
            bank_to_wallet: 'Bank to Wallet', wallet_to_bank: 'Wallet to Bank',
        };

        function fmt(n) { return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }

        function viewTxn(id) {
            const t = transactionsData.find(x => Number(x.id) === Number(id));
            if (!t) return;
            const statusTag = `<span class="tag ${t.status === 'completed' ? 'tag-green' : (t.status === 'reversed' ? 'tag-grey' : (t.status === 'failed' ? 'tag-red' : 'tag-gold'))}">${t.status[0].toUpperCase() + t.status.slice(1)}</span>`;
            document.getElementById('receiptBody').innerHTML = `
                <div class="receipt">
                    <div class="receipt-row"><span>Reference</span><b>${t.reference}</b></div>
                    <div class="receipt-row"><span>Provider ref</span><b>${t.provider_reference || '—'}</b></div>
                    <div class="receipt-row"><span>Date</span><b>${t.created_at}</b></div>
                    <div class="receipt-row"><span>Type</span><b>${TYPE_LABEL[t.type] || t.type}</b></div>
                    <div class="receipt-row"><span>Network</span><b><span class="net-dot" style="background:${t.network_color || '#999'};"></span> ${t.network || '—'}</b></div>
                    <div class="receipt-row"><span>Customer</span><b>${t.customer_name || '—'}</b></div>
                    <div class="receipt-row"><span>Phone</span><b>${t.customer_phone}</b></div>
                    <div class="receipt-row"><span>Amount</span><b>${fmt(t.amount)}</b></div>
                    <div class="receipt-row"><span>Fee</span><b>${fmt(t.fee)}</b></div>
                    <div class="receipt-row"><span>Commission</span><b>${fmt(t.commission)}</b></div>
                    <div class="receipt-row"><span>Status</span><b>${statusTag}</b></div>
                    ${t.reversal_reason ? `<div class="receipt-row"><span>Reason</span><b>${t.reversal_reason}</b></div>` : ''}
                    ${t.notes ? `<div class="receipt-row"><span>Notes</span><b>${t.notes}</b></div>` : ''}
                    <div class="receipt-row"><span>Operator</span><b>${t.operator || authUser}</b></div>
                </div>`;
            openModal('receiptModal');
        }

        function openReverseModal(id, reference) {
            reverseTarget = id;
            document.getElementById('reverseRef').textContent = reference;
            openModal('reverseModal');
        }

        async function doReverse() {
            if (!reverseTarget) return;
            const reason = document.getElementById('reverseReason').value;
            try {
                const body = new URLSearchParams('_token=' + CSRF_TOKEN + '&reason=' + encodeURIComponent(reason));
                const response = await fetch(`/transactions/${reverseTarget}/reverse`, {
                    method: 'PUT',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body,
                });
                const data = await response.json();
                if (data.success) { toast(data.message, 'success'); setTimeout(() => location.reload(), 600); }
                else { toast(data.message || 'Reversal failed.', 'error'); }
            } catch (err) { console.error(err); toast('Something went wrong!', 'error'); }
            closeModal('reverseModal');
        }

        document.querySelectorAll('[data-process-txn]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 600) });
            });
        });

        bindRowClick('#transactionsBody tr[data-id]', tr => {
            const t = transactionsData.find(x => Number(x.id) === Number(tr.dataset.id));
            if (!t) return [];
            return [
                ['Reference', t.reference],
                ['Provider ref', t.provider_reference || '—'],
                ['Date', t.created_at],
                ['Type', TYPE_LABEL[t.type] || t.type],
                ['Network', t.network ? { __html: `<span class="net-dot" style="background:${t.network_color || '#999'};"></span> ${t.network}` } : '—'],
                ['Customer', t.customer_name || '—'],
                ['Phone', t.customer_phone],
                ['Amount', fmt(t.amount)],
                ['Fee', fmt(t.fee)],
                ['Commission', fmt(t.commission)],
                ['Status', { __html: statusBadgeHtml(t.status) }],
                ...(t.reversal_reason ? [['Reversal reason', t.reversal_reason]] : []),
                ...(t.notes ? [['Notes', t.notes]] : []),
                ['Operator', t.operator || authUser],
            ];
        }, 'Transaction details');
    </script>
@endsection