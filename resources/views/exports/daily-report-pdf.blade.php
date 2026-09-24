<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 12mm 10mm 14mm 10mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1a1a1a; line-height: 1.4; }
    .header { border-bottom: 3px solid #C2592B; padding-bottom: 8px; margin-bottom: 10px; }
    .business-name { font-size: 14px; font-weight: 700; color: #C2592B; }
    .business-meta { font-size: 7px; color: #7A5C42; }
    .title { font-size: 13px; font-weight: 700; margin: 4px 0 2px; }
    .subtitle { font-size: 8px; color: #6B5A48; margin-bottom: 8px; }
    .section { margin-top: 10px; }
    .section-title { font-size: 9px; font-weight: 700; color: #5E6E3F; border-bottom: 1px solid #E5DDD0; padding-bottom: 3px; margin-bottom: 6px; }
    table { width: 100%; border-collapse: collapse; font-size: 7px; }
    thead th { background: #5E6E3F; color: #fff; font-weight: 700; text-transform: uppercase; font-size: 6px; padding: 5px 4px; border: 1px solid #4a5532; text-align: left; }
    tbody td { padding: 4px; border: 1px solid #E5DDD0; }
    tfoot td { background: #5E6E3F; color: #fff; font-weight: 700; padding: 5px 4px; border: 1px solid #4a5532; }
    .badge { display:inline-block; padding:1px 5px; border-radius:10px; font-size:6px; font-weight:700; }
    .badge-green { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
    .badge-gold { background:#FFF8E1; color:#8a6418; border:1px solid #FFECB3; }
    .badge-red { background:#FFEBEE; color:#B33A3A; border:1px solid #FFCDD2; }
    .footer { position: fixed; bottom: -10mm; left:0; right:0; text-align:center; font-size:6px; color:#7A5C42; border-top:1px solid #E5DDD0; padding-top:4px; }
</style>
</head>
<body>
<div class="header">
    <div class="business-name">{{ $business['name'] }}</div>
    <div class="business-meta">{{ $business['address'] }} | {{ $business['phone'] }} | {{ $business['email'] }}</div>
</div>

<div class="title">Daily Report — {{ $day->format('l, d M Y') }}</div>
<div class="subtitle">Generated {{ now()->format('d M Y H:i') }} — Agent {{ $cashPoint?->code ?? '—' }} {{ $cashPoint?->name ?? '' }}</div>

@if($opening)
<div class="section">
    <div class="section-title">Opening — {{ $dayStr }}</div>
    <table>
        <thead><tr><th>Cash Opening</th><th>Float Total</th><th>Txs</th><th>Vol</th><th>Status</th></tr></thead>
        <tbody>
            <tr>
                <td>{{ money($opening->cash_opening) }}</td>
                <td>{{ money($opening->totalFloatOpening()) }}</td>
                <td>{{ $opening->total_transactions }} txs</td>
                <td>{{ money($opening->total_volume) }}</td>
                <td><span class="badge {{ $opening->is_closed ? 'badge-green' : 'badge-gold' }}">{{ $opening->is_closed ? 'Closed' : 'Open' }}</span></td>
            </tr>
        </tbody>
    </table>
    <div style="margin-top:4px; font-size:7px; color:#7A5C42;">
        @foreach(\App\Models\Network::orderBy('name')->get(['id','name']) as $net)
            @php $amt = $opening->float_openings[$net->id] ?? 0; @endphp
            <span style="display:inline-block; border:1px solid #E5DDD0; border-radius:3px; padding:1px 4px; margin:1px 3px 1px 0;">{{ $net->name }}: {{ money($amt) }}</span>
        @endforeach
    </div>
</div>
@else
<div class="section"><div style="padding:8px; background:#FFF8E1; border:1px solid #E5DDD0; border-radius:4px; font-size:7px;">No Daily Opening for {{ $dayStr }} — fallback to previous closing/live balances.</div></div>
@endif

<div class="section">
    <div class="section-title">Customer Transactions — {{ $dayStr }} ({{ $transactions->count() }})</div>
    @if($transactions->isEmpty())
        <div style="padding:8px; border:1px dashed #E5DDD0; border-radius:4px; text-align:center; color:#7A5C42; font-size:7px;">No customer transactions for {{ $dayStr }}.</div>
    @else
    <table>
        <thead><tr><th>Time</th><th>Reference</th><th>Type</th><th>Network</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($transactions as $t)
            <tr>
                <td>{{ $t->created_at->format('H:i') }}</td>
                <td>{{ $t->reference }}<br><span style="color:#7A5C42; font-size:6px;">{{ $t->provider_reference ?? '' }}</span></td>
                <td>{{ $t->type }}</td>
                <td>{{ $t->network?->name ?? '—' }}</td>
                <td style="text-align:right;">{{ money($t->amount) }}</td>
                <td><span class="badge {{ $t->status === 'completed' ? 'badge-green' : 'badge-gold' }}">{{ ucfirst($t->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="4" style="text-align:right;">Totals</td><td style="text-align:right;">{{ money($transactions->sum('amount')) }}</td><td>{{ $transactions->count() }} txs</td></tr>
        </tfoot>
    </table>
    @endif
</div>

<div class="section">
    <div class="section-title">Float Movements — {{ $dayStr }} ({{ $floatTransactions->count() }})</div>
    @if($floatTransactions->isEmpty())
        <div style="padding:8px; border:1px dashed #E5DDD0; border-radius:4px; text-align:center; color:#7A5C42; font-size:7px;">No float movements for {{ $dayStr }}.</div>
    @else
    <table>
        <thead><tr><th>Time</th><th>Reference</th><th>Network</th><th>Type</th><th style="text-align:right;">Amount</th></tr></thead>
        <tbody>
            @foreach($floatTransactions as $ft)
            <tr>
                <td>{{ $ft->created_at->format('H:i') }}</td>
                <td>{{ $ft->reference }}</td>
                <td>{{ $ft->network?->name ?? '—' }}</td>
                <td>{{ str_replace('_',' ',$ft->type) }}</td>
                <td style="text-align:right;">{{ money($ft->amount) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="4" style="text-align:right;">Total float</td><td style="text-align:right;">{{ money($floatTransactions->sum('amount')) }}</td></tr>
        </tfoot>
    </table>
    @endif
</div>

@if($reconciliation)
<div class="section">
    <div class="section-title">Reconciliation — {{ $dayStr }} ({{ $reconciliation->code }}, {{ ucfirst($reconciliation->status) }})</div>
    <table>
        <thead><tr><th>Channel</th><th style="text-align:right;">Opening</th><th style="text-align:right;">Deposits</th><th style="text-align:right;">Withdrawals</th><th style="text-align:right;">Expected</th><th style="text-align:right;">Counted</th><th style="text-align:right;">Variance</th></tr></thead>
        <tbody>
            <tr>
                <td>Cash in Till</td>
                <td style="text-align:right;">{{ money($reconciliation->opening_cash) }}</td>
                <td style="text-align:right;">{{ money($reconciliation->cash_deposits) }}</td>
                <td style="text-align:right;">{{ money($reconciliation->cash_withdrawals) }}</td>
                <td style="text-align:right;">{{ money($reconciliation->expected_cash) }}</td>
                <td style="text-align:right;">{{ money($reconciliation->counted_cash) }}</td>
                <td style="text-align:right;">{{ money($reconciliation->cash_variance) }}</td>
            </tr>
            @foreach($reconciliation->network_balances ?? [] as $row)
            <tr>
                <td>{{ $row['network'] ?? 'Network' }} Float</td>
                <td style="text-align:right;">{{ money($row['opening'] ?? 0) }}</td>
                <td style="text-align:right;">{{ money($row['deposits'] ?? 0) }}</td>
                <td style="text-align:right;">{{ money($row['withdrawals'] ?? 0) }}</td>
                <td style="text-align:right;">{{ money($row['expected'] ?? $row['system'] ?? 0) }}</td>
                <td style="text-align:right;">{{ money($row['counted'] ?? 0) }}</td>
                <td style="text-align:right;">{{ money(($row['counted'] ?? 0) - ($row['expected'] ?? $row['system'] ?? 0)) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td style="text-align:right;">{{ money($reconciliation->opening_cash + $reconciliation->opening_float) }}</td>
                <td colspan="2" style="text-align:center;">—</td>
                <td style="text-align:right;">{{ money($reconciliation->expected_cash + $reconciliation->total_float) }}</td>
                <td style="text-align:right;">{{ money($reconciliation->counted_cash + collect($reconciliation->network_balances ?? [])->sum('counted')) }}</td>
                <td style="text-align:right;">{{ money($reconciliation->cash_variance + $reconciliation->float_variance) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

<div class="footer">
    <span>{{ $business['name'] }} — {{ $business['address'] }}</span>
    <span>Daily Report {{ $dayStr }} • {{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
