@extends('layouts.app')

@section('title', 'Finance')

@section('content')
    <div class="view-head">
        <div>
            <h2>Finance &amp; Settlement</h2>
            <p class="sub">Profit &amp; loss, commission by network and type, and cash-float settlement.</p>
        </div>
        <div class="view-actions">
            <button class="btn btn-ghost" onclick="window.print()">Print</button>
            <a href="{{ route('finance.index', ['tab' => $tab, 'range' => $range, 'export' => 1]) }}" class="btn btn-primary">Export CSV</a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 9-9 5 5 4-4"></path><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></div></div>
            <div class="stat-value">@money($cards['deposits'])</div>
            <div class="stat-label">Deposits (cash in)</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 7-9 9-5-5-4 4"></path><path d="M21 3v4h-4"></path></svg></div></div>
            <div class="stat-value">@money($cards['withdrawals'])</div>
            <div class="stat-label">Withdrawals (cash out)</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div></div>
            <div class="stat-value">@money($cards['commission'])</div>
            <div class="stat-label">Commission earned</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money($cards['net'])</div>
            <div class="stat-label">Net revenue (minus fees)</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--sand-200);--stat-fg:var(--coffee-700);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18v12H3z"></path><path d="M3 10h18"></path></svg></div></div>
            <div class="stat-value">{{ $cards['count'] }}</div>
            <div class="stat-label">Completed transactions</div>
        </div>
    </div>

    <form method="GET" action="{{ route('finance.index') }}">
        <div class="table-card">
            <div class="table-toolbar">
                <div class="chip-filters">
                    <button type="button" class="chip {{ $tab === 'pnl' ? 'active' : '' }}" onclick="goTab('pnl')">P&amp;L</button>
                    <button type="button" class="chip {{ $tab === 'network' ? 'active' : '' }}" onclick="goTab('network')">By network</button>
                    <button type="button" class="chip {{ $tab === 'type' ? 'active' : '' }}" onclick="goTab('type')">By type</button>
                    <button type="button" class="chip {{ $tab === 'settlement' ? 'active' : '' }}" onclick="goTab('settlement')">Settlement</button>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <select name="range" onchange="window.location=buildUrl(this.value)" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                        <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="7d" {{ $range === '7d' ? 'selected' : '' }}>Last 7 days</option>
                        <option value="month" {{ $range === 'month' ? 'selected' : '' }}>This month</option>
                        <option value="all" {{ $range === 'all' ? 'selected' : '' }}>All time</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="table-card" style="margin-top:-24px;">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        @if ($tab === 'network')
                            <th>Network</th>
                            <th>Count</th>
                            <th>Volume</th>
                            <th>Commission</th>
                        @elseif ($tab === 'type')
                            <th>Type</th>
                            <th>Count</th>
                            <th>Volume</th>
                            <th>Commission</th>
                        @elseif ($tab === 'settlement')
                            <th>Network</th>
                            <th>Float balance</th>
                            <th colspan="2">Cash &amp; float position</th>
                        @else
                            <th>Date</th>
                            <th>Count</th>
                            <th>Volume</th>
                            <th>Commission</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if ($tab === 'network')
                        @forelse ($byNetwork as $row)
                            <tr>
                                <td class="cell-title">{{ $row['name'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td class="cell-title">@money($row['volume'])</td>
                                <td>@money($row['commission'])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state">No network data in this period</td></tr>
                        @endforelse
                    @elseif ($tab === 'type')
                        @forelse ($byType as $row)
                            <tr>
                                <td class="cell-title">{{ $row['label'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td class="cell-title">@money($row['volume'])</td>
                                <td>@money($row['commission'])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state">No transaction types in this period</td></tr>
                        @endforelse
                    @elseif ($tab === 'settlement')
                        @forelse ($settlement['perNetwork'] as $float)
                            <tr>
                                <td class="cell-title">{{ $float['name'] }}</td>
                                <td>@money($float['balance'])</td>
                                <td colspan="2" class="cell-sub">{{ $loop->first ? 'Highest float' : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state">No network floats recorded</td></tr>
                        @endforelse
                        <tr class="receipt-row">
                            <td class="cell-title" colspan="4" style="padding:12px 14px;border-top:1px solid var(--line);">
                                <div class="detail-grid" style="margin-top:4px;">
                                    <div class="detail-item"><div class="dk">Cash on hand</div><div class="dv">@money($settlement['cash'])</div></div>
                                    <div class="detail-item"><div class="dk">Float in networks</div><div class="dv">@money($settlement['float'])</div></div>
                                    <div class="detail-item"><div class="dk">Net float movement</div><div class="dv">@money($settlement['netFloatIn'])</div></div>
                                    <div class="detail-item"><div class="dk">Total liquidity</div><div class="dv">@money($settlement['total'])</div></div>
                                </div>
                            </td>
                        </tr>
                    @else
                        @forelse ($breakdown as $row)
                            <tr>
                                <td class="cell-title">{{ $row['label'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td class="cell-title">@money($row['amount'])</td>
                                <td>@money($row['commission'])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state">No transactions in this period</td></tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function buildUrl(range) {
            const url = new URL(window.location.href);
            url.searchParams.set('range', range);
            url.searchParams.delete('export');
            return url.toString();
        }
        function goTab(tab) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            url.searchParams.delete('export');
            window.location = url.toString();
        }
    </script>
@endsection