@extends('layouts.app')

@section('title', 'Receipt '.$transaction->reference)

@section('content')
    <div class="view-head">
        <div>
            <h2>Transaction Receipt</h2>
            <p class="sub">{{ $transaction->reference }} · {{ $transaction->created_at->format('d M Y H:i') }}</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('transactions.index') }}" class="btn btn-ghost">← Back</a>
            <a href="{{ route('transactions.receipt.pdf', $transaction) }}" class="btn btn-ghost">Download PDF</a>
            <button class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Transaction Details</h3>
                <span class="tag {{ status_badge($transaction->status) }}">{{ ucfirst($transaction->status) }}</span>
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Reference (internal)</div><div class="dv">{{ $transaction->reference }}</div></div>
                    <div class="detail-item"><div class="dk">Provider reference</div><div class="dv">{{ $transaction->provider_reference ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Date & Time</div><div class="dv">{{ $transaction->created_at->format('d M Y H:i:s') }}</div></div>
                    <div class="detail-item"><div class="dk">Type</div><div class="dv">{{ txn_type_label($transaction->type) }} ({{ $transaction->type }})</div></div>
                    <div class="detail-item"><div class="dk">Network</div><div class="dv"><span class="net-dot" style="background:{{ $transaction->network?->color ?? '#999' }};"></span> {{ $transaction->network?->name ?? '—' }} ({{ $transaction->network?->code ?? '—' }})</div></div>
                    <div class="detail-item"><div class="dk">Agent / Cash Point</div><div class="dv">{{ $transaction->agent?->name ?? '—' }} · {{ $transaction->agent?->code ?? '' }}</div></div>
                    @if($transaction->dailyOpening)
                        <div class="detail-item"><div class="dk">Daily Opening</div><div class="dv">{{ $transaction->dailyOpening->opening_date->format('Y-m-d') }} · Cash {{ money($transaction->dailyOpening->cash_opening) }}</div></div>
                    @endif
                    <div class="detail-item"><div class="dk">Status</div><div class="dv">{{ strtoupper($transaction->status) }}</div></div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Customer & Amount</h3>
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Customer Name</div><div class="dv">{{ $transaction->customer_name ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Phone</div><div class="dv">{{ $transaction->customer_phone }}</div></div>
                    <div class="detail-item"><div class="dk">Amount</div><div class="dv" style="font-size:18px; font-weight:700; color:var(--coffee-900);">@money($transaction->amount)</div></div>
                    <div class="detail-item"><div class="dk">Fee</div><div class="dv">@money($transaction->fee)</div></div>
                    <div class="detail-item"><div class="dk">Commission (agent)</div><div class="dv">@money($transaction->commission)</div></div>
                    <div class="detail-item"><div class="dk">Running Cash Balance</div><div class="dv">{{ $transaction->running_cash_balance !== null ? money($transaction->running_cash_balance) : '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Running Float Balance</div><div class="dv">{{ $transaction->running_float_balance !== null ? money($transaction->running_float_balance) : '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Operator</div><div class="dv">{{ $transaction->operator?->name ?? auth()->user()->name }}</div></div>
                </div>
                @if($transaction->status === 'reversed')
                    <div style="margin-top:16px; padding:12px; background:var(--danger-100); border-radius:8px; font-size:13px;">
                        <strong>Reversed by {{ $transaction->reverser?->name ?? '—' }}</strong> at {{ $transaction->reversed_at?->format('d M Y H:i') }}<br>
                        <span style="color:var(--ink-soft);">Reason: {{ $transaction->reversal_reason ?? '—' }}</span>
                    </div>
                @endif
                @if($transaction->notes)
                    <div style="margin-top:12px; font-size:13px;"><strong>Notes:</strong> {{ $transaction->notes }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Full Details</h3>
            <span class="link">{{ $transaction->reference }}</span>
        </div>
        <div class="panel-body">
            <div class="detail-grid">
                <div class="detail-item"><div class="dk">Agent Cash Point</div><div class="dv">{{ $transaction->agent?->name ?? '—' }} · Phone {{ $transaction->agent?->phone ?? '—' }} · Code {{ $transaction->agent?->code ?? '—' }}</div></div>
                <div class="detail-item"><div class="dk">Network</div><div class="dv">{{ $transaction->network?->name ?? '—' }} · {{ $transaction->network?->code ?? '—' }}</div></div>
                <div class="detail-item"><div class="dk">Daily Opening Cash</div><div class="dv">{{ $transaction->dailyOpening?->cash_opening !== null ? money($transaction->dailyOpening->cash_opening) : '—' }}</div></div>
                <div class="detail-item"><div class="dk">Daily Opening Float</div><div class="dv">{{ $transaction->dailyOpening ? money($transaction->dailyOpening->totalFloatOpening()) : '—' }}</div></div>
                <div class="detail-item"><div class="dk">Performed at</div><div class="dv">{{ $transaction->created_at->format('d M Y H:i:s') }}</div></div>
                <div class="detail-item"><div class="dk">Updated at</div><div class="dv">{{ $transaction->updated_at->format('d M Y H:i:s') }}</div></div>
            </div>
            @if($transaction->smsMessages->isNotEmpty())
                <div style="margin-top:18px; padding-top:14px; border-top:1px solid var(--line);">
                    <h4 style="font-size:13px; font-weight:700; color:var(--coffee-900); margin-bottom:10px;">Source SMS (full detailed)</h4>
                    @foreach($transaction->smsMessages as $sms)
                        <div style="background:var(--sand-100); border:1px solid var(--line); border-radius:8px; padding:14px; margin-bottom:12px;">
                            <div class="detail-grid" style="margin-bottom:8px;">
                                <div class="detail-item"><div class="dk">Sender / Provider</div><div class="dv">{{ $sms->sender }} ({{ $sms->provider ?? '—' }})</div></div>
                                <div class="detail-item"><div class="dk">Device</div><div class="dv">{{ $sms->device?->name ?? '—' }} · Slot {{ $sms->sim_slot ?? '—' }}</div></div>
                                <div class="detail-item"><div class="dk">Received</div><div class="dv">{{ $sms->server_received_at?->format('d M Y H:i:s') ?? $sms->created_at->format('d M Y H:i:s') }}</div></div>
                                <div class="detail-item"><div class="dk">Status</div><div class="dv"><span class="tag {{ sms_status_badge($sms->processing_status, $sms->is_duplicate, $sms->processing_error) }}">{{ sms_status_label($sms->processing_status, $sms->is_duplicate, $sms->processing_error) }}</span></div></div>
                            </div>
                            <div style="background:var(--white); border:1px solid var(--line); border-radius:6px; padding:10px; font-size:12.5px; white-space:pre-wrap; word-break:break-word; font-family:ui-monospace,monospace;">{{ $sms->message_body }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Receipt Preview</h3>
            <div style="display:flex; gap:8px;">
                <a href="{{ route('transactions.receipt.pdf', $transaction) }}" class="btn btn-ghost btn-sm">Download PDF</a>
                <button class="btn btn-primary btn-sm" onclick="window.print()">Print</button>
            </div>
        </div>
        <div class="panel-body" style="display:flex; justify-content:center; background:var(--sand-50); padding:24px;">
            <div class="rc-receipt" style="background:var(--white); border:1px dashed var(--coffee-300); border-radius:8px; padding:24px 28px; font-family:'Courier New',ui-monospace,monospace; font-size:13px; line-height:1.6; color:#1a1a1a; box-shadow:var(--shadow-sm); max-width:480px; width:100%;">
                <div style="text-align:center;">
                    <strong style="display:block; font-size:16px; color:var(--coffee-900);">Wakala Feedtan Store</strong>
                    <span style="display:block; font-size:10.5px; letter-spacing:.2em; text-transform:uppercase; color:var(--ink-soft);">Mobile Money Services</span>
                </div>
                <div style="border-top:1px dashed #cdbfa8; margin:13px 0;"></div>
                <div style="text-align:center; font-size:10.5px; font-weight:700; letter-spacing:.28em; text-transform:uppercase; color:var(--coffee-700);">Transaction Receipt</div>
                <div style="text-align:center; font-size:12.5px;">{{ txn_type_label($transaction->type) }}</div>
                <div style="border-top:1px dashed #cdbfa8; margin:13px 0;"></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink-soft);">Reference</span><b>{{ $transaction->reference }}</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink-soft);">Provider ref</span><b>{{ $transaction->provider_reference ?? '—' }}</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink-soft);">Date</span><b>{{ $transaction->created_at->format('d M Y H:i') }}</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink-soft);">Network</span><b><span class="net-dot" style="background:{{ $transaction->network?->color ?? '#999' }};"></span>&nbsp;{{ $transaction->network?->name ?? '—' }}</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink-soft);">Customer</span><b>{{ $transaction->customer_name ?? '—' }}</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink-soft);">Phone</span><b>{{ $transaction->customer_phone }}</b></div>
                <div style="border-top:1px dashed #cdbfa8; margin:10px 0;"></div>
                <div style="text-align:center;"><span style="font-size:10px; letter-spacing:.24em; text-transform:uppercase; color:var(--ink-soft);">Amount</span><b style="display:block; font-size:24px; color:var(--coffee-900);">@money($transaction->amount)</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink-soft);">Fee</span><b>@money($transaction->fee)</b></div>
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink-soft);">Commission</span><b>@money($transaction->commission)</b></div>
                <div style="text-align:center; font-size:11px; color:var(--ink-soft); margin-top:10px;">Thank you for using Wakala Feedtan Store</div>
            </div>
        </div>
    </div>
@endsection
