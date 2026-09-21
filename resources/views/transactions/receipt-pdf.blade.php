<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 12mm; size: A4; }
    body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; font-size: 11px; line-height: 1.5; }
    .brand { text-align: center; border-bottom: 2px solid #C2592B; padding-bottom: 10px; margin-bottom: 14px; }
    .brand strong { font-size: 16px; color: #2A1B10; display: block; }
    .brand span { font-size: 9px; letter-spacing: .2em; text-transform: uppercase; color: #6B5A48; }
    .title { text-align: center; font-size: 11px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; color: #C2592B; margin: 8px 0 2px; }
    .subtitle { text-align: center; font-size: 12px; margin-bottom: 10px; }
    .rule { border-top: 1px dashed #cdbfa8; margin: 10px 0; }
    .row { display: flex; justify-content: space-between; padding: 3px 0; }
    .row span { color: #6B5A48; }
    .row b { text-align: right; }
    .amount { text-align: center; padding: 8px 0; border: 1px dashed #cdbfa8; border-radius: 6px; margin: 10px 0; }
    .amount span { font-size: 9px; letter-spacing: .2em; text-transform: uppercase; color: #6B5A48; display: block; }
    .amount b { font-size: 20px; color: #2A1B10; display: block; margin-top: 2px; }
    .section { margin: 14px 0 6px; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #C2592B; border-bottom: 1px solid #E4D7C2; padding-bottom: 4px; }
    .grid { display: table; width: 100%; }
    .grid-row { display: table-row; }
    .grid-cell { display: table-cell; width: 50%; padding: 3px 8px 3px 0; }
    .footer { text-align: center; font-size: 9px; color: #6B5A48; margin-top: 14px; border-top: 1px dashed #cdbfa8; padding-top: 10px; }
    .meta { font-size: 8px; color: #6B5A48; text-align: center; margin-top: 6px; word-break: break-all; }
</style>
</head>
<body>
    <div class="brand">
        <strong>Wakala Feedtan Store</strong>
        <span>Mobile Money Services · Kiborilon Moshi Kilimanjaro</span>
        <div style="font-size:8px; color:#6B5A48; margin-top:3px;">wakala@feedtanstore.com · +255 7xx xxx xxx</div>
    </div>
    <div class="title">Transaction Receipt</div>
    <div class="subtitle">{{ txn_type_label($transaction->type) }} · {{ $transaction->reference }}</div>
    <div class="rule"></div>

    <div class="section">Transaction Details</div>
    <div class="row"><span>Reference (internal)</span><b>{{ $transaction->reference }}</b></div>
    <div class="row"><span>Provider reference</span><b>{{ $transaction->provider_reference ?? '—' }}</b></div>
    <div class="row"><span>Date & Time</span><b>{{ $transaction->created_at->format('d M Y H:i:s') }}</b></div>
    <div class="row"><span>Status</span><b>{{ strtoupper($transaction->status) }}</b></div>
    <div class="row"><span>Type</span><b>{{ txn_type_label($transaction->type) }} ({{ $transaction->type }})</b></div>
    <div class="row"><span>Network</span><b>{{ $transaction->network?->name ?? '—' }} ({{ $transaction->network?->code ?? '—' }})</b></div>
    <div class="row"><span>Agent / Cash Point</span><b>{{ $transaction->agent?->name ?? '—' }} · {{ $transaction->agent?->code ?? '' }}</b></div>
    @if($transaction->dailyOpening)
        <div class="row"><span>Daily Opening</span><b>{{ $transaction->dailyOpening->opening_date->format('Y-m-d') }} · Cash {{ money($transaction->dailyOpening->cash_opening) }}</b></div>
    @endif

    <div class="section">Customer</div>
    <div class="row"><span>Customer Name</span><b>{{ $transaction->customer_name ?? '—' }}</b></div>
    <div class="row"><span>Phone</span><b>{{ $transaction->customer_phone }}</b></div>

    <div class="amount"><span>Amount</span><b>{{ money($transaction->amount) }}</b></div>
    <div class="row"><span>Fee</span><b>{{ money($transaction->fee) }}</b></div>
    <div class="row"><span>Commission (agent)</span><b>{{ money($transaction->commission) }}</b></div>
    <div class="row"><span>Running Cash Balance</span><b>{{ $transaction->running_cash_balance !== null ? money($transaction->running_cash_balance) : '—' }}</b></div>
    <div class="row"><span>Running Float Balance</span><b>{{ $transaction->running_float_balance !== null ? money($transaction->running_float_balance) : '—' }}</b></div>

    <div class="section">Operator & Audit</div>
    <div class="row"><span>Operator</span><b>{{ $transaction->operator?->name ?? '—' }} ({{ $transaction->operator?->email ?? '' }})</b></div>
    <div class="row"><span>Performed at</span><b>{{ $transaction->created_at->format('d M Y H:i:s') }}</b></div>
    @if($transaction->notes)
        <div class="row"><span>Notes</span><b>{{ $transaction->notes }}</b></div>
    @endif
    @if($transaction->status === 'reversed')
        <div class="row"><span>Reversed by</span><b>{{ $transaction->reverser?->name ?? '—' }} at {{ $transaction->reversed_at?->format('d M Y H:i') }}</b></div>
        <div class="row"><span>Reversal reason</span><b>{{ $transaction->reversal_reason ?? '—' }}</b></div>
    @endif

    @if($transaction->smsMessages->isNotEmpty())
        <div class="section">Source SMS</div>
        @foreach($transaction->smsMessages as $sms)
            <div class="row"><span>Sender</span><b>{{ $sms->sender }} ({{ $sms->provider ?? '—' }})</b></div>
            <div class="row"><span>Received</span><b>{{ $sms->server_received_at?->format('d M Y H:i:s') ?? $sms->created_at->format('d M Y H:i:s') }}</b></div>
            <div style="background:#FBF7EF; border:1px solid #E4D7C2; border-radius:6px; padding:8px; font-size:10px; white-space:pre-wrap; word-break:break-word; margin:6px 0;">{{ $sms->message_body }}</div>
        @endforeach
    @endif

    <div class="footer">
        Thank you for using Wakala Feedtan Store<br>
        <span style="font-size:8px;">This is a computer generated receipt. Reference: {{ $transaction->reference }} · {{ $transaction->created_at->format('d M Y H:i:s') }}</span>
    </div>
</body>
</html>
