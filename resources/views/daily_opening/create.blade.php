@extends('layouts.app')

@section('title', 'Enter Opening Balances')

@section('content')
    <div class="view-head">
        <div>
            <h2>Start Day — Enter Opening Balances</h2>
            <p class="sub">Record the cash in hand and float for each mobile money network at the start of the business day, {{ today()->format('l, j F Y') }}.</p>
        </div>
    </div>

    <div class="panel" style="max-width:880px;">
        <div class="panel-head">
            <h3>Opening Balances</h3>
            <span class="link">{{ $agent->name ?? 'Cash Point' }}</span>
        </div>
        <form action="{{ route('daily-opening.store') }}" method="POST" data-opening-form>
            @csrf
            <div class="panel-body">
                <div class="balance-strip">
                    <div class="balance-box" style="--stat-tint:var(--terracotta-100);">
                        <div class="bb-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--terracotta-600);"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            Cash in Hand (Opening)
                        </div>
                        <div class="field" style="margin-bottom:0;margin-top:6px;">
                            <label style="display:none;">Cash opening</label>
                            <input type="number" name="cash_opening" min="0" step="0.01" value="{{ old('cash_opening', $agent->cash_balance ?? 0) }}" required placeholder="0.00" style="font-size:22px;font-weight:700;">
                        </div>
                        <div class="bb-sub">Physical cash available at the counter right now.</div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--acacia-100);">
                        <div class="bb-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--acacia-600);"><path d="M3 17l9-9 5 5 4-4"></path><path d="m16 8 2-2"></path></svg>
                            Total Float (Auto)
                        </div>
                        <div class="bb-amount" id="totalFloatAuto">{{ money(0) }}</div>
                        <div class="bb-sub">Sum of all network floats below.</div>
                    </div>
                </div>

                <div class="settings-section"><h4>Float per network</h4></div>
                <p class="sub" style="margin-bottom:18px;font-size:13px;color:var(--ink-soft);">Enter the current wallet / float balance for each network.</p>

                @foreach ($networks as $network)
                    @php
                        $prev = $currentBalances[$network->id] ?? null;
                        $suggested = old('float_openings.'.$network->id, $prev?->opening_balance ?? $prev?->balance ?? 0);
                    @endphp
                    <div class="field">
                        <label>
                            <span class="net-dot" style="background:{{ $network->color }};margin-right:7px;vertical-align:middle;"></span>
                            {{ $network->name }} Float
                        </label>
                        <input type="number" name="float_openings[{{ $network->id }}]" min="0" step="0.01" value="{{ $suggested }}" class="float-input" data-network="{{ $network->id }}" placeholder="0.00">
                    </div>
                @endforeach

                <div class="field" style="margin-top:8px;">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="2" maxlength="500" placeholder="Any shift handover notes, variance explanations, etc.">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M20 6 9 17l-5-5"></path></svg>
                    Confirm &amp; Start Day
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const inputs = document.querySelectorAll('.float-input');
            const totalEl = document.getElementById('totalFloatAuto');

            function fmt(num) {
                const rounded = Math.round(num);
                return 'TZS ' + rounded.toLocaleString('en-US');
            }

            function recalc() {
                let total = 0;
                inputs.forEach(i => { total += parseFloat(i.value || 0); });
                totalEl.textContent = fmt(total);
            }

            inputs.forEach(i => i.addEventListener('input', recalc));
            recalc();
        })();

        document.querySelectorAll('[data-opening-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, {
                    done: () => {
                        toast('Opening balances recorded. Have a great day!', 'success');
                        setTimeout(() => location.href = '{{ route("cash-point.index") }}', 700);
                    }
                });
            });
        });
    </script>
@endsection
