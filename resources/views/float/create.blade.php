@extends('layouts.app')

@section('title', 'New Float Entry')

@section('content')
    <div class="view-head">
        <div>
            <h2>New Float / Cash Entry</h2>
            <p class="sub">Record a new float top-up, pull, or cash movement.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('float.index') }}" class="btn btn-ghost">← Back to float</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Float Entry Details</h3>
            <span class="link">{{ $isAdmin ? 'Admin — any date' : 'Today' }} · {{ $viewDate->format('d M Y') }}</span>
        </div>
        <div class="panel-body">
            <form action="{{ route('float.store') }}" method="POST" data-float-form>
                @csrf
                @if($isAdmin)
                    <div class="field">
                        <label>Float date (admin — for selected day)</label>
                        <input type="date" name="float_date" id="floatDateInput" value="{{ old('float_date', $selectedDate) }}" max="{{ today()->toDateString() }}">
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Pick the day this float movement actually happened. Changing the date will reload all float data for that day. It will be counted for that day's reports/reconciliation and use that day's opening if exists.</p>
                    </div>
                    @if(isset($dayTransactions) && $dayTransactions->isNotEmpty())
                        <div class="field">
                            <label>Reference Transaction (optional — select to autofill)</label>
                            <select id="floatTxnSelect" style="width:100%; padding:10px 12px; border:1.5px solid var(--line); border-radius:8px; background:var(--white);">
                                <option value="">— No reference —</option>
                                @foreach($dayTransactions as $t)
                                    <option value="{{ $t->id }}" data-network="{{ $t->network_id }}" data-amount="{{ $t->amount }}" data-type="{{ $t->type }}" data-ref="{{ $t->provider_reference ?? $t->reference }}">{{ $t->created_at->format('H:i') }} · {{ $t->reference }} · {{ txn_type_label($t->type) }} · {{ $t->network?->name }} · @money($t->amount) · {{ Str::limit($t->customer_name ?? $t->customer_phone, 24) }}</option>
                                @endforeach
                            </select>
                            <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Pick a transaction received on {{ $viewDate->format('d M Y') }} to autofill network/amount and link. Will update that transaction's notes and appear in reconciliation for this date.</p>
                            <input type="hidden" name="transaction_id" id="floatLinkedTxn" value="{{ old('transaction_id') }}">
                        </div>
                    @else
                        <p style="font-size:12px; color:var(--ink-soft);">No completed transactions for {{ $viewDate->format('d M Y') }} to reference.</p>
                    @endif
                    <div class="panel" style="margin-top:12px; border:1px solid var(--line); border-radius:10px; overflow:hidden;">
                        <div class="panel-head" style="padding:12px 16px;">
                            <h3 style="font-size:14px;">All Float Data for {{ $viewDate->format('d M Y') }}</h3>
                            <span class="tag tag-gold">{{ $viewDate->toDateString() }}</span>
                        </div>
                        <div class="panel-body" style="padding:12px 16px; font-size:12.5px;">
                            @if($todayOpening)
                                <div style="margin-bottom:10px;">
                                    <strong>Opening:</strong> Cash @money($todayOpening->cash_opening) ·
                                    @foreach($allNetworks as $net)
                                        @php $openingAmt = $todayOpening->float_openings[$net->id] ?? 0; @endphp
                                        <span class="tag" style="background:var(--white); border:1px solid var(--line);"><span class="net-dot" style="background:{{ $net->color }};"></span> {{ $net->name }}: @money($openingAmt)</span>
                                    @endforeach
                                </div>
                            @else
                                <div style="margin-bottom:10px; color:var(--ink-soft);">No Daily Opening for {{ $viewDate->toDateString() }} — opening balances from live <code>NetworkBalance.opening_balance</code> will be used.</div>
                            @endif
                            <div class="table-scroll" style="max-height:220px; overflow-y:auto;">
                                <table style="min-width:500px;">
                                    <thead>
                                        <tr>
                                            <th>Network</th>
                                            <th style="text-align:right;">Opening</th>
                                            <th style="text-align:right;">Current</th>
                                            <th style="text-align:right;">Variance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allNetworks as $net)
                                            @php
                                                $bal = $currentBalances[$net->id] ?? null;
                                                $openingVal = $todayOpening ? ($todayOpening->float_openings[$net->id] ?? 0) : ($bal?->opening_balance ?? 0);
                                                $currentVal = $bal?->balance ?? 0;
                                                $var = $currentVal - $openingVal;
                                            @endphp
                                            <tr data-float-net-id="{{ $net->id }}" style="transition:background .2s;">
                                                <td><span class="net-dot" style="background:{{ $net->color }};"></span> {{ $net->name }}</td>
                                                <td style="text-align:right;">@money($openingVal)</td>
                                                <td style="text-align:right; font-weight:600;">@money($currentVal)</td>
                                                <td style="text-align:right; color:{{ $var >=0 ? 'var(--acacia-600)' : 'var(--danger)' }};">{{ $var >=0 ? '+' : '' }}@money($var)</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(isset($dayFloatTransactions) && $dayFloatTransactions->isNotEmpty())
                                <div style="margin-top:12px; border-top:1px solid var(--line); padding-top:10px;">
                                    <strong>Float movements for {{ $viewDate->format('d M Y') }} ({{ $dayFloatTransactions->count() }}):</strong>
                                    <div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap;">
                                        @foreach($dayFloatTransactions as $ft)
                                                <span class="tag {{ in_array($ft->type, ['float_topup', 'cash_in', 'cash_to_float'], true) ? 'tag-green' : 'tag-terracotta' }}">{{ $ft->created_at->format('H:i') }} {{ $ft->network?->name }} {{ txn_type_label($ft->type) }} @money($ft->amount) {{ $ft->notes ? '· '.$ft->notes : '' }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div style="margin-top:10px; font-size:12px; color:var(--ink-soft);">No float movements yet for {{ $viewDate->format('d M Y') }}.</div>
                            @endif
                        </div>
                    </div>
                @endif
                <div class="form-row">
                    <div class="field">
                        <label>Network</label>
                        <select name="network_id" required>
                            @foreach ($networks as $id => $name)
                                <option value="{{ $id }}" {{ old('network_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select name="type" required>
                            <option value="float_topup" {{ old('type', $selectedType) === 'float_topup' ? 'selected' : '' }}>Float top-up (float in)</option>
                            <option value="float_pull" {{ old('type', $selectedType) === 'float_pull' ? 'selected' : '' }}>Float pull (float out)</option>
                            <option value="cash_to_float" {{ old('type', $selectedType) === 'cash_to_float' ? 'selected' : '' }}>Cash to Float</option>
                            <option value="cash_in" {{ old('type') === 'cash_in' ? 'selected' : '' }}>Cash deposited to network</option>
                            <option value="cash_out" {{ old('type') === 'cash_out' ? 'selected' : '' }}>Cash withdrawn from network</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label>Amount (TZS)</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" min="1" step="any" placeholder="e.g. 500000" required>
                </div>
                <div class="field" data-cash-to-float-fields>
                    <label>Commission / top-up fee (TZS)</label>
                    <input type="number" name="commission" value="{{ old('commission', 0) }}" min="0" step="0.01" placeholder="0">
                    <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Float receives the entered amount minus this commission.</p>
                </div>
                <div class="field" data-float-net-preview style="display:none;">
                    <label>Net float added (TZS)</label>
                    <output id="floatNetPreview" style="display:block; padding:10px 12px; background:var(--acacia-50); border:1px solid var(--line); border-radius:8px; font-weight:700;">TZS 0</output>
                </div>
                <div class="field">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Optional reference…">{{ old('notes') }}</textarea>
                </div>
                @if($todayOpening)
                    <div style="margin-top:12px; padding:8px 10px; background:var(--sand-100); border:1px solid var(--line); border-radius:6px; font-size:12px; color:var(--ink-soft);">Opening for {{ $viewDate->format('Y-m-d') }}: Cash @money($todayOpening->cash_opening) · Float total @money($todayOpening->totalFloatOpening())</div>
                @endif
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="submit" class="btn btn-primary">Save entry</button>
                    <a href="{{ route('float.index', $isAdmin ? ['date' => $selectedDate] : []) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-float-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => window.location.href = '{{ route('float.index') }}', 600) });
            });
        });

        const floatTypeSelect = document.querySelector('[name=type]');
        const commissionInput = document.querySelector('[name=commission]');
        const amountInput = document.querySelector('[name=amount]');
        const cashToFloatFields = document.querySelector('[data-cash-to-float-fields]');
        const floatNetPreview = document.querySelector('[data-float-net-preview]');
        const floatNetPreviewValue = document.getElementById('floatNetPreview');

        const updateCashToFloatPreview = () => {
            const isCashToFloat = floatTypeSelect?.value === 'cash_to_float';
            if (cashToFloatFields) cashToFloatFields.style.display = isCashToFloat ? 'block' : 'none';
            if (floatNetPreview) floatNetPreview.style.display = isCashToFloat ? 'block' : 'none';
            if (!isCashToFloat) return;
            const amount = parseFloat(amountInput?.value || '0') || 0;
            const commission = parseFloat(commissionInput?.value || '0') || 0;
            if (floatNetPreviewValue) floatNetPreviewValue.textContent = 'TZS ' + Math.max(0, amount - commission).toLocaleString('en-US', { maximumFractionDigits: 2 });
        };

        floatTypeSelect?.addEventListener('change', updateCashToFloatPreview);
        amountInput?.addEventListener('input', updateCashToFloatPreview);
        commissionInput?.addEventListener('input', updateCashToFloatPreview);
        updateCashToFloatPreview();

        const txnSelect = document.getElementById('floatTxnSelect');
        if (txnSelect) {
            txnSelect.addEventListener('change', () => {
                const opt = txnSelect.options[txnSelect.selectedIndex];
                const linked = document.getElementById('floatLinkedTxn');
                if (!opt || !opt.value) {
                    if (linked) linked.value = '';
                    document.querySelectorAll('[data-float-net-id]').forEach(row => { row.style.background=''; row.style.outline=''; });
                    return;
                }
                if (linked) linked.value = opt.value;
                const networkId = opt.dataset.network;
                const amount = opt.dataset.amount;
                const type = opt.dataset.type;
                const ref = opt.dataset.ref;
                const form = txnSelect.closest('form');
                if (networkId) {
                    const sel = form.querySelector('select[name=network_id]');
                    if (sel) sel.value = networkId;
                }
                if (amount) {
                    const inp = form.querySelector('input[name=amount]');
                    if (inp) inp.value = amount;
                }
                const typeMap = {
                    'deposit': 'float_pull',
                    'withdrawal': 'float_topup',
                    'float_deposit': 'float_topup',
                    'float_topup': 'float_topup',
                    'bank_to_wallet': 'float_topup',
                    'cash_to_float': 'cash_to_float',
                    'wallet_to_bank': 'float_pull',
                    'send_money': 'float_pull',
                    'bill_payment': 'float_pull',
                    'airtime': 'float_pull',
                    'data': 'float_pull'
                };
                if (type && typeMap[type]) {
                    const sel = form.querySelector('select[name=type]');
                    if (sel) sel.value = typeMap[type];
                }
                const notes = form.querySelector('textarea[name=notes]');
                if (notes && ref) {
                    const addition = 'Ref txn ' + ref;
                    if (!notes.value.includes(addition)) {
                        notes.value = (notes.value ? notes.value + ' | ' : '') + addition;
                    }
                }
                // Highlight network row in All Float Data panel and show full data
                document.querySelectorAll('[data-float-net-id]').forEach(row => { row.style.background=''; row.style.outline=''; });
                if (networkId) {
                    const row = document.querySelector('[data-float-net-id="' + networkId + '"]');
                    if (row) {
                        row.style.background = 'var(--gold-100)';
                        row.style.outline = '2px solid var(--gold-500)';
                        row.style.borderRadius = '6px';
                    }
                }
            });
        }

        const floatDateInput = document.getElementById('floatDateInput');
        if (floatDateInput) {
            floatDateInput.addEventListener('change', () => {
                const newDate = floatDateInput.value;
                if (newDate) {
                    const currentType = document.querySelector('select[name=type]')?.value || 'float_topup';
                    window.location.href = '{{ route('float.create') }}?date=' + encodeURIComponent(newDate) + '&type=' + encodeURIComponent(currentType);
                }
            });
        }
    </script>
@endsection
