@extends('layouts.app')

@section('title', 'Record Transaction')

@section('content')
    <div class="view-head">
        <div>
            <h2>Record New Transaction</h2>
            <p class="sub">Manually record a transaction and optionally link it to an SMS via reference.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('transactions.index') }}" class="btn btn-ghost">← Back to transactions</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Transaction Details</h3>
            <span class="link">Manual entry</span>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('transactions.store') }}" data-transaction-create>
                @csrf
                <div class="form-row">
                    <div class="field">
                        <label>Network</label>
                        <select name="network_id" required>
                            <option value="">Select network</option>
                            @foreach($combos['networks'] as $network)
                                <option value="{{ $network->id }}" {{ old('network_id') == $network->id ? 'selected' : '' }}>{{ $network->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Transaction type</label>
                        <select name="type" required>
                            <option value="deposit" {{ old('type') === 'deposit' ? 'selected' : '' }}>Customer Deposit</option>
                            <option value="withdrawal" {{ old('type') === 'withdrawal' ? 'selected' : '' }}>Customer Withdrawal</option>
                            <option value="send_money" {{ old('type') === 'send_money' ? 'selected' : '' }}>Send Money</option>
                            <option value="bill_payment" {{ old('type') === 'bill_payment' ? 'selected' : '' }}>Bill Payment</option>
                            <option value="airtime" {{ old('type') === 'airtime' ? 'selected' : '' }}>Airtime</option>
                            <option value="data" {{ old('type') === 'data' ? 'selected' : '' }}>Data Bundle</option>
                            <option value="bank_to_wallet" {{ old('type') === 'bank_to_wallet' ? 'selected' : '' }}>Bank to Wallet</option>
                            <option value="wallet_to_bank" {{ old('type') === 'wallet_to_bank' ? 'selected' : '' }}>Wallet to Bank</option>
                            <option value="float_deposit" {{ old('type') === 'float_deposit' ? 'selected' : '' }}>Float Deposit</option>
                            <option value="float_topup" {{ old('type') === 'float_topup' ? 'selected' : '' }}>Float Top-up</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Customer name</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="e.g. Juma Athumani">
                    </div>
                    <div class="field">
                        <label>Customer phone</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="07xxxxxxxx" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Amount (TZS)</label>
                        <input type="number" name="amount" value="{{ old('amount') }}" min="1" step="any" placeholder="e.g. 100000" required>
                    </div>
                    <div class="field">
                        <label>Provider reference (optional)</label>
                        <input type="text" name="provider_reference" value="{{ old('provider_reference', request('reference')) }}" placeholder="e.g. Tnx 626... or PP...">
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">If this matches an SMS transaction_reference, that SMS will be linked.</p>
                    </div>
                </div>
                @if(is_admin() && isset($selectedDate) && $selectedDate)
                    <div class="field">
                        <label>Transaction date (admin — for selected day)</label>
                        <input type="datetime-local" name="transaction_date" value="{{ old('transaction_date', $selectedDate.'T'.now()->format('H:i')) }}">
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Back-date this transaction to <strong>{{ $selectedDate }}</strong> for correct reconciliation. Leave as now for today.</p>
                    </div>
                @elseif(is_admin())
                    <div class="field">
                        <label>Transaction date (admin — optional, for past day)</label>
                        <input type="datetime-local" name="transaction_date" value="{{ old('transaction_date') }}" placeholder="{{ now()->format('Y-m-d\TH:i') }}">
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Leave empty for now, or set a past datetime to back-date for reconciliation on that day.</p>
                    </div>
                @endif
                <div class="field" style="background:var(--sand-50); border:1.5px solid var(--line); border-radius:10px; padding:12px;">
                    <label style="color:var(--terracotta-600);">Select SMS to autofill (optional — computes float top-up automatically)</label>
                    <select id="txnSmsSelect" style="width:100%; padding:10px 12px; border:1.5px solid var(--line); border-radius:8px; background:var(--white);">
                        <option value="">— No autofill — manual entry —</option>
                        @foreach($smsMessages as $sms)
                            <option value="{{ $sms->id }}" data-network="{{ $sms->network_id ?? '' }}" data-amount="{{ $sms->amount ?? '' }}" data-type="{{ $sms->transaction_type ?? '' }}" data-phone="{{ $sms->customer_phone ?? '' }}" data-name="{{ $sms->customer_name ?? '' }}" data-ref="{{ $sms->transaction_reference ?? '' }}" data-sms="{{ Str::limit($sms->message_body, 100) }}">{{ $sms->sender }} · {{ Str::limit($sms->message_body, 70) }} · {{ $sms->transaction_reference ?? 'no ref' }} · {{ $sms->amount ? money($sms->amount) : '' }} · {{ $sms->server_received_at->format('d M H:i') }} · {{ $sms->processing_status }}</option>
                        @endforeach
                    </select>
                    <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Pick a received SMS (e.g. for float top-up) — will autofill Network, Amount, Type, Customer, Provider ref and link the SMS. For top-up float, it will set <code>float_topup</code> + amount and provider ref.</p>
                </div>
                <div class="field">
                    <label>Link to SMS (optional - reference connect to the message)</label>
                    <select name="sms_id" id="txnSmsLink">
                        <option value="">— No SMS link —</option>
                        @foreach($smsMessages as $sms)
                            <option value="{{ $sms->id }}" {{ (string)old('sms_id', $selectedSms) === (string)$sms->id ? 'selected' : '' }}>{{ $sms->sender }} · {{ Str::limit($sms->message_body, 60) }} · {{ $sms->transaction_reference ?? 'no ref' }} · {{ $sms->server_received_at->format('d M H:i') }}</option>
                        @endforeach
                    </select>
                    <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Select an SMS that is currently Stored/Failed to link this transaction to that message.</p>
                </div>
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="submit" class="btn btn-primary">Record transaction</button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>How linking works</h3>
        </div>
        <div class="panel-body">
            <ul style="font-size:13px; color:var(--ink-soft); line-height:1.7; margin:0; padding-left:18px;">
                <li><strong>Provider reference</strong> — if you enter `6263421245180145` and an SMS has `transaction_reference = 6263421245180145`, that SMS will be marked `RECORDED` and linked.</li>
                <li><strong>SMS dropdown</strong> — directly pick an SMS (e.g. from `https://wakala.feedtanstore.com/sms?device=...`) to link. The SMS you pick will be updated to `RECORDED`.</li>
                <li>The new transaction will appear in both <code>/transactions</code> and (if `Bank to Wallet`/`Float Deposit`) in <code>/float</code>.</li>
            </ul>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-transaction-create]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: (data) => { toast(data.message, 'success'); setTimeout(() => window.location.href = '{{ route('transactions.index') }}', 700); } });
            });
        });

        const smsSelect = document.getElementById('txnSmsSelect');
        if (smsSelect) {
            smsSelect.addEventListener('change', () => {
                const opt = smsSelect.options[smsSelect.selectedIndex];
                if (!opt || !opt.value) return;
                const network = opt.dataset.network;
                const amount = opt.dataset.amount;
                const type = opt.dataset.type;
                const phone = opt.dataset.phone;
                const name = opt.dataset.name;
                const ref = opt.dataset.ref;
                const form = smsSelect.closest('form');
                if (network) {
                    const sel = form.querySelector('select[name=network_id]');
                    if (sel) sel.value = network;
                }
                if (amount) {
                    const inp = form.querySelector('input[name=amount]');
                    if (inp) inp.value = amount;
                }
                if (type) {
                    const sel = form.querySelector('select[name=type]');
                    if (sel) {
                        let mapped = type;
                        if (['float_topup','float_deposit'].includes(type)) mapped = 'float_topup';
                        if (sel.querySelector('option[value="'+mapped+'"]')) sel.value = mapped;
                    }
                }
                if (phone) {
                    const inp = form.querySelector('input[name=customer_phone]');
                    if (inp) inp.value = phone;
                }
                if (name) {
                    const inp = form.querySelector('input[name=customer_name]');
                    if (inp) inp.value = name;
                }
                if (ref) {
                    const inp = form.querySelector('input[name=provider_reference]');
                    if (inp) inp.value = ref;
                }
                const linkSel = document.getElementById('txnSmsLink');
                if (linkSel) linkSel.value = opt.value;
                toast('Autofilled from SMS ' + ref, 'success');
            });
        }
    </script>
@endsection
