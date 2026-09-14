@extends('layouts.app')

@section('title', $network->name.' · Transactions')

@section('content')
    <div class="view-head">
        <div>
            <h2><span class="net-dot" style="background:{{ $network->color }};"></span> {{ $network->name }} <span class="tag tag-terracotta">{{ $network->code }}</span></h2>
            <p class="sub">All transaction records for this network — every deposit, withdrawal, send money, bill and airtime processed.</p>
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

        const qInput = document.querySelector('input[name="q"]');
        qInput.addEventListener('input', () => {
            const q = qInput.value.toLowerCase();
            document.querySelectorAll('#txnBody tr[data-id]').forEach(tr => {
                tr.style.display = (!q || tr.dataset.search.includes(q)) ? 'table-row' : 'none';
            });
        });

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