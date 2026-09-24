@extends('layouts.app')

@section('title', 'Cash & Float')

@section('content')
    <div class="view-head">
        <div>
            <h2>Cash &amp; Float</h2>
            <p class="sub">Manage mobile-money float on your networks and track cash moving in and out of the till.</p>
        </div>
        <div class="view-actions">
            <a href="{{ $isAdmin ? route('float.create', ['date' => $selectedDate]) : route('float.create') }}" class="btn btn-primary">+ New float / cash entry</a>
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
        <div class="panel" style="border-left:3px solid var(--terracotta-600);">
            <div class="panel-head">
                <h3>Admin — Select Day & Opening Balances</h3>
                <span class="tag tag-terracotta">Edit any date</span>
            </div>
            <div class="panel-body">
                <form method="GET" action="{{ route('float.index') }}" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
                    <div class="field" style="margin-bottom:0;">
                        <label>Selected date</label>
                        <input type="date" name="date" value="{{ $selectedDate }}" max="{{ today()->toDateString() }}">
                    </div>
                    <button type="submit" class="btn btn-primary">Load day</button>
                    <a href="{{ route('float.index') }}" class="btn btn-ghost">Today</a>
                    <a href="{{ route('float.opening.edit', ['date' => $selectedDate]) }}" class="btn btn-ghost" style="border:1.5px solid var(--line);">Edit opening for {{ $viewDate->format('d M Y') }}</a>
                </form>
                @if($todayOpening)
                    <div style="margin-top:14px; padding:12px; background:var(--sand-100); border:1px solid var(--line); border-radius:8px; font-size:13px; line-height:1.6;">
                        <strong>Opening for {{ $viewDate->format('Y-m-d') }}:</strong> Cash <strong>@money($todayOpening->cash_opening)</strong> · Float total <strong>@money($todayOpening->totalFloatOpening())</strong> · Status <span class="tag {{ $todayOpening->is_closed ? 'tag-grey' : 'tag-green' }}">{{ $todayOpening->is_closed ? 'Closed' : 'Open' }}</span> · {{ $todayOpening->total_transactions }} txs · Vol @money($todayOpening->total_volume)
                        @if($todayOpening->notes)<br><span style="color:var(--ink-soft);">{{ $todayOpening->notes }}</span>@endif
                        <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                            @foreach($allNetworks as $net)
                                @php $amt = $todayOpening->float_openings[$net->id] ?? 0; @endphp
                                <span class="tag" style="background:var(--white); border:1px solid var(--line);"><span class="net-dot" style="background:{{ $net->color }};"></span> {{ $net->name }}: @money($amt)</span>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div style="margin-top:14px; padding:12px; background:var(--danger-100); border-radius:8px; font-size:13px;">No Daily Opening for <strong>{{ $viewDate->format('Y-m-d') }}</strong> — <a href="{{ route('float.opening.edit', ['date' => $selectedDate]) }}" class="btn btn-sm btn-primary" style="margin-left:8px;">Create / Add opening</a> <span style="color:var(--ink-soft);">You can still add float entries for this date; they will be counted via <code>created_at</code> for reports/reconciliation.</span></div>
                @endif
                {{-- Cash at till must reference previous day closing --}}
                @if(isset($summary['previousClosingCash']) || isset($summary['resolvedCashOpening']))
                    @php
                        $prevClosing = $summary['previousClosingCash'] ?? null;
                        $resolvedOpening = $summary['resolvedCashOpening'] ?? ($todayOpening ? $todayOpening->cash_opening : 0);
                        $prevDateStr = $viewDate->copy()->subDay()->format('Y-m-d');
                    @endphp
                    <div style="margin-top:12px; padding:12px; background:var(--white); border:1px solid var(--line); border-radius:8px; font-size:13px; line-height:1.6;">
                        <strong>Cash at till — references previous closing:</strong><br>
                        @if($prevClosing !== null)
                            <span style="color:var(--ink-soft);">Previous day {{ $prevDateStr }} closing: <strong>@money($prevClosing)</strong></span>
                            <span style="margin:0 6px;">→</span>
                            <span>Opening {{ $viewDate->format('Y-m-d') }}: <strong>@money($resolvedOpening)</strong></span>
                            @if(isset($summary['cashIn']) || isset($summary['cashOut']))
                                <span style="color:var(--ink-soft);"> (Cash in +@money($summary['cashIn'] ?? 0) − Out @money($summary['cashOut'] ?? 0) → Closing <strong>@money($summary['totalCash'])</strong>)</span>
                            @endif
                        @else
                            <span style="color:var(--ink-soft);">No previous closing found — opening defaults to <strong>@money($resolvedOpening)</strong> (set via Daily Opening or Agent balance)</span>
                        @endif
                        <div style="margin-top:8px; font-size:12px; color:var(--ink-soft);">Edit opening to change base, or add additional cash below (creates cash_in for this date, updates opening + live balance if today).</div>
                    </div>
                @endif
                {{-- Allow to add additional cash for this date --}}
                <div style="margin-top:12px; padding:12px; background:var(--acacia-50); border:1px solid var(--line); border-radius:8px;">
                    <strong style="font-size:13px;">Add additional cash at till for {{ $viewDate->format('Y-m-d') }}</strong>
                    <p style="font-size:12px; color:var(--ink-soft); margin:4px 0 8px;">References previous closing above — amount will be added to Daily Opening cash_opening for this date and booked as Float cash_in (visible in balance & reconciliation).</p>
                    <form method="POST" action="{{ route('float.cash.add') }}" data-cash-add style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
                        @csrf
                        <input type="hidden" name="date" value="{{ $viewDate->toDateString() }}">
                        <div class="field" style="margin-bottom:0; min-width:160px;">
                            <label>Amount (TZS) *</label>
                            <input type="number" name="amount" min="1" step="0.01" required placeholder="e.g. 50000">
                        </div>
                        <div class="field" style="margin-bottom:0; flex:1; min-width:200px;">
                            <label>Notes (optional)</label>
                            <input type="text" name="notes" maxlength="255" placeholder="Additional cash from bank / owner">
                        </div>
                        <button type="submit" class="btn btn-primary">+ Add cash</button>
                    </form>
                </div>
            </div>
        </div>

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

    <div class="panel">
        <div class="panel-head">
            <h3>Float activity — filter by dates</h3>
            <span class="link">{{ request('from') || request('to') || request('date_filter') ? 'Filtered' : 'Latest 50' }}</span>
        </div>
        <form method="GET" action="{{ route('float.index') }}" style="padding:12px 16px; border-bottom:1px solid var(--line); display:flex; gap:8px; flex-wrap:wrap; align-items:end; background:var(--sand-50);">
            @if($isAdmin)
                <input type="hidden" name="date" value="{{ $selectedDate }}">
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
            <a href="{{ route('float.index', $isAdmin ? ['date' => $selectedDate] : []) }}" class="btn btn-ghost btn-sm">Clear dates</a>
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
                            data-notes="{{ $ft->notes }}"
                            data-operator="{{ $ft->operator?->name }}">
                            <td>
                                <div class="cell-title">{{ $ft->reference }}</div>
                                <div class="cell-sub">{{ $ft->created_at->format('d M Y · H:i') }}</div>
                            </td>
                            <td>
                                <span class="tag {{ $ft->type === 'float_topup' || $ft->type === 'cash_in' ? 'tag-green' : 'tag-terracotta' }}">
                                    {{ $ft->type === 'cash_in' ? 'Cash in' : ($ft->type === 'cash_out' ? 'Cash out' : ($ft->type === 'float_topup' ? 'Float top-up' : 'Float pull')) }}
                                </span>
                            </td>
                            <td>
                                <span class="net-dot" style="background:{{ $ft->network?->color }};"></span>
                                {{ $ft->network?->name }}
                            </td>
                            <td class="cell-title">@money($ft->amount)</td>
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
                        <tr><td colspan="{{ $isAdmin ? 7 : 6 }}" class="empty-state">No float transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

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
            cash_in: 'Cash in', cash_out: 'Cash out',
        };

        bindRowClick('#floatActRows tr[data-ref]', tr => {
            return [
                ['Reference', tr.dataset.ref],
                ['Date', tr.dataset.date],
                ['Type', FLOAT_TYPE_LABEL[tr.dataset.type] || tr.dataset.type],
                ['Network', tr.dataset.network ? { __html: `<span class="net-dot" style="background:${tr.dataset.color || '#999'};"></span> ${tr.dataset.network}` } : '—'],
                ['Amount', floatFmt(parseFloat(tr.dataset.amount) || 0)],
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
    </script>
@endsection