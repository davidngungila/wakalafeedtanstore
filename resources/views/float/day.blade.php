@extends('layouts.app')

@section('title', 'Day ' . $viewDate->format('Y-m-d') . ' — Float')

@section('content')
    <div class="view-head">
        <div>
            <h2>Day {{ $viewDate->format('Y-m-d') }} — {{ $viewDate->format('l') }}</h2>
            <p class="sub">Only this day opened — cash & float for {{ $selectedDate }} alone. No other day details.</p>
        </div>
        <div class="view-actions" style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('float.days') }}" class="btn btn-ghost">← All Days (index)</a>
            <a href="{{ route('float.index', ['date' => $selectedDateEncrypted]) }}" class="btn btn-ghost">View in Float Filter</a>
            <a href="{{ route('float.create', ['date' => $selectedDateEncrypted, 'type' => 'cash_to_float']) }}" class="btn btn-primary">Cash → Float</a>
            <a href="{{ route('float.opening.edit', ['date' => $selectedDateEncrypted]) }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 1 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Edit Opening
            </a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg></div><span class="stat-trend up">{{ $summary['networks'] }} networks</span></div>
            <div class="stat-value">@money($summary['totalFloat'])</div>
            <div class="stat-label">Total float balance (this day)</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle></svg></div></div>
            <div class="stat-value">@money($summary['totalCash'])</div>
            <div class="stat-label">Cash at till (this day)</div>
            @if(isset($summary['previousClosingCash']))<div style="font-size:11px; color:var(--ink-soft);">Prev closing @money($summary['previousClosingCash']) → Opening @money($summary['resolvedCashOpening'])</div>@endif
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg></div></div>
            <div class="stat-value">@money($summary['floatOut'])</div>
            <div class="stat-label">Float in circulation (this day)</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money($summary['floatCapacity'])</div>
            <div class="stat-label">Combined capacity (this day)</div>
        </div>
    </div>

    <div class="panel" style="border-left:3px solid var(--terracotta-600);">
        <div class="panel-head">
            <h3>Opening for {{ $viewDate->format('Y-m-d') }}</h3>
            <span class="tag {{ $todayOpening && $todayOpening->is_closed ? 'tag-grey' : 'tag-green' }}">{{ $todayOpening && $todayOpening->is_closed ? 'Closed' : 'Open' }}</span>
        </div>
        <div class="panel-body">
            @if($todayOpening)
                <div style="padding:12px; background:var(--sand-100); border:1px solid var(--line); border-radius:8px; font-size:13px; line-height:1.6;">
                    <strong>Opening:</strong> Cash <strong>@money($todayOpening->cash_opening)</strong> · Float total <strong>@money($todayOpening->totalFloatOpening())</strong> · Status <span class="tag {{ $todayOpening->is_closed ? 'tag-grey' : 'tag-green' }}">{{ $todayOpening->is_closed ? 'Closed' : 'Open' }}</span> · {{ $todayOpening->total_transactions }} txs · Vol @money($todayOpening->total_volume)
                    @if($todayOpening->notes)<br><span style="color:var(--ink-soft);">{{ $todayOpening->notes }}</span>@endif
                    <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                        @foreach($allNetworks as $net)
                            @php $amt = $todayOpening->float_openings[$net->id] ?? 0; @endphp
                            <span class="tag" style="background:var(--white); border:1px solid var(--line);"><span class="net-dot" style="background:{{ $net->color }};"></span> {{ $net->name }}: @money($amt)</span>
                        @endforeach
                    </div>
                </div>
                <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
                    <a href="{{ route('float.opening.edit', ['date' => $selectedDateEncrypted]) }}" class="btn btn-primary btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 1 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        Edit Opening for this day
                    </a>
                    <a href="{{ route('daily-opening.show', $todayOpening) }}" class="btn btn-ghost btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        View Daily Opening
                    </a>
                    <button type="button" onclick="deleteDay('{{ $selectedDate }}', '{{ $viewDate->format('Y-m-d') }}', false)" class="btn btn-ghost btn-sm" style="color:var(--danger); border:1px solid var(--line); display:inline-flex; align-items:center; gap:6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        Delete Day (opening only)
                    </button>
                </div>
            @else
                <div style="padding:12px; background:var(--danger-100); border-radius:8px; font-size:13px;">No Daily Opening for <strong>{{ $selectedDate }}</strong> — <a href="{{ route('float.opening.edit', ['date' => $selectedDateEncrypted]) }}" class="btn btn-sm btn-primary" style="margin-left:8px;">Create Opening for this day</a></div>
            @endif
            @if(isset($summary['previousClosingCash']))
                @php $prevDateStr = $viewDate->copy()->subDay()->format('Y-m-d'); @endphp
                <div style="margin-top:12px; padding:12px; background:var(--white); border:1px solid var(--line); border-radius:8px; font-size:13px;">
                    <strong>Cash at till — references previous closing:</strong><br>
                    <span style="color:var(--ink-soft);">Previous day {{ $prevDateStr }} closing: <strong>@money($summary['previousClosingCash'])</strong></span>
                    <span style="margin:0 6px;">→</span>
                    <span>Opening {{ $viewDate->format('Y-m-d') }}: <strong>@money($summary['resolvedCashOpening'])</strong></span>
                    <span style="color:var(--ink-soft);"> (Cash in +@money($summary['cashIn'] ?? 0) − Out @money($summary['cashOut'] ?? 0) → Closing <strong>@money($summary['totalCash'])</strong>)</span>
                </div>
            @endif
            <div style="margin-top:12px; padding:12px; background:var(--acacia-50); border:1px solid var(--line); border-radius:8px;">
                <strong style="font-size:13px;">Add additional cash at till for {{ $viewDate->format('Y-m-d') }} (only this day)</strong>
                <form method="POST" action="{{ route('float.cash.add') }}" data-cash-add style="display:flex; gap:8px; align-items:end; flex-wrap:wrap; margin-top:8px;">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <div class="field" style="margin-bottom:0; min-width:160px;">
                        <label>Amount (TZS) *</label>
                        <input type="number" name="amount" min="1" step="0.01" required placeholder="e.g. 50000">
                    </div>
                    <div class="field" style="margin-bottom:0; flex:1; min-width:200px;">
                        <label>Notes</label>
                        <input type="text" name="notes" maxlength="255" placeholder="Additional cash">
                    </div>
                    <button type="submit" class="btn btn-primary">+ Add cash to this day</button>
                </form>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Network</th>
                        <th>Opening (this day)</th>
                        <th>Current balance (this day)</th>
                        <th>Variance (this day)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balances as $balance)
                        <tr>
                            <td><span class="net-dot" style="background:{{ $balance->network?->color }};"></span> {{ $balance->network?->name }}</td>
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
                        <tr><td colspan="4" class="empty-state">No float balances for this day.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Transactions — Only {{ $selectedDate }}</h3>
            <a href="{{ route('transactions.index', ['date' => $selectedDateEncrypted]) }}" class="btn btn-ghost btn-sm">View all for this day</a>
        </div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Time</th><th>Reference</th><th>Type</th><th>Network</th><th>Amount</th><th>Commission</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($dayTransactions as $t)
                    <tr>
                        <td>{{ $t->created_at->format('H:i:s') }}</td>
                        <td>{{ $t->reference }}</td>
                        <td>{{ txn_type_label($t->type) }}</td>
                        <td>{{ $t->network?->name }}</td>
                        <td>@money($t->amount)</td>
                        <td>@money($t->commission)</td>
                        <td><span class="tag tag-green">{{ $t->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="empty-state">No transactions for {{ $selectedDate }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Float Movements — Only {{ $selectedDate }}</h3>
        </div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Reference</th><th>Type</th><th>Network</th><th>Amount</th><th>Commission</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($floatTransactions as $ft)
                    <tr>
                        <td>{{ $ft->reference }}<br><span class="cell-sub">{{ $ft->created_at->format('H:i') }}</span></td>
                        <td><span class="tag {{ in_array($ft->type, ['float_topup', 'cash_in', 'cash_to_float'], true) ? 'tag-green' : 'tag-terracotta' }}">{{ txn_type_label($ft->type) }}</span></td>
                        <td>{{ $ft->network?->name }}</td>
                        <td>@money($ft->amount)</td>
                        <td>@money($ft->commission)</td>
                        <td><span class="tag tag-green">Completed</span></td>
                    </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No float movements for {{ $selectedDate }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-cash-add]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 600) });
            });
        });
        async function deleteDay(dateStr, display, withTransactions) {
            if (!confirm('Delete opening for ' + display + (withTransactions ? ' + ALL transactions & float for that day?' : ' (opening only)?') + ' This cannot be undone.')) return;
            try {
                const url = '/float/opening/' + encodeURIComponent(dateStr) + (withTransactions ? '?with_transactions=1' : '');
                const resp = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.success) { toast(data.message || 'Day deleted', 'success'); setTimeout(() => window.location.href='{{ route('float.days') }}', 600); }
                else toast(data.message || 'Failed', 'error');
            } catch (e) { toast('Network error', 'error'); }
        }
    </script>
@endsection
