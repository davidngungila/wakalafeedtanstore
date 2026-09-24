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
                <div class="field" style="background:var(--gold-100); border:1.5px solid var(--line); border-radius:10px; padding:14px; margin-bottom:16px;">
                    <label style="color:var(--terracotta-600); font-weight:700;">1. Select SMS First — Read Full SMS & Compute Network</label>
                    <select id="txnSmsSelectTop" style="width:100%; padding:10px 12px; border:1.5px solid var(--line); border-radius:8px; background:var(--white); margin-top:6px;">
                        <option value="">— No SMS — manual entry —</option>
                        @foreach($smsMessages as $sms)
                            <option value="{{ $sms->id }}" data-network="{{ $sms->network_id ?? '' }}" data-network-name="{{ $sms->network?->name ?? '' }}" data-amount="{{ $sms->amount ?? '' }}" data-type="{{ $sms->transaction_type ?? '' }}" data-phone="{{ $sms->customer_phone ?? '' }}" data-name="{{ $sms->customer_name ?? '' }}" data-ref="{{ $sms->transaction_reference ?? '' }}" data-sender="{{ $sms->sender }}" data-body="{{ htmlspecialchars($sms->message_body, ENT_QUOTES) }}" data-color="{{ $sms->network?->color ?? '#999' }}">{{ $sms->sender }} · {{ Str::limit($sms->message_body, 80) }} · {{ $sms->transaction_reference ?? 'no ref' }} · {{ $sms->amount ? money($sms->amount) : '' }}</option>
                        @endforeach
                    </select>
                    <div id="smsFullArea" style="display:none; margin-top:10px; background:var(--white); border:1px solid var(--line); border-radius:8px; padding:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <strong id="smsFullSender" style="color:var(--coffee-900);"></strong>
                            <span id="smsFullTime" style="font-size:11px; color:var(--ink-soft);"></span>
                        </div>
                        <div id="smsFullBody" style="font-size:13px; white-space:pre-wrap; word-break:break-word; background:var(--sand-50); border:1px solid var(--line); border-radius:6px; padding:10px; font-family:ui-monospace,monospace; color:var(--coffee-700);"></div>
                        <div style="margin-top:10px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <button type="button" id="smsComputeBtn" class="btn btn-primary btn-sm">Compute → Fill Network & Details</button>
                            <span id="smsComputeResult" style="font-size:12px; color:var(--ink-soft);"></span>
                        </div>
                        <div id="smsNetworkPreview" style="margin-top:8px; font-size:12px; display:none;">
                            <span style="color:var(--ink-soft);">Detected Network:</span> <strong id="smsDetectedNetwork" style="color:var(--coffee-900);"></strong> <span class="net-dot" id="smsDetectedDot" style="display:inline-block; width:10px; height:10px; border-radius:50%; vertical-align:middle; margin-left:4px;"></span>
                        </div>
                    </div>
                    <p style="font-size:11px; color:var(--ink-soft); margin-top:6px;">Pick an SMS received — read the full message below, then click <strong>Compute</strong> to auto-detect Network (via sender/keywords) and fill Amount/Type/Customer/Reference and link the SMS.</p>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Network <span id="networkComputedBadge" style="display:none; font-size:10px; background:var(--acacia-100); color:var(--acacia-600); padding:2px 6px; border-radius:10px; margin-left:6px;">computed</span></label>
                        <select name="network_id" id="txnNetworkSelect" required>
                            <option value="">Select network</option>
                            @foreach($combos['networks'] as $network)
                                <option value="{{ $network->id }}" data-color="{{ $network->color }}" {{ old('network_id') == $network->id ? 'selected' : '' }}>{{ $network->name }}</option>
                            @endforeach
                        </select>
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;" id="networkHelp">Will be auto-filled after Compute.</p>
                    </div>
                    <div class="field">
                        <label>Transaction type</label>
                        <select name="type" id="txnTypeSelect" required>
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

        // Top SMS selector with full read area and Compute
        const topSelect = document.getElementById('txnSmsSelectTop');
        const smsFullArea = document.getElementById('smsFullArea');
        const smsFullBody = document.getElementById('smsFullBody');
        const smsFullSender = document.getElementById('smsFullSender');
        const smsFullTime = document.getElementById('smsFullTime');
        const smsDetectedNetwork = document.getElementById('smsDetectedNetwork');
        const smsDetectedDot = document.getElementById('smsDetectedDot');
        const smsComputeBtn = document.getElementById('smsComputeBtn');
        const smsComputeResult = document.getElementById('smsComputeResult');
        const networkComputedBadge = document.getElementById('networkComputedBadge');
        const networkHelp = document.getElementById('networkHelp');

        const networkKeywords = {
            'Vodacom': ['vodacom','mpesa','m-pesa'],
            'Tigo': ['tigo','tigopesa'],
            'Airtel': ['airtel'],
            'HaloPesa': ['halopesa','halo','mixx','yas'],
        };
        const networkColors = {
            @foreach($combos['networks'] as $network)
                '{{ $network->name }}': '{{ $network->color }}',
            @endforeach
        };
        const networkIdByName = {
            @foreach($combos['networks'] as $network)
                '{{ strtolower($network->name) }}': '{{ $network->id }}',
            @endforeach
        };

        function detectNetwork(sender, body) {
            const text = (sender + ' ' + body).toLowerCase();
            for (const [name, keywords] of Object.entries(networkKeywords)) {
                for (const kw of keywords) {
                    if (text.includes(kw)) return name;
                }
            }
            return null;
        }

        if (topSelect) {
            topSelect.addEventListener('change', () => {
                const opt = topSelect.options[topSelect.selectedIndex];
                if (!opt || !opt.value) {
                    if (smsFullArea) smsFullArea.style.display = 'none';
                    return;
                }
                const sender = opt.dataset.sender || '';
                const body = opt.dataset.body || '';
                const rawTime = opt.text.split('·').pop()?.trim() || '';
                if (smsFullSender) smsFullSender.textContent = sender;
                if (smsFullBody) smsFullBody.textContent = body;
                if (smsFullTime) smsFullTime.textContent = rawTime;
                if (smsFullArea) smsFullArea.style.display = 'block';
                const detected = detectNetwork(sender, body);
                if (detected && smsDetectedNetwork) {
                    smsDetectedNetwork.textContent = detected;
                    if (smsDetectedDot) smsDetectedDot.style.background = networkColors[detected] || '#999';
                    const preview = document.getElementById('smsNetworkPreview');
                    if (preview) preview.style.display = 'block';
                } else if (smsDetectedNetwork) {
                    smsDetectedNetwork.textContent = 'Unknown — will use SMS stored network';
                    if (smsDetectedDot) smsDetectedDot.style.background = '#999';
                }
                // Sync bottom link
                const linkSel = document.getElementById('txnSmsLink');
                if (linkSel) linkSel.value = opt.value;
                const bottomSel = document.getElementById('txnSmsSelect');
                if (bottomSel) bottomSel.value = opt.value;
            });
        }

        if (smsComputeBtn) {
            smsComputeBtn.addEventListener('click', () => {
                const topOpt = topSelect ? topSelect.options[topSelect.selectedIndex] : null;
                if (!topOpt || !topOpt.value) {
                    toast('Select an SMS first', 'error');
                    return;
                }
                const sender = topOpt.dataset.sender || '';
                const body = topOpt.dataset.body || '';
                const amount = topOpt.dataset.amount;
                const type = topOpt.dataset.type;
                const phone = topOpt.dataset.phone;
                const name = topOpt.dataset.name;
                const ref = topOpt.dataset.ref;
                let networkId = topOpt.dataset.network;
                const networkName = detectNetwork(sender, body);
                const form = topSelect.closest('form');
                if (!networkId && networkName) {
                    const key = networkName.toLowerCase();
                    for (const [nName, nId] of Object.entries(networkIdByName)) {
                        if (nName.includes(key) || key.includes(nName)) { networkId = nId; break; }
                    }
                }
                if (!networkId && networkName) {
                    const sel = form.querySelector('select[name=network_id]');
                    for (const opt of sel.options) {
                        if (opt.text.toLowerCase().includes(networkName.toLowerCase())) { networkId = opt.value; break; }
                    }
                }
                if (networkId) {
                    const sel = form.querySelector('select[name=network_id]');
                    if (sel) {
                        sel.value = networkId;
                        sel.style.borderColor = 'var(--acacia-600)';
                        setTimeout(() => sel.style.borderColor = '', 1500);
                    }
                    if (networkComputedBadge) networkComputedBadge.style.display = 'inline';
                    if (networkHelp) {
                        networkHelp.textContent = 'Computed from SMS: ' + sender + ' → ' + (networkName || 'unknown');
                        networkHelp.style.color = 'var(--acacia-600)';
                    }
                    if (smsComputeResult) {
                        smsComputeResult.textContent = 'Network computed: ' + (networkName || networkId);
                        smsComputeResult.style.color = 'var(--acacia-600)';
                    }
                } else {
                    if (smsComputeResult) {
                        smsComputeResult.textContent = 'Could not detect network, please select manually';
                        smsComputeResult.style.color = 'var(--danger)';
                    }
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
                if (linkSel) linkSel.value = topOpt.value;
                toast('Computed and filled Network: ' + (networkName || networkId || 'unknown'), 'success');
            });
        }
    </script>
@endsection
