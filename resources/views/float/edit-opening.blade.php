@extends('layouts.app')

@section('title', 'Edit Opening Balances')

@section('content')
    <div class="view-head">
        <div>
            <h2>{{ $opening ? 'Edit' : 'Add' }} Opening Balances</h2>
            <p class="sub">Admin — Set cash and float opening for <strong>{{ $viewDate->format('l, j F Y') }}</strong> ({{ $viewDate->toDateString() }}) at {{ $agent->name }}. Cash must reference previous day reconciled <strong>Counted cash</strong>.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('float.index', ['date' => \Illuminate\Support\Facades\Crypt::encryptString($viewDate->toDateString())]) }}" class="btn btn-ghost">← Back to Float ({{ $viewDate->format('d M Y') }})</a>
            <a href="{{ route('daily-opening.index') }}" class="btn btn-ghost">All openings</a>
        </div>
    </div>

    @if($previousReconciliation || $previousClosingCash !== null)
    <div class="panel" style="max-width:880px; border-left:3px solid var(--acacia-600);">
        <div class="panel-head">
            <h3>Previous Day Reconciled — Cash Reference</h3>
            <span class="tag tag-green">Counted cash</span>
        </div>
        <div class="panel-body">
            @if($previousReconciliation)
                <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; font-size:13px;">
                    <span><strong>Date:</strong> {{ $previousReconciliation->reconciliation_date->format('Y-m-d') }}</span>
                    <span class="tag {{ $previousReconciliation->status === 'reconciled' ? 'tag-green' : 'tag-gold' }}">{{ ucfirst($previousReconciliation->status) }}</span>
                    <a href="{{ route('reconciliation.show', $previousReconciliation) }}" class="btn btn-ghost btn-sm" style="padding:4px 8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
                        View Reconciliation {{ $previousReconciliation->code }}
                    </a>
                </div>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap:12px; margin-top:12px;">
                    <div style="background:var(--sand-100); border:1px solid var(--line); border-radius:8px; padding:10px; text-align:center;">
                        <div style="font-size:11px; color:var(--ink-soft); text-transform:uppercase; font-weight:700;">Expected cash</div>
                        <div style="font-size:14px; font-weight:700;">@money($previousReconciliation->expected_cash)</div>
                    </div>
                    <div style="background:var(--acacia-100); border:1px solid var(--line); border-radius:8px; padding:10px; text-align:center;">
                        <div style="font-size:11px; color:var(--ink-soft); text-transform:uppercase; font-weight:700;">Counted cash (previous closing)</div>
                        <div style="font-size:16px; font-weight:700; color:var(--acacia-600);">@money($previousReconciliation->counted_cash)</div>
                        <div style="font-size:11px; color:var(--ink-soft);">Use this as base for {{ $viewDate->format('Y-m-d') }} opening</div>
                    </div>
                    <div style="background:var(--white); border:1px solid var(--line); border-radius:8px; padding:10px; text-align:center;">
                        <div style="font-size:11px; color:var(--ink-soft); text-transform:uppercase; font-weight:700;">Cash variance</div>
                        <div style="font-size:14px; font-weight:700; color:{{ abs((float)$previousReconciliation->cash_variance) < 0.005 ? 'var(--acacia-600)' : 'var(--danger)' }};">{{ (float)$previousReconciliation->cash_variance > 0 ? '+' : '' }}@money($previousReconciliation->cash_variance)</div>
                    </div>
                </div>
            @else
                <div style="font-size:13px; color:var(--ink-soft);">No reconciliation for {{ $prevDate->format('Y-m-d') }} — fallback closing cash: <strong>@money($previousClosingCash ?? 0)</strong> (from {{ $prevDate->format('l') }} opening + activity).</div>
            @endif
            <div style="margin-top:12px; padding:10px; background:var(--white); border:1px dashed var(--line); border-radius:8px; font-size:12.5px; color:var(--ink-soft);">
                Cash at till for <strong>{{ $viewDate->format('Y-m-d') }}</strong> must reference this previous <strong>Counted cash</strong> <code>@money($previousClosingCash ?? 0)</code>. To add new cash, increase the Cash in Hand field below above this base (e.g. <code>@money($previousClosingCash ?? 0)</code> + <code>50,000</code> = <code>@money(($previousClosingCash ?? 0) + 50000)</code>).
            </div>
        </div>
    </div>
    @endif

    <div class="panel" style="max-width:880px;">
        <div class="panel-head">
            <h3>Opening Balances — {{ $viewDate->format('Y-m-d') }}</h3>
            <span class="tag {{ $opening && $opening->is_closed ? 'tag-grey' : 'tag-green' }}">{{ $opening ? ($opening->is_closed ? 'Closed' : 'Open') : 'New' }}</span>
        </div>
        <form action="{{ route('float.opening.update') }}" method="POST" data-opening-edit>
            @csrf
            @method('PUT')
            <div class="panel-body">
                <div class="field">
                    <label>Opening date <span style="color:var(--danger);">*</span></label>
                    <input type="date" name="opening_date" value="{{ old('opening_date', $viewDate->toDateString()) }}" required max="{{ today()->toDateString() }}">
                    <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">You are editing opening for this date. Changing the date will create/update that day's opening. Reports & reconciliation for both old and new dates will auto-reflect.</p>
                    @error('opening_date')<p style="color:var(--danger);font-size:12px;">{{ $message }}</p>@enderror
                </div>

                <div class="balance-strip">
                    <div class="balance-box" style="--stat-tint:var(--terracotta-100);">
                        <div class="bb-label">Cash in Hand (Opening) * — refs previous Counted cash</div>
                        <div class="field" style="margin-bottom:0;margin-top:6px;">
                            <input type="number" name="cash_opening" id="cashOpeningInput" min="0" step="0.01" value="{{ old('cash_opening', $suggestedCash) }}" required placeholder="0.00" style="font-size:22px;font-weight:700;">
                            @error('cash_opening')<p style="color:var(--danger);font-size:12px;">{{ $message }}</p>@enderror
                        </div>
                        <div class="bb-sub">
                            Base from reconciled <strong>@money($previousClosingCash ?? 0)</strong> ({{ $prevDate->format('Y-m-d') }} counted). Current value = base + new cash added below.
                            @if($opening && $opening->cash_opening != $suggestedCash)
                                <br>Existing opening for this date is <strong>@money($opening->cash_opening)</strong> — editing will overwrite.
                            @endif
                        </div>
                        <div style="margin-top:10px; padding:10px; background:var(--white); border:1px solid var(--line); border-radius:8px;">
                            <label style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--ink-soft);">Add new cash (additional) — will increase Cash in Hand above</label>
                            <div style="display:flex; gap:8px; margin-top:6px;">
                                <input type="number" id="additionalCashInput" min="0" step="0.01" placeholder="e.g. 50000" style="flex:1; padding:10px 12px; border:1.5px solid var(--line); border-radius:8px; font-size:14px;">
                                <button type="button" id="applyAdditionalCash" class="btn btn-ghost btn-sm" style="white-space:nowrap;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    Add to opening
                                </button>
                            </div>
                            <div style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Click to add amount to Cash in Hand field (previous counted + additional = new opening). Or edit Cash in Hand directly.</div>
                        </div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--acacia-100);">
                        <div class="bb-label">Total Float (Auto) — opening + top-ups</div>
                        <div class="bb-amount" id="totalFloatAuto">{{ money(($opening ? $opening->totalFloatOpening() : 0) + $floatTopupsForDate) }}</div>
                        <div class="bb-sub" id="totalFloatBreakdown" data-topups="{{ $floatTopupsForDate }}">
                            <span id="openingSumDisplay">{{ money($opening ? $opening->totalFloatOpening() : 0) }}</span> opening
                            @if($floatTopupsForDate > 0)
                                + <span style="color:var(--acacia-600); font-weight:700;">@money($floatTopupsForDate)</span> float top-ups
                                @if($topupBreakdown->isNotEmpty())
                                    <span style="font-size:11px; color:var(--ink-soft);">({{ $topupBreakdown->map(fn($r) => $r['network'].': '.money($r['total']))->implode(', ') }})</span>
                                @endif
                            @else
                                + <span style="color:var(--ink-soft);">@money(0)</span> top-ups
                            @endif
                            = total
                        </div>
                    </div>
                </div>

                <div class="settings-section"><h4>Float per network</h4></div>
                <p class="sub" style="margin-bottom:18px;font-size:13px;color:var(--ink-soft);">Enter opening float per network for {{ $viewDate->format('d M Y') }}. You can add a new network balance by entering an amount for a network that currently has no opening.</p>

                @foreach ($networks as $network)
                    @php
                        $prevOpening = $opening ? ($opening->float_openings[$network->id] ?? null) : null;
                        $bal = $currentBalances[$network->id] ?? null;
                        $prevCounted = $prevFloatCountedMap[$network->id] ?? $prevFloatCountedMap[$network->name] ?? null;
                        $suggested = old('float_openings.'.$network->id, $prevOpening ?? $prevCounted ?? $bal?->balance ?? $bal?->opening_balance ?? 0);
                        $isFromPrevCounted = $prevOpening === null && $prevCounted !== null && !old('float_openings.'.$network->id);
                    @endphp
                    <div class="field">
                        <label>
                            <span class="net-dot" style="background:{{ $network->color }};margin-right:7px;vertical-align:middle;"></span>
                            {{ $network->name }} Float
                            @if($isFromPrevCounted)
                                <span style="font-size:11px; color:var(--acacia-600); font-weight:700;"> — from previous day Counted @money($prevCounted)</span>
                                <span style="font-size:11px; color:var(--ink-soft);"> (reconciled {{ $prevDate->format('Y-m-d') }})</span>
                            @elseif($bal)
                                <span style="font-size:11px; color:var(--ink-soft);"> (current live: @money($bal->balance) · opening: @money($bal->opening_balance))</span>
                            @endif
                        </label>
                        <input type="number" name="float_openings[{{ $network->id }}]" min="0" step="0.01" value="{{ $suggested }}" class="float-input" placeholder="0.00">
                        @error('float_openings.'.$network->id)<p style="color:var(--danger);font-size:12px;">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                <div class="field" style="margin-top:8px;">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="2" maxlength="500" placeholder="Handover notes, variance explanation for this date…">{{ old('notes', $opening?->notes) }}</textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary">
                    {{ $opening ? 'Update opening' : 'Create opening' }} for {{ $viewDate->format('d M Y') }}
                </button>
                <a href="{{ route('float.index', ['date' => \Illuminate\Support\Facades\Crypt::encryptString($viewDate->toDateString())]) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>

    <div class="panel" style="max-width:880px; margin-top:18px;">
        <div class="panel-head">
            <h3>How this affects reports & reconciliation</h3>
        </div>
        <div class="panel-body" style="font-size:13px; color:var(--ink-soft); line-height:1.7;">
            <ul style="margin:0; padding-left:18px;">
                <li><strong>Opening balances</strong> are used to compute <code>expected_cash/expected_float</code> in <a href="{{ route('reconciliation.index') }}">Reconciliation</a> for that date; editing will auto-recompute any existing reconciliation for that date (variances/status).</li>
                <li><strong>Current float balances</strong> (<code>NetworkBalance.balance</code>) are live; editing today’s opening also syncs current balance if no transactions yet that day.</li>
                <li>Use <a href="{{ route('float.index') }}">Float index</a> with date picker to add float entries for a past day (they will be timestamped to that day for correct reporting).</li>
            </ul>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const inputs = document.querySelectorAll('.float-input');
            const totalEl = document.getElementById('totalFloatAuto');
            const breakdown = document.getElementById('totalFloatBreakdown');
            const topups = breakdown ? parseFloat(breakdown.getAttribute('data-topups') || 0) : 0;
            function fmt(num) { return 'TZS ' + Math.round(num).toLocaleString('en-US'); }
            function recalc() {
                let openingSum = 0;
                inputs.forEach(i => { openingSum += parseFloat(i.value || 0); });
                const total = openingSum + topups;
                if (totalEl) totalEl.textContent = fmt(total);
                const openingSpan = document.getElementById('openingSumDisplay');
                if (openingSpan) openingSpan.textContent = fmt(openingSum);
            }
            inputs.forEach(i => i.addEventListener('input', recalc));
            recalc();
        })();

        // When selecting day, load that day's data (opening, cash refs, float top-ups)
        const openingDateInput = document.querySelector('input[name="opening_date"]');
        if (openingDateInput) {
            let lastLoaded = openingDateInput.value;
            openingDateInput.addEventListener('change', (e) => {
                const val = e.target.value;
                if (!val || val === lastLoaded) return;
                // Navigate to same page with new date so server loads that day's opening + previous reconciled + top-ups
                window.location.href = '{{ route('float.opening.edit') }}?date=' + encodeURIComponent(val);
            });
        }

        document.getElementById('applyAdditionalCash')?.addEventListener('click', () => {
            const base = parseFloat('{{ $previousClosingCash ?? 0 }}') || 0;
            const addInput = document.getElementById('additionalCashInput');
            const cashInput = document.getElementById('cashOpeningInput');
            const add = parseFloat(addInput.value || 0);
            if (!add || add <= 0) { toast('Enter additional amount first', 'error'); return; }
            const current = parseFloat(cashInput.value || 0);
            // If current equals base (no manual edit), set to base + add; otherwise add to current
            const next = (Math.abs(current - base) < 0.01 ? base : current) + add;
            cashInput.value = next.toFixed(2);
            toast('Added ' + add.toLocaleString() + ' to Cash in Hand (now ' + next.toLocaleString() + ')', 'success');
            addInput.value = '';
        });

        document.querySelectorAll('[data-opening-edit]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: (data) => { toast(data.message || 'Opening saved', 'success'); setTimeout(() => window.location.href = '{{ route('float.index') }}?date=' + encodeURIComponent(form.querySelector('input[name=opening_date]').value), 700); } });
            });
        });
    </script>
@endsection
