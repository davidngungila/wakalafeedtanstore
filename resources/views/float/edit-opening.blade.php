@extends('layouts.app')

@section('title', 'Edit Opening Balances')

@section('content')
    <div class="view-head">
        <div>
            <h2>{{ $opening ? 'Edit' : 'Add' }} Opening Balances</h2>
            <p class="sub">Admin — Set cash and float opening for <strong>{{ $viewDate->format('l, j F Y') }}</strong> ({{ $viewDate->toDateString() }}) at {{ $agent->name }}. This updates <code>DailyOpening</code> and <code>NetworkBalance.opening_balance</code> for that day.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('float.index', ['date' => $dateStr]) }}" class="btn btn-ghost">← Back to Float ({{ $viewDate->format('d M Y') }})</a>
            <a href="{{ route('daily-opening.index') }}" class="btn btn-ghost">All openings</a>
        </div>
    </div>

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
                        <div class="bb-label">Cash in Hand (Opening) *</div>
                        <div class="field" style="margin-bottom:0;margin-top:6px;">
                            <input type="number" name="cash_opening" min="0" step="0.01" value="{{ old('cash_opening', $opening?->cash_opening ?? $agent->cash_balance ?? 0) }}" required placeholder="0.00" style="font-size:22px;font-weight:700;">
                            @error('cash_opening')<p style="color:var(--danger);font-size:12px;">{{ $message }}</p>@enderror
                        </div>
                        <div class="bb-sub">Physical cash at counter for {{ $viewDate->format('d M Y') }}. If editing today, this also updates live <code>Agent.cash_balance</code>.</div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--acacia-100);">
                        <div class="bb-label">Total Float (Auto)</div>
                        <div class="bb-amount" id="totalFloatAuto">{{ money($opening ? $opening->totalFloatOpening() : 0) }}</div>
                        <div class="bb-sub">Sum of floats below. Updates <code>NetworkBalance.opening_balance</code>.</div>
                    </div>
                </div>

                <div class="settings-section"><h4>Float per network</h4></div>
                <p class="sub" style="margin-bottom:18px;font-size:13px;color:var(--ink-soft);">Enter opening float per network for {{ $viewDate->format('d M Y') }}. You can add a new network balance by entering an amount for a network that currently has no opening.</p>

                @foreach ($networks as $network)
                    @php
                        $prevOpening = $opening ? ($opening->float_openings[$network->id] ?? null) : null;
                        $bal = $currentBalances[$network->id] ?? null;
                        $suggested = old('float_openings.'.$network->id, $prevOpening ?? $bal?->opening_balance ?? $bal?->balance ?? 0);
                    @endphp
                    <div class="field">
                        <label>
                            <span class="net-dot" style="background:{{ $network->color }};margin-right:7px;vertical-align:middle;"></span>
                            {{ $network->name }} Float
                            @if($bal)<span style="font-size:11px; color:var(--ink-soft);"> (current live: @money($bal->balance) · opening: @money($bal->opening_balance))</span>@endif
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
                <a href="{{ route('float.index', ['date' => $viewDate->toDateString()]) }}" class="btn btn-ghost">Cancel</a>
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
            function fmt(num) { return 'TZS ' + Math.round(num).toLocaleString('en-US'); }
            function recalc() {
                let total = 0;
                inputs.forEach(i => { total += parseFloat(i.value || 0); });
                if (totalEl) totalEl.textContent = fmt(total);
            }
            inputs.forEach(i => i.addEventListener('input', recalc));
            recalc();
        })();

        document.querySelectorAll('[data-opening-edit]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: (data) => { toast(data.message || 'Opening saved', 'success'); setTimeout(() => window.location.href = '{{ route('float.index') }}?date=' + encodeURIComponent(form.querySelector('input[name=opening_date]').value), 700); } });
            });
        });
    </script>
@endsection
