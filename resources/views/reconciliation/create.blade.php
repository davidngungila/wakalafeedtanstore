@extends('layouts.app')

@section('title', 'New Reconciliation')

@section('content')
    <div class="view-head">
        <div>
            <h2>New Reconciliation</h2>
            <p class="sub">Compare counted till cash and network floats against the system balances for {{ today()->format('l, j F Y') }}.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('reconciliation.index') }}" class="btn btn-ghost">← Back to reconciliation</a>
        </div>
    </div>

    <div class="balance-strip">
        <div class="balance-box" style="--stat-tint:var(--sand-100);">
            <div class="bb-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--coffee-500);"><path d="M3 9l9-6 9 6v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                Opening cash
            </div>
            <div class="bb-amount">@money($openingCash)</div>
            <div class="bb-sub">Closing cash from the last reconciliation.</div>
        </div>
        <div class="balance-box" style="--stat-tint:var(--terracotta-100);">
            <div class="bb-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--terracotta-600);"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                Expected cash
            </div>
            <div class="bb-amount">@money($expectedCash)</div>
            <div class="bb-sub">System cash balance after today's transactions.</div>
        </div>
        <div class="balance-box" style="--stat-tint:var(--acacia-100);">
            <div class="bb-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--acacia-600);"><path d="M3 17l9-9 5 5 4-4"></path><path d="m16 8 2-2"></path></svg>
                Expected total float
            </div>
            <div class="bb-amount">@money($balances->sum())</div>
            <div class="bb-sub">Sum of all network float balances in the system.</div>
        </div>
    </div>

    <div class="panel" style="max-width:880px;">
        <div class="panel-head">
            <h3>Counted balances</h3>
            <span class="link">{{ $agent->name }}</span>
        </div>
        <form action="{{ route('reconciliation.store') }}" method="POST" data-recon-form>
            @csrf
            <div class="panel-body">
                <div class="form-row">
                    <div class="field">
                        <label>Reconciliation date</label>
                        <input type="date" name="reconciliation_date" value="{{ old('reconciliation_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="field">
                        <label>Counted cash in till (TZS)</label>
                        <input type="number" name="counted_cash" id="countedCash" min="0" step="0.01" value="{{ old('counted_cash') }}" class="cash-counted" placeholder="0.00" required>
                    </div>
                </div>

                <div class="settings-section"><h4>Counted float per network</h4></div>
                <p class="sub" style="margin-bottom:18px;font-size:13px;color:var(--ink-soft);">Enter the wallet / float balance you physically see on each network. System balance is prefilled — adjust to what you counted.</p>

                @foreach ($networks as $network)
                    @php
                        $system = $balances[$network->id] ?? 0;
                        $suggested = old('counted_floats.'.$network->id, $system);
                    @endphp
                    <div class="field">
                        <label>
                            <span class="net-dot" style="background:{{ $network->color }};margin-right:7px;vertical-align:middle;"></span>
                            {{ $network->name }} Float
                            <span style="font-weight:400;color:var(--ink-soft);font-size:12px;margin-left:6px;">System: <b data-system-float="{{ $network->id }}">@money($system)</b></span>
                        </label>
                        <input type="number" name="counted_floats[{{ $network->id }}]" min="0" step="0.01" value="{{ $suggested }}" class="float-counted" data-network="{{ $network->id }}" placeholder="0.00">
                    </div>
                @endforeach

                <div class="field" style="margin-top:8px;">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="2" maxlength="255" placeholder="Shift handover notes, variance explanations, etc.">{{ old('notes') }}</textarea>
                </div>

                <div class="balance-strip" style="margin-top:20px;margin-bottom:0;">
                    <div class="balance-box">
                        <div class="bb-label">Cash variance</div>
                        <div class="bb-amount" id="cashVarDisplay">TZS 0</div>
                        <div class="bb-sub">Counted cash minus expected cash.</div>
                    </div>
                    <div class="balance-box">
                        <div class="bb-label">Float variance</div>
                        <div class="bb-amount" id="floatVarDisplay">TZS 0</div>
                        <div class="bb-sub">Counted float minus expected float.</div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--sand-100);">
                        <div class="bb-label">Overall result</div>
                        <div class="bb-amount" id="resultDisplay">Perfect match</div>
                        <div class="bb-sub">Reconciled when both variances are zero.</div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M20 6 9 17l-5-5"></path></svg>
                    Save reconciliation
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const expectedCash = {{ $expectedCash }};
            const systemFloats = {!! json_encode($balances) !!};
            const cashInput = document.getElementById('countedCash');
            const floatInputs = document.querySelectorAll('.float-counted');

            function fmt(n) {
                return 'TZS ' + Math.round(n).toLocaleString('en-US');
            }

            function display(el, value, hash) {
                el.textContent = fmt(value);
                el.style.color = hash > 0 ? '#8a6418' : (hash < 0 ? 'var(--danger)' : 'var(--coffee-900)');
            }

            function recalc() {
                const countedCash = parseFloat(cashInput.value || 0);
                const cashVar = countedCash - expectedCash;
                display(document.getElementById('cashVarDisplay'), Math.abs(cashVar), cashVar === 0 ? 0 : (cashVar > 0 ? 1 : -1));

                let countedFloat = 0;
                floatInputs.forEach(i => { countedFloat += parseFloat(i.value || 0); });
                const expectedFloat = floatInputs.length ? Object.values(systemFloats).reduce((a, b) => a + (parseFloat(b) || 0), 0) : 0;
                const floatVar = countedFloat - expectedFloat;
                display(document.getElementById('floatVarDisplay'), Math.abs(floatVar), floatVar === 0 ? 0 : (floatVar > 0 ? 1 : -1));

                const result = document.getElementById('resultDisplay');
                if (cashVar === 0 && floatVar === 0) {
                    result.textContent = 'Perfect match';
                    result.style.color = 'var(--acacia-600)';
                } else {
                    result.textContent = 'Variance found';
                    result.style.color = '#8a6418';
                }
            }

            cashInput.addEventListener('input', recalc);
            floatInputs.forEach(i => i.addEventListener('input', recalc));
            recalc();
        })();

        document.querySelectorAll('[data-recon-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => window.location.href = '{{ route('reconciliation.index') }}', 700) });
            });
        });
    </script>
@endsection