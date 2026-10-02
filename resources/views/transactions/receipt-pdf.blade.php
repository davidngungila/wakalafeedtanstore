<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 0; size: A4 portrait; }

    * { box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        color: #2A1B10;
        font-size: 10px;
        line-height: 1.45;
        margin: 0;
    }

    /* ---- Palette (matches the application) ---- */
    .ink          { color: #2A1B10; }
    .ink-soft     { color: #6B5A48; }
    .terracotta   { color: #C2592B; }
    .green        { color: #4F6231; }
    .red          { color: #B33A3A; }
    .gold         { color: #8A6418; }

    /* ---- Masthead ---- */
    .masthead {
        background: #2A1B10;
        color: #fff;
        padding: 14px 18px 12px;
    }
    .masthead-top { display: table; width: 100%; }
    .masthead-l { display: table-cell; vertical-align: top; width: 62%; }
    .masthead-r { display: table-cell; vertical-align: top; text-align: right; width: 38%; }
    .brand { font-size: 15px; font-weight: bold; letter-spacing: .04em; }
    .brand-sub { font-size: 7.5px; letter-spacing: .22em; text-transform: uppercase; color: #D9C4AC; margin-top: 2px; }
    .contact { font-size: 8px; color: #D9C4AC; margin-top: 5px; line-height: 1.5; }
    .doc-title { font-size: 13px; font-weight: bold; letter-spacing: .18em; text-transform: uppercase; }
    .doc-sub { font-size: 8px; color: #D9C4AC; margin-top: 3px; letter-spacing: .06em; }

    .status-pill {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 3px;
        font-size: 8px;
        font-weight: bold;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #fff;
    }
    .status-completed { background: #4F6231; }
    .status-pending   { background: #C89A2E; }
    .status-failed,
    .status-reversed  { background: #B33A3A; }

    /* ---- Layout ---- */
    .sheet { padding: 0 18px 18px; }

    /* ---- Amount hero ---- */
    .hero {
        border: 1px solid #E4D7C2;
        border-top: 3px solid #C2592B;
        background: #FBF7EF;
        padding: 12px 14px;
        margin: 14px 0 12px;
    }
    .hero-label { font-size: 8px; letter-spacing: .2em; text-transform: uppercase; color: #6B5A48; }
    .hero-amount { font-size: 24px; font-weight: bold; color: #2A1B10; margin-top: 2px; }
    .hero-words { font-size: 8.5px; color: #6B5A48; font-style: italic; margin-top: 4px; }
    .hero-type { font-size: 11px; font-weight: bold; color: #C2592B; margin-top: 6px; letter-spacing: .04em; }

    /* ---- Alerts ---- */
    .alert {
        border-radius: 3px;
        padding: 8px 10px;
        font-size: 8.5px;
        margin-bottom: 10px;
    }
    .alert-unusual { background: #FDF4E0; border-left: 3px solid #C89A2E; color: #6B4E12; }
    .alert-reversed { background: #FBEBEB; border-left: 3px solid #B33A3A; color: #8A2020; }

    /* ---- Sections ---- */
    .section {
        margin: 12px 0 5px;
        font-size: 8px;
        font-weight: bold;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: #C2592B;
        border-bottom: 1px solid #E4D7C2;
        padding-bottom: 3px;
    }

    /* ---- Key/value grid ---- */
    .kv { display: table; width: 100%; table-layout: fixed; }
    .kv-row { display: table-row; }
    .kv-cell { display: table-cell; padding: 3px 8px 3px 0; vertical-align: top; width: 50%; }
    .kv-label { font-size: 8px; color: #6B5A48; text-transform: uppercase; letter-spacing: .05em; }
    .kv-value { font-size: 10px; color: #2A1B10; font-weight: bold; word-break: break-word; }
    .kv-value.mono { font-family: DejaVu Sans Mono, monospace; font-size: 9px; font-weight: normal; }

    /* ---- Ledger table ---- */
    .ledger { display: table; width: 100%; border-collapse: collapse; margin-top: 3px; }
    .ledger-row { display: table-row; }
    .ledger-cell { display: table-cell; padding: 4px 6px; border-bottom: 1px solid #EFE6D6; font-size: 9.5px; }
    .ledger-head .ledger-cell {
        background: #F5EEE1;
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: #6B5A48;
        font-weight: bold;
        border-bottom: 1px solid #E4D7C2;
    }
    .num { text-align: right; white-space: nowrap; }
    .pos { color: #4F6231; font-weight: bold; }
    .neg { color: #B33A3A; font-weight: bold; }
    .zero { color: #A89880; }

    /* ---- SMS evidence ---- */
    .sms {
        background: #FBF7EF;
        border: 1px solid #E4D7C2;
        border-radius: 3px;
        padding: 8px 10px;
        margin-bottom: 8px;
    }
    .sms-head { font-size: 8px; color: #6B5A48; margin-bottom: 4px; }
    .sms-body {
        font-family: DejaVu Sans Mono, monospace;
        font-size: 8.5px;
        background: #fff;
        border: 1px solid #E4D7C2;
        border-radius: 2px;
        padding: 6px 8px;
        white-space: pre-wrap;
        word-wrap: break-word;
        word-break: break-all;
        line-height: 1.4;
    }

    /* ---- Signature ---- */
    .sign-grid { display: table; width: 100%; margin-top: 6px; }
    .sign-cell { display: table-cell; width: 50%; padding-right: 18px; }
    .sign-line { border-bottom: 1px solid #2A1B10; height: 22px; }
    .sign-cap { font-size: 7.5px; color: #6B5A48; text-transform: uppercase; letter-spacing: .1em; margin-top: 3px; }

    /* ---- Footer ---- */
    .footer {
        margin-top: 14px;
        border-top: 1px solid #E4D7C2;
        padding-top: 8px;
        text-align: center;
    }
    .footer-thanks { font-size: 10px; font-weight: bold; color: #2A1B10; }
    .footer-note { font-size: 7.5px; color: #6B5A48; margin-top: 3px; line-height: 1.5; }

    .verify {
        display: table;
        width: 100%;
        border: 1px dashed #C9B79B;
        border-radius: 3px;
        margin-top: 10px;
    }
    .verify-l { display: table-cell; padding: 7px 10px; width: 66%; font-size: 7.5px; color: #6B5A48; line-height: 1.5; }
    .verify-r {
        display: table-cell;
        padding: 7px 10px;
        width: 34%;
        text-align: center;
        background: #F5EEE1;
        border-left: 1px dashed #C9B79B;
    }
    .verify-code { font-family: DejaVu Sans Mono, monospace; font-size: 14px; font-weight: bold; letter-spacing: .12em; color: #2A1B10; }
</style>
</head>
<body>

<div class="masthead">
    <div class="masthead-top">
        <div class="masthead-l">
            <div class="brand">{{ $business['name'] }}</div>
            <div class="brand-sub">Mobile Money Services</div>
            <div class="contact">
                {{ $business['address'] }}<br>
                {{ $business['phone'] }} &nbsp;·&nbsp; {{ $business['email'] }}
            </div>
        </div>
        <div class="masthead-r">
            <div class="doc-title">Receipt</div>
            <div class="doc-sub">{{ $transaction->reference }}</div>
            <div style="margin-top:7px;">
                <span class="status-pill status-{{ $transaction->status }}">{{ $transaction->status }}</span>
            </div>
        </div>
    </div>
</div>

<div class="sheet">

    @if($transaction->is_unusual)
        <div class="alert alert-unusual">
            <b>FLAGGED AS UNUSUAL.</b> {{ $transaction->unusual_reason ?? 'No reason recorded.' }}
            This transaction was marked for supervisor review.
        </div>
    @endif

    @if($transaction->status === 'reversed')
        <div class="alert alert-reversed">
            <b>THIS TRANSACTION HAS BEEN REVERSED.</b>
            It no longer represents completed business.
            @if($transaction->reverser)
                Reversed by {{ $transaction->reverser->name }} on {{ $transaction->reversed_at?->format('d M Y H:i') }}.
            @endif
        </div>
    @endif

    <div class="hero">
        <div class="hero-label">{{ $transaction->status === 'reversed' ? 'Reversed Amount' : 'Amount' }}</div>
        <div class="hero-amount">{{ money($transaction->amount) }}</div>
        <div class="hero-words">{{ ucfirst($amountWords) }} Shilling{{ ((int) round((float) $transaction->amount)) === 1 ? '' : 's' }} Only</div>
        <div class="hero-type">{{ txn_type_label($transaction->type) }}</div>
    </div>

    <div class="section">Transaction</div>
    <div class="kv">
        <div class="kv-row">
            <div class="kv-cell">
                <div class="kv-label">Reference</div>
                <div class="kv-value mono">{{ $transaction->reference }}</div>
            </div>
            <div class="kv-cell">
                <div class="kv-label">Provider reference</div>
                <div class="kv-value mono">{{ $transaction->provider_reference ?? '—' }}</div>
            </div>
        </div>
        <div class="kv-row">
            <div class="kv-cell">
                <div class="kv-label">Date &amp; time</div>
                <div class="kv-value">{{ $transaction->created_at->format('d M Y, H:i:s') }}</div>
            </div>
            <div class="kv-cell">
                <div class="kv-label">Network</div>
                <div class="kv-value">{{ $transaction->network?->name ?? '—' }}</div>
            </div>
        </div>
        <div class="kv-row">
            <div class="kv-cell">
                <div class="kv-label">Agent / cash point</div>
                <div class="kv-value">{{ $transaction->agent?->name ?? '—' }}</div>
            </div>
            <div class="kv-cell">
                <div class="kv-label">Agent code</div>
                <div class="kv-value">{{ $transaction->agent?->code ?? '—' }}</div>
            </div>
        </div>
        <div class="kv-row">
            <div class="kv-cell">
                <div class="kv-label">Business date (shift)</div>
                <div class="kv-value">{{ \Illuminate\Support\Carbon::parse($shift['date'])->format('d M Y') }} &nbsp;·&nbsp; {{ ucfirst($shift['key']) }} shift</div>
            </div>
            <div class="kv-cell">
                <div class="kv-label">Shift window</div>
                <div class="kv-value">{{ $shift['window'] }}</div>
            </div>
        </div>
    </div>

    <div class="section">Customer</div>
    <div class="kv">
        <div class="kv-row">
            <div class="kv-cell">
                <div class="kv-label">Name</div>
                <div class="kv-value">{{ $transaction->customer_name ?? '—' }}</div>
            </div>
            <div class="kv-cell">
                <div class="kv-label">Phone</div>
                <div class="kv-value">{{ $transaction->customer_phone ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="section">Income &amp; effects</div>
    <div class="ledger">
        <div class="ledger-row ledger-head">
            <div class="ledger-cell">Item</div>
            <div class="ledger-cell num">Amount</div>
        </div>
        <div class="ledger-row">
            <div class="ledger-cell">Transaction amount</div>
            <div class="ledger-cell num">{{ money($transaction->amount) }}</div>
        </div>
        <div class="ledger-row">
            <div class="ledger-cell">Service fee charged</div>
            <div class="ledger-cell num">{{ money($transaction->fee) }}</div>
        </div>
        <div class="ledger-row">
            <div class="ledger-cell">Commission earned by agent</div>
            <div class="ledger-cell num">{{ money($transaction->commission) }}</div>
        </div>
        <div class="ledger-row">
            <div class="ledger-cell">Effect on cash at till</div>
            <div class="ledger-cell num">
                @if($cashDelta > 0)<span class="pos">+{{ money($cashDelta) }}</span>
                @elseif($cashDelta < 0)<span class="neg">−{{ money(abs($cashDelta)) }}</span>
                @else<span class="zero">No change</span>@endif
            </div>
        </div>
        <div class="ledger-row">
            <div class="ledger-cell">Effect on {{ $transaction->network?->name ?? 'network' }} float</div>
            <div class="ledger-cell num">
                @if($floatDelta > 0)<span class="pos">+{{ money($floatDelta) }}</span>
                @elseif($floatDelta < 0)<span class="neg">−{{ money(abs($floatDelta)) }}</span>
                @else<span class="zero">No change</span>@endif
            </div>
        </div>
    </div>

    <div class="section">Balances after this transaction</div>
    <div class="kv">
        <div class="kv-row">
            <div class="kv-cell">
                <div class="kv-label">Cash at till</div>
                <div class="kv-value">{{ $transaction->running_cash_balance !== null ? money($transaction->running_cash_balance) : '—' }}</div>
            </div>
            <div class="kv-cell">
                <div class="kv-label">Float — {{ $transaction->network?->name ?? 'network' }}</div>
                <div class="kv-value">{{ $transaction->running_network_balance !== null ? money($transaction->running_network_balance) : '—' }}</div>
            </div>
        </div>
        <div class="kv-row">
            <div class="kv-cell">
                <div class="kv-label">Float — all networks</div>
                <div class="kv-value">{{ $transaction->running_float_balance !== null ? money($transaction->running_float_balance) : '—' }}</div>
            </div>
            <div class="kv-cell">
                <div class="kv-label">Daily opening cash</div>
                <div class="kv-value">{{ $transaction->dailyOpening ? money($transaction->dailyOpening->cash_opening) : '—' }}</div>
            </div>
        </div>
    </div>

    <div class="section">Operator &amp; audit trail</div>
    <div class="kv">
        <div class="kv-row">
            <div class="kv-cell">
                <div class="kv-label">Operator</div>
                <div class="kv-value">{{ $transaction->operator?->name ?? '—' }}</div>
            </div>
            <div class="kv-cell">
                <div class="kv-label">Recorded</div>
                <div class="kv-value">{{ $transaction->created_at->format('d M Y, H:i') }}</div>
            </div>
        </div>
        @if($transaction->notes)
            <div class="kv-row">
                <div class="kv-cell" style="width:100%;">
                    <div class="kv-label">Notes</div>
                    <div class="kv-value" style="font-weight:normal;">{{ $transaction->notes }}</div>
                </div>
            </div>
        @endif
        @if($transaction->status === 'reversed')
            <div class="kv-row">
                <div class="kv-cell" style="width:100%;">
                    <div class="kv-label">Reversal reason</div>
                    <div class="kv-value" style="font-weight:normal;">{{ $transaction->reversal_reason ?? '—' }}</div>
                </div>
            </div>
        @endif
    </div>

    @if($transaction->smsMessages->isNotEmpty())
        <div class="section">Source SMS evidence</div>
        @foreach($transaction->smsMessages as $sms)
            <div class="sms">
                <div class="sms-head">
                    {{ $sms->sender ?? 'Unknown sender' }}
                    @if($sms->provider) &nbsp;·&nbsp; {{ $sms->provider }} @endif
                    &nbsp;·&nbsp; {{ $sms->server_received_at?->format('d M Y, H:i') ?? $sms->created_at->format('d M Y, H:i') }}
                    @if($sms->processing_status) &nbsp;·&nbsp; {{ ucfirst(str_replace('_', ' ', $sms->processing_status)) }} @endif
                    @if($sms->device) &nbsp;·&nbsp; {{ $sms->device->name }} @endif
                </div>
                <div class="sms-body">{{ $sms->message_body }}</div>
            </div>
        @endforeach
    @endif

    @if($transaction->status === 'completed')
        <div class="section">Handover</div>
        <div class="sign-grid">
            <div class="sign-cell">
                <div class="sign-line"></div>
                <div class="sign-cap">Customer signature</div>
            </div>
            <div class="sign-cell">
                <div class="sign-line"></div>
                <div class="sign-cap">Agent signature</div>
            </div>
        </div>
    @endif

    <div class="verify">
        <div class="verify-l">
            <b>Verification code {{ $verification }}</b><br>
            Quote this code together with the reference to have this receipt looked up.
            This is a computer generated document.
        </div>
        <div class="verify-r">
            <div style="font-size:7px;color:#6B5A48;text-transform:uppercase;letter-spacing:.1em;">Printed</div>
            <div style="font-size:9px;font-weight:bold;margin-top:2px;">{{ $printedAt->format('d M Y') }}</div>
            <div style="font-size:8px;color:#6B5A48;">{{ $printedAt->format('H:i') }}</div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-thanks">Thank you for choosing {{ $business['name'] }}</div>
        <div class="footer-note">
            Reference {{ $transaction->reference }} &nbsp;·&nbsp; {{ $transaction->created_at->format('d M Y H:i:s') }}
            &nbsp;·&nbsp; Verification {{ $verification }}<br>
            Keep this receipt for your records. Figures reflect the system position at the time of printing.
        </div>
    </div>

</div>
</body>
</html>