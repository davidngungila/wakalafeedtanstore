@extends('layouts.app')

@section('title', 'Transactions')

@section('head')
    <style>
        @page { margin: 6mm; }
        .rc-receipt{
            background:var(--white);border:1px dashed var(--coffee-300);border-radius:8px;
            padding:24px 28px;font-family:'Courier New',ui-monospace,monospace;font-size:13px;
            line-height:1.6;color:#1a1a1a;box-shadow:var(--shadow-sm);margin:0 auto;
        }
        .rc-brand{text-align:center;}
        .rc-brand strong{display:block;font-size:16px;letter-spacing:.02em;color:var(--coffee-900);}
        .rc-brand span{display:block;font-size:10.5px;letter-spacing:.2em;text-transform:uppercase;color:var(--ink-soft);margin-top:2px;}
        .rc-rule{border-top:1px dashed #cdbfa8;margin:13px 0;}
        .rc-title{text-align:center;font-size:10.5px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:var(--coffee-700);}
        .rc-subtitle{text-align:center;font-size:12.5px;margin-top:1px;}
        .rc-row{display:flex;justify-content:space-between;gap:14px;padding:3px 0;align-items:baseline;}
        .rc-row span{color:var(--ink-soft);}
        .rc-row b{text-align:right;color:#1a1a1a;font-weight:700;}
        .rc-amount{text-align:center;padding:4px 0;}
        .rc-amount span{display:block;font-size:10px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:var(--ink-soft);}
        .rc-amount b{display:block;font-size:24px;color:var(--coffee-900);margin-top:2px;}
        .rc-foot{text-align:center;font-size:11px;letter-spacing:.05em;color:var(--ink-soft);}

        @media print {
            body * { visibility: hidden; }
            #receiptBody .rc-receipt, #receiptBody .rc-receipt * { visibility: visible; }
            #receiptBody .rc-receipt {
                position: fixed; left: 0; top: 0; width: 100%; max-width: 80mm;
                margin: 0; border: 1px dashed #999; box-shadow: none;
            }
        }
    </style>
@endsection

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
                        <th>Running Cash</th>
                        <th>Running Float</th>
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
                            <td style="font-weight:600;">{{ $txn->running_cash_balance !== null ? money($txn->running_cash_balance) : '—' }}</td>
                            <td style="font-weight:600;">{{ $txn->running_float_balance !== null ? money($txn->running_float_balance) : '—' }}</td>
                            <td><span class="tag {{ status_badge($txn->status) }}">{{ ucfirst($txn->status) }}</span></td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('transactions.receipt', $txn) }}" title="View receipt" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--coffee-700);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14.5px;height:14.5px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"></path></svg>
                                    </a>
                                    @if (! is_cashier())
                                        <button type="button" class="warn js-reverse-txn" data-id="{{ $txn->id }}" data-ref="{{ $txn->reference }}" title="Reverse">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m17 2 4 4-4 4"></path><path d="M3 11v-1a4 4 0 0 1 4-4h14"></path><path d="m7 22-4-4 4-4"></path><path d="M21 13v1a4 4 0 0 1-4 4H3"></path></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="empty-state"><h4>No transactions found</h4><p>Try a different filter or process a new transaction.</p></td></tr>
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
        <div class="popup" style="max-width:440px; width:100%; margin:auto;">
            <div class="modal-head">
                <h3>Transaction receipt</h3>
                <button class="modal-close" onclick="closeModal('receiptModal')">✕</button>
            </div>
            <div class="modal-body" id="receiptBody"></div>
            <div class="modal-foot">
                <button class="btn btn-ghost" onclick="window.print()">Print receipt</button>
                <button class="btn btn-primary" onclick="closeModal('receiptModal')">Close</button>
            </div>
        </div>
    </div>
    <style>
        /* Receipt as centered popup - overrides drawer transform */
        #receiptModal.show { display:flex !important; align-items:center; justify-content:center; }
        #receiptModal .popup { max-height:90vh; overflow:hidden; display:flex; flex-direction:column; }
        #receiptModal .popup .modal-body { max-height:60vh; overflow-y:auto; }
    </style>

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
                'running_cash_balance' => $t->running_cash_balance !== null ? (float) $t->running_cash_balance : null,
                'running_float_balance' => $t->running_float_balance !== null ? (float) $t->running_float_balance : null,
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
            try {
                console.log('viewTxn called', id, 'data len', transactionsData.length);
                const t = transactionsData.find(x => String(x.id) === String(id));
                if (!t) { console.warn('viewTxn not found', id, transactionsData); toast('Transaction not found in local data. Please reload.', 'error'); return; }
                const safe = (v) => v === null || v === undefined || v === '' ? '—' : v;
                const bodyEl = document.getElementById('receiptBody');
                if (!bodyEl) { toast('Receipt container missing', 'error'); return; }
                bodyEl.innerHTML = `
                    <div class="rc-receipt">
                        <div class="rc-brand">
                            <strong>Wakala Feedtan Store</strong>
                            <span>Mobile Money Services</span>
                        </div>
                        <div class="rc-rule"></div>
                        <div class="rc-title">Transaction Receipt</div>
                        <div class="rc-subtitle">${TYPE_LABEL[t.type] || t.type}</div>
                        <div class="rc-rule"></div>
                        <div class="rc-row"><span>Reference</span><b>${safe(t.reference)}</b></div>
                        <div class="rc-row"><span>Provider ref</span><b>${safe(t.provider_reference)}</b></div>
                        <div class="rc-row"><span>Date</span><b>${safe(t.created_at)}</b></div>
                        <div class="rc-row"><span>Network</span><b><span class="net-dot" style="background:${t.network_color || '#999'};"></span>&nbsp;${safe(t.network)}</b></div>
                        <div class="rc-row"><span>Customer</span><b>${safe(t.customer_name)}</b></div>
                        <div class="rc-row"><span>Phone</span><b>${safe(t.customer_phone)}</b></div>
                        <div class="rc-rule"></div>
                        <div class="rc-amount"><span>Amount</span><b>${fmt(t.amount ?? 0)}</b></div>
                        <div class="rc-row"><span>Fee</span><b>${fmt(t.fee ?? 0)}</b></div>
                        <div class="rc-row"><span>Commission</span><b>${fmt(t.commission ?? 0)}</b></div>
                        <div class="rc-row"><span>Running Cash</span><b>${t.running_cash_balance === null || t.running_cash_balance === undefined ? '—' : fmt(t.running_cash_balance)}</b></div>
                        <div class="rc-row"><span>Running Float</span><b>${t.running_float_balance === null || t.running_float_balance === undefined ? '—' : fmt(t.running_float_balance)}</b></div>
                        <div class="rc-row"><span>Status</span><b>${String(t.status || 'unknown').toUpperCase()}</b></div>
                        ${t.reversal_reason ? `<div class="rc-row"><span>Reason</span><b>${t.reversal_reason}</b></div>` : ''}
                        ${t.notes ? `<div class="rc-row"><span>Notes</span><b>${t.notes}</b></div>` : ''}
                        <div class="rc-rule"></div>
                        <div class="rc-row"><span>Operator</span><b>${safe(t.operator) || safe(authUser)}</b></div>
                        <div class="rc-rule"></div>
                        <div class="rc-foot">Thank you for using Wakala Feedtan Store</div>
                    </div>`;
                console.log('receipt HTML built, opening modal, body len', bodyEl.innerHTML.length);
                const modalEl = document.getElementById('receiptModal');
                console.log('modalEl before', modalEl, 'class', modalEl?.className, 'inner', modalEl?.querySelector('.popup')?.className);
                openModal('receiptModal');
                console.log('modalEl after open', modalEl?.className, 'zIndex', modalEl?.style.zIndex);
                // fallback: ensure modal is visible even if CSS fails - force popup visible
                if (modalEl && !modalEl.classList.contains('show')) {
                    console.warn('modal show class not added, forcing');
                    modalEl.classList.add('show');
                }
                if (modalEl) {
                    modalEl.style.display = 'flex';
                    const inner = modalEl.querySelector('.popup') || modalEl.querySelector('.modal');
                    if (inner) {
                        console.log('inner before transform', inner.style.transform, getComputedStyle(inner).transform);
                        inner.style.transform = 'none';
                        inner.style.position = 'relative';
                        inner.style.right = 'auto';
                        console.log('inner after', inner.style.transform);
                    }
                    // verify visibility after 50ms
                    setTimeout(() => {
                        const rect = modalEl.getBoundingClientRect();
                        const innerRect = modalEl.querySelector('.popup')?.getBoundingClientRect();
                        console.log('modal rect', rect, 'inner rect', innerRect, 'computed display', getComputedStyle(modalEl).display);
                        if (!innerRect || innerRect.width === 0) {
                            console.error('popup still not visible, showing alert fallback');
                            alert(bodyEl.innerText || 'Receipt ready but modal hidden - check console');
                        }
                    }, 100);
                }
            } catch (e) {
                console.error('viewTxn error', e);
                toast('Failed to render receipt: ' + (e.message || 'unknown'), 'error');
            }
        }
        // expose globally for inline handlers and ensure delegated listeners work
        window.viewTxn = viewTxn;
        window.openReverseModal = openReverseModal;

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

        // Delegated handler for reverse buttons
        document.querySelectorAll('.js-reverse-txn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openReverseModal(Number(btn.dataset.id), btn.dataset.ref);
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