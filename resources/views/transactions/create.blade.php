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
                <div class="field">
                    <label>Link to SMS (optional - reference connect to the message)</label>
                    <select name="sms_id">
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
    </script>
@endsection
