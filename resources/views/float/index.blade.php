@extends('layouts.app')

@section('title', 'Cash & Float')

@section('content')
    <div class="view-head">
        <div>
            <h2>Cash &amp; Float</h2>
            <p class="sub">Manage mobile-money float on your networks and track cash moving in and out of the till.</p>
        </div>
        <div class="view-actions">
            <a href="{{ $isAdmin ? route('float.create', ['date' => $selectedDateEncrypted]) : route('float.create') }}" class="btn btn-primary">+ New float / cash entry</a>
        </div>
        @include('exports._export-modal', ['route' => $exportRoute, 'columns' => $exportColumns, 'title' => 'Float Transactions'])
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg></div><span class="stat-trend up">{{ $summary['networks'] }} networks</span></div>
            <div class="stat-value">@money($summary['totalFloat'])</div>
            <div class="stat-label">Total float balance</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg></div></div>
            <div class="stat-value">@money($summary['floatOut'])</div>
            <div class="stat-label">Float in circulation</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path></svg></div></div>
            <div class="stat-value">@money($summary['totalCash'])</div>
            <div class="stat-label">Cash at till</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money($summary['floatCapacity'])</div>
            <div class="stat-label">Combined float capacity</div>
        </div>
    </div>

    @if($isAdmin)
        <div class="panel">
            <div class="panel-head">
                <h3>All Days — Table</h3>
                <span class="tag tag-terracotta">{{ $openingsCount ?? 0 }} days</span>
            </div>
            <div class="panel-body" style="padding:0;">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Cash Opening</th>
                                <th>Float Total</th>
                                <th>Txs / Vol</th>
                                <th>Status</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $openingsTable = $openings ?? collect(); @endphp
                            @forelse($openingsTable as $op)
                            <tr>
                                <td>
                                    <span class="cell-title">{{ $op->opening_date->format('Y-m-d') }}</span>
                                    <span class="cell-sub">{{ $op->opening_date->format('l') }}</span>
                                </td>
                                <td class="cell-title">@money($op->cash_opening)</td>
                                <td>@money($op->totalFloatOpening())</td>
                                <td>{{ $op->total_transactions }} txs<br><span class="cell-sub">@money($op->total_volume)</span></td>
                                <td><span class="tag {{ $op->is_closed ? 'tag-grey' : 'tag-green' }}">{{ $op->is_closed ? 'Closed' : 'Open' }}</span></td>
                                <td style="white-space:nowrap; text-align:center;">
                                    <a href="{{ route('float.day', ['date' => \Illuminate\Support\Facades\Crypt::encryptString($op->opening_date->toDateString())]) }}" title="View day — only its page" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:inline-flex;align-items:center;justify-content:center;color:var(--ink);margin-right:4px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a href="{{ route('float.opening.edit', ['date' => \Illuminate\Support\Facades\Crypt::encryptString($op->opening_date->toDateString())]) }}" title="Edit opening" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:inline-flex;align-items:center;justify-content:center;color:var(--acacia-600);margin-right:4px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 1 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </a>
                                    <button type="button" onclick="deleteDay('{{ $op->opening_date->toDateString() }}', '{{ $op->opening_date->format('Y-m-d') }}', false)" title="Delete day" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:inline-flex;align-items:center;justify-content:center;color:var(--danger);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="empty-state">No days added yet — <a href="{{ route('float.opening.edit', ['date' => \Illuminate\Support\Facades\Crypt::encryptString(today()->toDateString())]) }}">Create opening</a></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="padding:10px 12px; background:var(--sand-50); border-top:1px solid var(--line); font-size:12px; color:var(--ink-soft);">
                    This is the <strong>index</strong> — all days list. Click <span style="display:inline-flex; vertical-align:middle; width:18px; height:18px; border:1px solid var(--line); border-radius:4px; align-items:center; justify-content:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></span> to enter single-day page (only that day opened, no other day details).
                    <a href="{{ route('float.days') }}" style="margin-left:8px; color:var(--terracotta-600); font-weight:700;">View dedicated Manage All Days page →</a>
                </div>
            </div>
        </div>
        @endif

        @if($viewDate->isSameDay(today()))
        <div class="panel">
            <div class="panel-head">
                <h3>Current Float Balances — Admin Direct Edit (Global Live)</h3>
                <span class="tag tag-gold">NetworkBalance · Live</span>
            </div>
            <div class="panel-body">
                <p style="font-size:12.5px; color:var(--ink-soft); margin-bottom:12px;">Edit <strong>live</strong> float per network and cash at till (Agent <code>cash_balance</code>). This changes <code>NetworkBalance.balance</code> and <code>Agent.cash_balance</code> immediately — use for corrections. For opening balances on a selected historical day, use “Edit opening” above or “Add additional cash” — live edit is hidden when viewing past dates.</p>
                <form method="POST" action="{{ route('float.balances.update') }}" data-float-balances>
                    @csrf
                    @method('PUT')
                    <div class="form-row">
                        <div class="field">
                            <label>Cash at till (Agent cash_balance) — Live</label>
                            <input type="number" name="cash_balance" value="{{ $summary['totalCash'] }}" step="0.01">
                        </div>
                    </div>
                    @foreach($allNetworks as $network)
                        @php $bal = $balances->where('network_id', $network->id)->first(); @endphp
                        <div class="form-row">
                            <div class="field">
                                <label><span class="net-dot" style="background:{{ $network->color }};"></span> {{ $network->name }} — Opening (for reference)</label>
                                <input type="number" name="balances[{{ $loop->index }}][opening_balance]" value="{{ $bal?->opening_balance ?? 0 }}" step="0.01">
                                <input type="hidden" name="balances[{{ $loop->index }}][network_id]" value="{{ $network->id }}">
                            </div>
                            <div class="field">
                                <label>{{ $network->name }} — Current Balance *</label>
                                <input type="number" name="balances[{{ $loop->index }}][balance]" value="{{ $bal?->balance ?? 0 }}" step="0.01" required>
                            </div>
                        </div>
                    @endforeach
                    <div style="display:flex; gap:10px; margin-top:12px;">
                        <button type="submit" class="btn btn-primary">Save balances</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if(!$isAdmin)
    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Network</th>
                        <th>Opening</th>
                        <th>Current balance</th>
                        <th>Variance</th>
                    </tr>
                </thead>
                <tbody id="floatBalRows">
                    @forelse ($balances as $balance)
                        <tr data-network="{{ $balance->network?->name }}"
                            data-color="{{ $balance->network?->color }}"
                            data-opening="{{ $balance->opening_balance }}"
                            data-balance="{{ $balance->balance }}">
                            <td>
                                <span class="net-dot" style="background:{{ $balance->network?->color }};"></span>
                                {{ $balance->network?->name }}
                            </td>
                            <td>@money($balance->opening_balance)</td>
                            <td class="cell-title">@money($balance->balance)</td>
                            <td>
                                @if ($balance->balance >= $balance->opening_balance)
                                    <span class="tag tag-green">+@money($balance->balance - $balance->opening_balance)</span>
                                @else
                                    <span class="tag tag-red">-@money($balance->opening_balance - $balance->balance)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state"><h4>No float balances</h4><p>Add your first float entry above.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(!$isAdmin)
    <div class="panel">
        <div class="panel-head">
            <h3>Float activity — filter by dates</h3>
            <span class="link">{{ request('from') || request('to') || request('date_filter') ? 'Filtered' : 'Latest 50' }}</span>
        </div>
        <form method="GET" action="{{ route('float.index') }}" style="padding:12px 16px; border-bottom:1px solid var(--line); display:flex; gap:8px; flex-wrap:wrap; align-items:end; background:var(--sand-50);">
            @if($isAdmin)
                <input type="hidden" name="date" value="{{ $selectedDateEncrypted }}">
            @endif
            <div class="field" style="margin-bottom:0;">
                <label style="font-size:11px;">From</label>
                <input type="date" name="from" value="{{ request('from') }}" style="padding:8px 10px; border:1.5px solid var(--line); border-radius:8px; font-size:13px;">
            </div>
            <div class="field" style="margin-bottom:0;">
                <label style="font-size:11px;">To</label>
                <input type="date" name="to" value="{{ request('to') }}" style="padding:8px 10px; border:1.5px solid var(--line); border-radius:8px; font-size:13px;">
            </div>
            <div class="field" style="margin-bottom:0;">
                <label style="font-size:11px;">Single date</label>
                <input type="date" name="date_filter" value="{{ request('date_filter') }}" style="padding:8px 10px; border:1.5px solid var(--line); border-radius:8px; font-size:13px;">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="{{ route('float.index', $isAdmin ? ['date' => $selectedDateEncrypted] : []) }}" class="btn btn-ghost btn-sm">Clear dates</a>
            @if(request('from') || request('to') || request('date_filter'))
                <span style="font-size:12px; color:var(--ink-soft);">Filtering {{ $floatTransactions->count() }} transactions</span>
            @endif
        </form>
        <div class="table-scroll">
            <table style="min-width:700px;">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Operator</th>
                        <th>Status</th>
                        @if($isAdmin)<th></th>@endif
                    </tr>
                </thead>
                <tbody id="floatActRows">
                    @forelse ($floatTransactions as $ft)
                        <tr data-ref="{{ $ft->reference }}"
                            data-date="{{ $ft->created_at->format('d M Y H:i') }}"
                            data-type="{{ $ft->type }}"
                            data-network="{{ $ft->network?->name }}"
                            data-color="{{ $ft->network?->color }}"
                            data-amount="{{ $ft->amount }}"
                            data-commission="{{ $ft->commission }}"
                            data-notes="{{ $ft->notes }}"
                            data-operator="{{ $ft->operator?->name }}">
                            <td>
                                <div class="cell-title">{{ $ft->reference }}</div>
                                <div class="cell-sub">{{ $ft->created_at->format('d M Y · H:i') }}</div>
                            </td>
                            <td>
                                <span class="tag {{ in_array($ft->type, ['float_topup', 'cash_in', 'cash_to_float'], true) ? 'tag-green' : 'tag-terracotta' }}">
                                    {{ txn_type_label($ft->type) }}
                                </span>
                            </td>
                            <td>
                                <span class="net-dot" style="background:{{ $ft->network?->color }};"></span>
                                {{ $ft->network?->name }}
                            </td>
                            <td class="cell-title">@money($ft->amount)</td>
                            <td>@money($ft->commission)</td>
                            <td>{{ $ft->operator?->name ?? '—' }}</td>
                            <td><span class="tag tag-green">Completed</span></td>
                            @if($isAdmin)
                                <td>
                                    <button type="button" onclick="event.stopPropagation(); deleteFloat({{ $ft->id }}, '{{ addslashes($ft->reference) }}')" title="Delete" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--danger);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAdmin ? 8 : 7 }}" class="empty-state">No float transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($isAdmin)
        <div class="modal-backdrop" id="deleteFloatModal">
            <div class="modal" style="max-width:440px;">
                <div class="modal-head">
                    <h3>Delete Float Entry</h3>
                    <button class="modal-close" onclick="closeModal('deleteFloatModal')">✕</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:13.5px;color:var(--ink-soft);">Delete <strong id="deleteFloatRef"></strong>? This will revert its NetworkBalance and cash effects for that date.</p>
                </div>
                <div class="modal-foot">
                    <button class="btn btn-ghost" onclick="closeModal('deleteFloatModal')">Cancel</button>
                    <button class="btn btn-danger" onclick="confirmDeleteFloat()">Delete</button>
                </div>
            </div>
        </div>
    @endif

@endsection

@section('scripts')
    <script>
        function floatFmt(n) { return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }

        bindRowClick('#floatBalRows tr[data-network]', tr => {
            const opening = parseFloat(tr.dataset.opening) || 0;
            const balance = parseFloat(tr.dataset.balance) || 0;
            const diff = balance - opening;
            const tagHtml = diff === 0
                ? '<span class="tag tag-green">Balanced</span>'
                : (diff > 0
                    ? '<span class="tag tag-green">+' + floatFmt(diff) + '</span>'
                    : '<span class="tag tag-red">-' + floatFmt(Math.abs(diff)) + '</span>');
            return [
                ['Network', tr.dataset.network ? { __html: `<span class="net-dot" style="background:${tr.dataset.color || '#999'};"></span> ${tr.dataset.network}` } : '—'],
                ['Opening balance', floatFmt(opening)],
                ['Current balance', floatFmt(balance)],
                ['Variance', { __html: tagHtml }],
            ];
        }, 'Float balance');

        const FLOAT_TYPE_LABEL = {
            float_topup: 'Float top-up', float_pull: 'Float pull',
            cash_in: 'Cash in', cash_out: 'Cash out', cash_to_float: 'Cash to Float',
        };

        bindRowClick('#floatActRows tr[data-ref]', tr => {
            return [
                ['Reference', tr.dataset.ref],
                ['Date', tr.dataset.date],
                ['Type', FLOAT_TYPE_LABEL[tr.dataset.type] || tr.dataset.type],
                ['Network', tr.dataset.network ? { __html: `<span class="net-dot" style="background:${tr.dataset.color || '#999'};"></span> ${tr.dataset.network}` } : '—'],
                ['Amount', floatFmt(parseFloat(tr.dataset.amount) || 0)],
                ['Commission', floatFmt(parseFloat(tr.dataset.commission) || 0)],
                ['Operator', tr.dataset.operator || '—'],
                ['Notes', tr.dataset.notes || '—'],
                ['Status', { __html: '<span class="tag tag-green">Completed</span>' }],
            ];
        }, 'Float transaction');

        document.querySelectorAll('[data-float-balances]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 600) });
            });
        });

        document.querySelectorAll('[data-cash-add]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 600) });
            });
        });

        let deleteFloatId = null;
        function deleteFloat(id, ref) {
            deleteFloatId = id;
            document.getElementById('deleteFloatRef').textContent = ref;
            openModal('deleteFloatModal');
        }
        async function confirmDeleteFloat() {
            if (!deleteFloatId) return;
            try {
                const resp = await fetch('/float/' + deleteFloatId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.success) {
                    toast(data.message || 'Float deleted', 'success');
                    closeModal('deleteFloatModal');
                    setTimeout(() => location.reload(), 400);
                } else {
                    toast(data.message || 'Failed to delete', 'error');
                }
            } catch (e) { toast('Network error', 'error'); }
        }

        async function deleteDay(dateStr, display, withTransactions) {
            if (!confirm('Delete opening for ' + display + (withTransactions ? ' + ALL transactions & float for that day?' : ' (opening only)?') + ' This cannot be undone.')) return;
            try {
                const url = '/float/opening/' + encodeURIComponent(dateStr) + (withTransactions ? '?with_transactions=1' : '');
                const resp = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.success) {
                    toast(data.message || 'Day deleted', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    toast(data.message || 'Failed to delete day', 'error');
                }
            } catch (e) { toast('Network error', 'error'); }
        }
    </script>
@endsection