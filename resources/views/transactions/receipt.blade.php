@extends('layouts.app')

@section('title', 'Receipt '.$transaction->reference)

@section('head')
    <style>
        @page { margin: 6mm; }
        .rc-receipt{
            background:var(--white);border:1px dashed var(--coffee-300);border-radius:8px;
            padding:24px 28px;font-family:'Courier New',ui-monospace,monospace;font-size:13px;
            line-height:1.6;color:#1a1a1a;box-shadow:var(--shadow-sm);margin:0 auto; max-width:480px;
        }
        .rc-brand{text-align:center;}
        .rc-brand strong{display:block;font-size:16px;letter-spacing:.02em;color:var(--coffee-900);}
        .rc-brand span{display:block;font-size:10.5px;letter-spacing:.2em;text-transform:uppercase;color:var(--ink-soft);margin-top:2px;}
        .rc-rule{border-top:1px dashed #cdbfa8;margin:13px 0;}
        .rc-title{text-align:center;font-size:10.5px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:var(--coffee-700);}
        .rc-subtitle{text-align:center;font-size:12.5px;margin-top:1px;}
        .rc-row{display:flex;justify-content:space-between;gap:14px;padding:3px 0;align-items:baseline;}
        .rc-row span{color:var(--ink-soft);}
        .rc-row b{text-align:right;color:#1a1a1a;font-weight:700;}
        .rc-amount{text-align:center;padding:4px 0;}
        .rc-amount span{display:block;font-size:10px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:var(--ink-soft);}
        .rc-amount b{display:block;font-size:24px;color:var(--coffee-900);margin-top:2px;}
        .rc-foot{text-align:center;font-size:11px;letter-spacing:.05em;color:var(--ink-soft);}
        @media print {
            body * { visibility: hidden; }
            .rc-receipt, .rc-receipt * { visibility: visible; }
            .rc-receipt { position: fixed; left: 0; top: 0; width: 100%; max-width: 80mm; margin: 0; border: 1px dashed #999; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
@endsection

@section('content')
    <div class="view-head no-print">
        <div>
            <h2>Transaction Receipt</h2>
            <p class="sub">{{ $transaction->reference }} · {{ $transaction->created_at->format('d M Y H:i') }}</p>
            <p class="sub" style="font-size:11px; word-break:break-all; color:var(--ink-soft);">Encrypted ID: {{ $transaction->encryptedId }} · <a href="{{ route('transactions.receipt', $transaction) }}" style="color:var(--terracotta-600);">{{ route('transactions.receipt', $transaction) }}</a></p>
        </div>
        <div class="view-actions">
            <a href="{{ route('transactions.index') }}" class="btn btn-ghost">← Back to transactions</a>
            <a href="{{ route('transactions.receipt.pdf', $transaction) }}" class="btn btn-ghost">Download PDF</a>
            <button class="btn btn-primary" onclick="window.print()">Print receipt</button>
        </div>
    </div>

    <div class="rc-receipt" id="receiptCard">
        <div class="rc-brand">
            <strong>Wakala Feedtan Store</strong>
            <span>Mobile Money Services · Kiborilon Moshi Kilimanjaro</span>
            <span style="font-size:9px; margin-top:3px;">wakala@feedtanstore.com · Encrypted: {{ Str::limit($transaction->encryptedId, 28, '…') }}</span>
        </div>
        <div class="rc-rule"></div>
        <div class="rc-title">Transaction Receipt</div>
        <div class="rc-subtitle">{{ txn_type_label($transaction->type) }} · {{ $transaction->reference }}</div>
        <div class="rc-rule"></div>

        <div style="font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--terracotta-600); margin:10px 0 6px; border-bottom:1px solid var(--line); padding-bottom:4px;">Transaction Details</div>
        <div class="rc-row"><span>Reference (internal)</span><b>{{ $transaction->reference }}</b></div>
        <div class="rc-row"><span>Provider ref</span><b>{{ $transaction->provider_reference ?? '—' }}</b></div>
        <div class="rc-row"><span>Encrypted ID</span><b style="font-size:10px; word-break:break-all;">{{ Str::limit($transaction->encryptedId, 32, '…') }}</b></div>
        <div class="rc-row"><span>Date & Time</span><b>{{ $transaction->created_at->format('d M Y H:i:s') }}</b></div>
        <div class="rc-row"><span>Status</span><b>{{ strtoupper($transaction->status) }}</b></div>
        <div class="rc-row"><span>Type</span><b>{{ txn_type_label($transaction->type) }} ({{ $transaction->type }})</b></div>
        <div class="rc-row"><span>Network</span><b><span class="net-dot" style="background:{{ $transaction->network?->color ?? '#999' }};"></span>&nbsp;{{ $transaction->network?->name ?? '—' }} ({{ $transaction->network?->code ?? '—' }})</b></div>
        <div class="rc-row"><span>Agent / Cash Point</span><b>{{ $transaction->agent?->name ?? '—' }} · {{ $transaction->agent?->code ?? '' }}</b></div>
        @if($transaction->dailyOpening)
            <div class="rc-row"><span>Daily Opening</span><b>{{ $transaction->dailyOpening->opening_date->format('Y-m-d') }} · Cash {{ money($transaction->dailyOpening->cash_opening) }} · Float {{ money($transaction->dailyOpening->totalFloatOpening()) }}</b></div>
        @endif

        <div style="font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--terracotta-600); margin:12px 0 6px; border-bottom:1px solid var(--line); padding-bottom:4px;">Customer</div>
        <div class="rc-row"><span>Customer Name</span><b>{{ $transaction->customer_name ?? '—' }}</b></div>
        <div class="rc-row"><span>Phone</span><b>{{ $transaction->customer_phone }}</b></div>

        <div class="rc-amount"><span>Amount</span><b>@money($transaction->amount)</b></div>
        <div class="rc-row"><span>Fee</span><b>@money($transaction->fee)</b></div>
        <div class="rc-row"><span>Commission (agent)</span><b>@money($transaction->commission)</b></div>
        <div class="rc-row"><span>Running Cash Balance</span><b>{{ $transaction->running_cash_balance !== null ? money($transaction->running_cash_balance) : '—' }}</b></div>
        <div class="rc-row"><span>Running Float Balance</span><b>{{ $transaction->running_float_balance !== null ? money($transaction->running_float_balance) : '—' }}</b></div>

        <div style="font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--terracotta-600); margin:12px 0 6px; border-bottom:1px solid var(--line); padding-bottom:4px;">Operator & Audit</div>
        <div class="rc-row"><span>Operator</span><b>{{ $transaction->operator?->name ?? '—' }} @if($transaction->operator?->email) ({{ $transaction->operator->email }}) @endif</b></div>
        <div class="rc-row"><span>Performed at</span><b>{{ $transaction->created_at->format('d M Y H:i:s') }}</b></div>
        @if($transaction->notes)
            <div class="rc-row"><span>Notes</span><b>{{ $transaction->notes }}</b></div>
        @endif
        @if($transaction->status === 'reversed')
            <div class="rc-row"><span>Reversed by</span><b>{{ $transaction->reverser?->name ?? '—' }} at {{ $transaction->reversed_at?->format('d M Y H:i') }}</b></div>
            <div class="rc-row"><span>Reversal reason</span><b>{{ $transaction->reversal_reason ?? '—' }}</b></div>
        @endif
        <div class="rc-row"><span>Agent Cash Point</span><b>{{ $transaction->agent?->name ?? '—' }} · Phone {{ $transaction->agent?->phone ?? '—' }}</b></div>

        @if($transaction->smsMessages->isNotEmpty())
            <div style="font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--terracotta-600); margin:12px 0 6px; border-bottom:1px solid var(--line); padding-bottom:4px;">Source SMS (full detailed)</div>
            @foreach($transaction->smsMessages as $sms)
                <div class="rc-row"><span>Sender / Provider</span><b>{{ $sms->sender }} ({{ $sms->provider ?? '—' }})</b></div>
                <div class="rc-row"><span>Device</span><b>{{ $sms->device?->name ?? '—' }} · Slot {{ $sms->sim_slot ?? '—' }}</b></div>
                <div class="rc-row"><span>Received</span><b>{{ $sms->server_received_at?->format('d M Y H:i:s') ?? $sms->created_at->format('d M Y H:i:s') }}</b></div>
                <div style="background:var(--sand-100); border:1px solid var(--line); border-radius:6px; padding:10px; font-size:11.5px; white-space:pre-wrap; word-break:break-word; margin:6px 0; font-family:ui-monospace,monospace;">{{ $sms->message_body }}</div>
                <div class="rc-row"><span>SMS Status</span><b>{{ $sms->processing_status }} @if($sms->is_duplicate) · Duplicate @endif</b></div>
            @endforeach
        @endif

        <div class="rc-rule"></div>
        <div class="rc-foot">Thank you for using Wakala Feedtan Store<br><span style="font-size:8px;">Computer generated receipt · Reference: {{ $transaction->reference }} · Encrypted: {{ Str::limit($transaction->encryptedId, 24, '…') }} · {{ route('transactions.receipt', $transaction) }}</span></div>
    </div>

    <div class="no-print" style="text-align:center; margin-top:18px; display:flex; gap:10px; justify-content:center;">
        <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Back</a>
        <a href="{{ route('transactions.receipt.pdf', $transaction) }}" class="btn btn-primary">Download PDF</a>
        <button class="btn btn-ghost" onclick="window.print()">Print</button>
    </div>
@endsection
