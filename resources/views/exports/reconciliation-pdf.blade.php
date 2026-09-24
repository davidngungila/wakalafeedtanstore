<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reconciliation {{ $reconciliation->code }} — {{ $reconciliation->reconciliation_date->format('Y-m-d') }}</title>
<style>
    @page { margin: 14mm 10mm 16mm 10mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1a1a1a; line-height: 1.45; margin: 0; padding: 0; }
    .header { border-bottom: 3px solid #C2592B; padding-bottom: 10px; margin-bottom: 12px; }
    .header-top { display: table; width: 100%; }
    .header-left { display: table-cell; vertical-align: top; width: 70%; }
    .header-right { display: table-cell; vertical-align: top; width: 30%; text-align: right; }
    .business-name { font-size: 15px; font-weight: 700; color: #C2592B; margin: 0; }
    .business-meta { font-size: 7.5px; color: #7A5C42; margin: 2px 0 0; }
    .doc-badge { background: #5E6E3F; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; display: inline-block; }
    .title { font-size: 14px; font-weight: 700; color: #1a1a1a; margin: 0 0 2px; }
    .subtitle { font-size: 8.5px; color: #6B5A48; margin: 0 0 8px; }
    .meta-grid { display: table; width: 100%; margin-bottom: 10px; }
    .meta-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 8px; }
    .meta-box { background: #F4ECDC; border: 1px solid #E5DDD0; border-radius: 4px; padding: 7px 9px; margin-bottom: 8px; }
    .meta-label { color: #7A5C42; font-weight: 700; text-transform: uppercase; font-size: 6.5px; letter-spacing: 0.04em; }
    .meta-value { color: #1a1a1a; font-size: 8.5px; }
    .section { margin-top: 12px; }
    .section-title { font-size: 10px; font-weight: 700; color: #5E6E3F; margin: 0 0 4px; border-bottom: 1px solid #E5DDD0; padding-bottom: 4px; }
    .section-sub { font-size: 7px; color: #7A5C42; margin: 0 0 6px; }
    table { width: 100%; border-collapse: collapse; font-size: 7.5px; }
    thead th { background: #5E6E3F; color: #fff; font-weight: 700; text-transform: uppercase; font-size: 6.5px; padding: 6px 4px; border: 1px solid #4a5532; text-align: left; }
    thead th.amount, tbody td.amount, tfoot td.amount { text-align: right; font-family: DejaVu Sans Mono, monospace; }
    tbody td { padding: 4px 4px; border: 1px solid #E5DDD0; vertical-align: top; }
    tbody tr:nth-child(even) td { background: #F9F5EB; }
    tfoot td { background: #5E6E3F; color: #fff; font-weight: 700; padding: 6px 4px; border: 1px solid #4a5532; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 6.5px; font-weight: 700; text-transform: uppercase; }
    .badge-green { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
    .badge-gold { background: #FFF8E1; color: #8a6418; border: 1px solid #FFECB3; }
    .badge-red { background: #FFEBEE; color: #B33A3A; border: 1px solid #FFCDD2; }
    .badge-terracotta { background: #FBE9E7; color: #C2592B; border: 1px solid #FFCCBC; }
    .balance-grid { display: table; width: 100%; margin-bottom: 8px; }
    .balance-row { display: table-row; }
    .balance-cell { display: table-cell; width: 14.28%; padding: 4px; vertical-align: top; }
    .bb { background: #fff; border: 1px solid #E5DDD0; border-radius: 4px; padding: 6px; text-align: center; }
    .bb-label { font-size: 6.5px; color: #7A5C42; text-transform: uppercase; font-weight: 700; margin-bottom: 2px; }
    .bb-amount { font-size: 8.5px; font-weight: 700; color: #1a1a1a; }
    .bb-sub { font-size: 6px; color: #7A5C42; margin-top: 2px; }
    .footer { position: fixed; bottom: -10mm; left: 0; right: 0; text-align: center; font-size: 6.5px; color: #7A5C42; border-top: 1px solid #E5DDD0; padding-top: 5px; }
    .pagenum:before { content: counter(page); }
    .empty { text-align: center; padding: 14px; color: #7A5C42; font-style: italic; border: 1px dashed #E5DDD0; border-radius: 4px; background: #FFFBF5; font-size: 8px; }
    .correction { border: 1px solid #E5DDD0; border-radius: 4px; padding: 6px 8px; margin-bottom: 6px; background: #FFFBF5; }
    .correction-title { font-weight: 700; font-size: 8px; }
    h3 { margin: 0; }
    .page-break { page-break-after: always; }
</style>
</head>
<body>

<div class="header">
    <div class="header-top">
        <div class="header-left">
            <div class="business-name">{{ $business['name'] }}</div>
            <div class="business-meta">{{ $business['address'] }} &nbsp;|&nbsp; {{ $business['phone'] }} &nbsp;|&nbsp; {{ $business['email'] }}</div>
        </div>
        <div class="header-right">
            <div class="doc-badge">Reconciliation</div>
            <div style="font-size:6.5px;color:#7A5C42;margin-top:4px;">Generated: {{ $generatedAt }}</div>
            <div style="font-size:6.5px;color:#7A5C42;">By: {{ $generatedBy }}</div>
        </div>
    </div>
</div>

<div>
    <div class="title">Reconciliation {{ $reconciliation->code }} — {{ $reconciliation->reconciliation_date->format('l, d M Y') }}</div>
    <div class="subtitle">Agent {{ $reconciliation->agent?->code ?? '—' }} · {{ $reconciliation->agent?->name ?? '' }} | Status <span class="badge {{ $reconciliation->status === 'reconciled' ? 'badge-green' : ($reconciliation->status === 'variance' ? 'badge-red' : 'badge-gold') }}">{{ ucfirst($reconciliation->status) }}</span> | Reconciled by {{ $reconciliation->reconciler?->name ?? '—' }} on {{ $reconciliation->created_at->format('d M Y H:i') }}</div>
</div>

<div class="meta-box">
    <div style="display:table;width:100%;font-size:7.5px;">
        <div style="display:table-cell;width:25%;"><span class="meta-label">Date</span><br><span class="meta-value">{{ $reconciliation->reconciliation_date->format('Y-m-d') }}</span></div>
        <div style="display:table-cell;width:25%;"><span class="meta-label">Agent</span><br><span class="meta-value">{{ $reconciliation->agent?->code }} — {{ $reconciliation->agent?->name }}</span></div>
        <div style="display:table-cell;width:25%;"><span class="meta-label">Opening Cash</span><br><span class="meta-value">{{ money($run['openingCash']) }}</span></div>
        <div style="display:table-cell;width:25%;"><span class="meta-label">Opening Float</span><br><span class="meta-value">{{ money($run['openingFloat']) }}</span></div>
    </div>
    <div style="display:table;width:100%;font-size:7.5px;margin-top:6px;">
        <div style="display:table-cell;width:25%;"><span class="meta-label">Expected Cash</span><br><span class="meta-value">{{ money($run['expectedCash']) }}</span></div>
        <div style="display:table-cell;width:25%;"><span class="meta-label">Counted Cash</span><br><span class="meta-value">{{ money($run['countedCash']) }}</span></div>
        <div style="display:table-cell;width:25%;"><span class="meta-label">Cash Variance</span><br><span class="meta-value" style="color:{{ abs($run['cashVariance']) < 0.005 ? '#2E7D32' : '#B33A3A' }}">{{ $run['cashVariance'] > 0 ? '+' : '' }}{{ money($run['cashVariance']) }}</span></div>
        <div style="display:table-cell;width:25%;"><span class="meta-label">Float Variance</span><br><span class="meta-value" style="color:{{ abs($run['countedFloat'] - $run['expectedFloat']) < 0.005 ? '#2E7D32' : '#B33A3A' }}">{{ ($run['countedFloat'] - $run['expectedFloat']) > 0 ? '+' : '' }}{{ money($run['countedFloat'] - $run['expectedFloat']) }}</span></div>
    </div>
    @if($reconciliation->notes)<div style="margin-top:6px;font-size:7.5px;"><span class="meta-label">Notes</span><br><span class="meta-value">{{ $reconciliation->notes }}</span></div>@endif
</div>

@php
    $totalOpening = $run['openingCash'] + $run['openingFloat'];
    $totalDeposits = $run['cashDeposits'] + collect($run['networks'])->sum('deposits');
    $totalWithdrawals = $run['cashWithdrawals'] + collect($run['networks'])->sum('withdrawals');
    $totalTopups = collect($run['networks'])->sum(fn($r) => $r['float_topups'] ?? 0);
    $totalExpected = $run['expectedCash'] + $run['expectedFloat'];
    $totalCounted = $run['countedCash'] + $run['countedFloat'];
    $totalVariance = $run['cashVariance'] + ($run['countedFloat'] - $run['expectedFloat']);
    $openingPlusTopups = $totalOpening + $totalTopups;
@endphp

<div class="section">
    <div class="section-title">Reconciliation Run — Opening → Activity → Expected Closing</div>
    <div class="section-sub">Cash: Opening + deposits − withdrawals | Float: Opening − deposits + withdrawals + top-ups (bank float, already in Expected)</div>
    <table>
        <thead>
            <tr>
                <th style="width:22%;">Channel</th>
                <th class="amount">Opening</th>
                <th class="amount">Deposits</th>
                <th class="amount">Withdrawals</th>
                <th class="amount">+ Top-ups</th>
                <th class="amount">= Expected</th>
                <th class="amount">Counted</th>
                <th class="amount">Variance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Cash in Till</strong><br><span style="color:#7A5C42;font-size:6.5px;">Opening + deposits − withdrawals</span></td>
                <td class="amount">{{ money($run['openingCash']) }}</td>
                <td class="amount">+{{ money($run['cashDeposits']) }}</td>
                <td class="amount">−{{ money($run['cashWithdrawals']) }}</td>
                <td class="amount" style="color:#7A5C42;">—</td>
                <td class="amount"><strong>{{ money($run['expectedCash']) }}</strong></td>
                <td class="amount">{{ money($run['countedCash']) }}</td>
                <td class="amount"><span class="badge {{ abs($run['cashVariance']) < 0.005 ? 'badge-green' : ($run['cashVariance'] > 0 ? 'badge-gold' : 'badge-red') }}">{{ $run['cashVariance'] > 0 ? '+' : '' }}{{ money($run['cashVariance']) }}</span></td>
            </tr>
            @foreach($run['networks'] as $row)
            <tr>
                <td><strong>{{ $row['network'] }} Float</strong><br><span style="color:#7A5C42;font-size:6.5px;">Opening − deposits + withdrawals + top-ups</span></td>
                <td class="amount">{{ money($row['opening']) }}</td>
                <td class="amount">−{{ money($row['deposits']) }}</td>
                <td class="amount">+{{ money($row['withdrawals']) }}</td>
                <td class="amount" style="color:#5E6E3F;">+{{ money($row['float_topups'] ?? 0) }}</td>
                <td class="amount"><strong>{{ money($row['expected']) }}</strong></td>
                <td class="amount">{{ money($row['counted']) }}</td>
                <td class="amount"><span class="badge {{ abs($row['variance']) < 0.005 ? 'badge-green' : ($row['variance'] > 0 ? 'badge-gold' : 'badge-red') }}">{{ $row['variance'] > 0 ? '+' : '' }}{{ money($row['variance']) }}</span></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="amount">{{ money($totalOpening) }}</td>
                <td class="amount">+{{ money($totalDeposits) }}</td>
                <td class="amount">−{{ money($totalWithdrawals) }}</td>
                <td class="amount">+{{ money($totalTopups) }}</td>
                <td class="amount">{{ money($totalExpected) }}</td>
                <td class="amount">{{ money($totalCounted) }}</td>
                <td class="amount">{{ $totalVariance > 0 ? '+' : '' }}{{ money($totalVariance) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="section">
    <div class="section-title">Tie-out Check — Opening + Top-ups vs Counted (Top-ups Included on Both Sides)</div>
    <div style="display:table;width:100%;margin-top:4px;">
        <div style="display:table-cell;width:14%;padding:2px;"><div class="bb"><div class="bb-label">Opening Cash</div><div class="bb-amount">{{ money($run['openingCash']) }}</div></div></div>
        <div style="display:table-cell;width:14%;padding:2px;"><div class="bb"><div class="bb-label">+ Opening Float</div><div class="bb-amount">+{{ money($run['openingFloat']) }}</div></div></div>
        <div style="display:table-cell;width:14%;padding:2px;"><div class="bb"><div class="bb-label">= Opening Total</div><div class="bb-amount">{{ money($totalOpening) }}</div></div></div>
        <div style="display:table-cell;width:14%;padding:2px;"><div class="bb" style="background:#F4ECDC;"><div class="bb-label">+ Top-ups (bank)</div><div class="bb-amount">+{{ money($totalTopups) }}</div></div></div>
        <div style="display:table-cell;width:14%;padding:2px;"><div class="bb" style="background:#FFF8E1;"><div class="bb-label">= Opening+Top-ups</div><div class="bb-amount">{{ money($openingPlusTopups) }}</div><div class="bb-sub">≈ Expected {{ money($totalExpected) }}</div></div></div>
        <div style="display:table-cell;width:14%;padding:2px;"><div class="bb"><div class="bb-label">Counted Total</div><div class="bb-amount">{{ money($totalCounted) }}</div><div class="bb-sub">Cash {{ money($run['countedCash']) }} + Float {{ money($run['countedFloat']) }}</div></div></div>
        <div style="display:table-cell;width:16%;padding:2px;"><div class="bb" style="background:{{ abs($run['tieOut']) < 0.005 ? '#E8F5E9' : '#FFF8E1' }};"><div class="bb-label">Tie-out (must be 0)</div><div class="bb-amount" style="color:{{ abs($run['tieOut']) < 0.005 ? '#2E7D32' : '#B33A3A' }};">@if(abs($run['tieOut']) < 0.005) {{ money(0) }} ✓ @else {{ $run['tieOut'] > 0 ? '+' : '' }}{{ money($run['tieOut']) }} @endif</div><div class="bb-sub">Expected − Counted</div></div></div>
    </div>
</div>

<div class="section">
    <div class="section-title">Channels Breakdown — Settlement per Channel</div>
    <table>
        <thead>
            <tr>
                <th style="width:22%;">Channel</th>
                <th class="amount">System (Expected)</th>
                <th class="amount">Counted</th>
                <th class="amount">Variance</th>
                <th class="amount">Settled by Corrections</th>
                <th class="amount">Remaining</th>
                <th style="text-align:center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($channels as $ch)
            <tr>
                <td>{{ $ch['label'] }}</td>
                <td class="amount">{{ money($ch['system']) }}</td>
                <td class="amount">{{ money($ch['counted']) }}</td>
                <td class="amount"><span class="badge {{ abs($ch['variance']) < 0.005 ? 'badge-green' : ($ch['variance'] > 0 ? 'badge-gold' : 'badge-red') }}">{{ $ch['variance'] > 0 ? '+' : '' }}{{ money($ch['variance']) }}</span></td>
                <td class="amount">@if(abs($ch['settled']) < 0.005) — @else {{ $ch['settled'] > 0 ? '+' : '' }}{{ money($ch['settled']) }} @endif</td>
                <td class="amount"><span class="badge {{ abs($ch['remaining']) < 0.005 ? 'badge-green' : 'badge-gold' }}">{{ abs($ch['remaining']) < 0.005 ? '0' : (($ch['remaining'] > 0 ? '+' : '').money($ch['remaining'])) }}</span></td>
                <td style="text-align:center;"><span class="badge {{ abs($ch['remaining']) < 0.005 ? 'badge-green' : 'badge-gold' }}">{{ abs($ch['remaining']) < 0.005 ? 'Settled' : 'Open' }}</span></td>
            </tr>
            @empty
            <tr><td colspan="7"><div class="empty">No channel data</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">Opening Details</div>
    @if($dayOpening)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="amount">Cash Opening</th>
                    <th class="amount">Float Opening (Total)</th>
                    <th>By</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $dayOpening->opening_date->format('Y-m-d') }} ({{ $dayOpening->opening_date->format('l') }})</td>
                    <td><span class="badge {{ $dayOpening->is_closed ? 'badge-grey' : 'badge-green' }}">{{ $dayOpening->is_closed ? 'Closed' : 'Open' }}</span></td>
                    <td class="amount">{{ money($dayOpening->cash_opening) }}</td>
                    <td class="amount">{{ money($dayOpening->totalFloatOpening()) }}</td>
                    <td>{{ $dayOpening->user?->name ?? '—' }}</td>
                    <td>{{ $dayOpening->notes ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
        <div style="margin-top:4px;font-size:7px;color:#7A5C42;">
            @foreach($run['networks'] as $row)
                <span style="display:inline-block;border:1px solid #E5DDD0;border-radius:3px;padding:1px 5px;margin:2px 4px 0 0;background:#fff;">{{ $row['network'] ?? $row['name'] }}: {{ money($row['opening']) }}</span>
            @endforeach
        </div>
    @else
        <div class="empty">No Daily Opening for {{ $reconciliation->reconciliation_date->format('Y-m-d') }} — fallback to live balances.</div>
    @endif
</div>

@if($dayTransactions->isNotEmpty() || $dayFloatTransactions->isNotEmpty())
<div class="section">
    <div class="section-title">Activity for {{ $reconciliation->reconciliation_date->format('Y-m-d') }} — {{ $dayTransactions->count() }} transaction(s), {{ $dayFloatTransactions->count() }} float movement(s)</div>
    @if($dayTransactions->isNotEmpty())
    <div style="margin-bottom:6px;"><strong style="font-size:7.5px;">Customer Transactions (deposits/withdrawals — float top-ups excluded)</strong></div>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Reference</th>
                <th>Type</th>
                <th>Network</th>
                <th class="amount">Amount</th>
                <th>Customer</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dayTransactions as $t)
            <tr>
                <td>{{ $t->created_at->format('H:i:s') }}</td>
                <td>{{ $t->reference }}<br><span style="color:#7A5C42;font-size:6px;">{{ $t->provider_reference ?? '' }}</span></td>
                <td>{{ txn_type_label($t->type) }}</td>
                <td>{{ $t->network?->name ?? '—' }}</td>
                <td class="amount">{{ money($t->amount) }}</td>
                <td>{{ $t->customer_name ?? '—' }}<br><span style="color:#7A5C42;font-size:6px;">{{ $t->customer_phone ?? '' }}</span></td>
                <td><span class="badge badge-green">{{ $t->status }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:4px;font-size:7px;color:#6B5A48;">Vol {{ money($dayTransactions->sum('amount')) }} · Comm {{ money($dayTransactions->sum('commission')) }} · Fee {{ money($dayTransactions->sum('fee')) }}</div>
    @endif

    @if($dayFloatTransactions->isNotEmpty())
    <div style="margin-top:8px;">
        <strong style="font-size:7.5px;">Float Movements (bank top-ups included)</strong>
        <div style="margin-top:4px;">
            @foreach($dayFloatTransactions as $ft)
                <span style="display:inline-block;border:1px solid #E5DDD0;border-radius:3px;padding:2px 6px;margin:2px 4px 2px 0;font-size:7px;background:{{ in_array($ft->type, ['float_topup','cash_in']) ? '#E8F5E9' : '#FFF8E1' }};">{{ $ft->network?->name }}: {{ str_replace('_',' ',$ft->type) }} {{ money($ft->amount) }} at {{ $ft->created_at->format('H:i') }}</span>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

@if($reconciliation->corrections->isNotEmpty())
<div class="section">
    <div class="section-title">Corrections — {{ $reconciliation->corrections->count() }} recorded</div>
    <table>
        <thead>
            <tr>
                <th>Channel</th>
                <th>Type</th>
                <th class="amount">Amount</th>
                <th>Reference</th>
                <th>Notes</th>
                <th>By / At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reconciliation->corrections as $c)
            <tr>
                <td>{{ $c->scope === 'cash' ? 'Cash in Till' : $c->network?->name }}</td>
                <td>{{ $c->typeLabel() }}</td>
                <td class="amount">{{ $c->signedAmount() > 0 ? '+' : '' }}{{ money($c->amount) }}</td>
                <td>{{ $c->reference }}</td>
                <td>{{ $c->notes ?? '—' }}</td>
                <td>{{ $c->creator?->name ?? '—' }}<br><span style="color:#7A5C42;font-size:6px;">{{ $c->created_at->format('d M Y H:i') }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    <span>{{ $business['name'] }} — {{ $business['address'] }}</span>
    <span>Page <span class="pagenum"></span></span>
    <span>Reconciliation {{ $reconciliation->code }} • {{ $generatedAt }} • {{ $generatedBy }}</span>
</div>

</body>
</html>
