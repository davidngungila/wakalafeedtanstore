@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="view-head">
        <div>
            <h2>Reports &amp; Analytics</h2>
            <p class="sub">Volume, commissions and performance across every day and network.</p>
        </div>
        <div class="view-actions">
            <button class="btn btn-ghost" onclick="window.print()">Print report</button>
            <button class="btn btn-primary" onclick="toast('Report generated in demo mode','success')">Export PDF</button>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M2 12h20"></path><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg></div></div>
            <div class="stat-value">@money($cards['totalVolume'])</div>
            <div class="stat-label">Total completed volume</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path><path d="m15 5 4 4"></path></svg></div></div>
            <div class="stat-value">@money($cards['totalCommission'])</div>
            <div class="stat-label">Total commission earned</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money($cards['netRevenue'])</div>
            <div class="stat-label">Net revenue (fees deducted)</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg></div><span class="stat-trend down">{{ $cards['failed'] }} + {{ $cards['reversed'] }} bad</span></div>
            <div class="stat-value">{{ $cards['completed'] }}</div>
            <div class="stat-label">Completed transactions</div>
        </div>
    </div>

    <div class="balance-strip">
        <div class="balance-box" style="border-left:4px solid var(--terracotta-600);">
            <div class="bb-label">Cash in (volume in)</div>
            <div class="bb-amount">@money($cashFlow['cashIn'])</div>
            <div class="bb-sub">Money moving into the operation</div>
        </div>
        <div class="balance-box" style="border-left:4px solid var(--acacia-600);">
            <div class="bb-label">Cash out (withdrawals)</div>
            <div class="bb-amount">@money($cashFlow['cashOut'])</div>
            <div class="bb-sub">Money paid out to customers</div>
        </div>
        <div class="balance-box" style="border-left:4px solid var(--gold-500);">
            <div class="bb-label">Net float movement</div>
            <div class="bb-amount">@money($cashFlow['netFloatIn'])</div>
            <div class="bb-sub">Estimated float flow this period</div>
        </div>
        <div class="balance-box" style="border-left:4px solid var(--coffee-500);">
            <div class="bb-label">Commission payout</div>
            <div class="bb-amount">@money($cards['totalCommission'])</div>
            <div class="bb-sub">Total earned by the business</div>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.index') }}">
        <div class="table-card">
            <div class="table-toolbar">
                <div class="chip-filters">
                    <button type="button" class="chip {{ ($report ?? 'daily') === 'daily' ? 'active' : '' }}" onclick="location='/reports?report=daily'">Daily summary</button>
                    <button type="button" class="chip {{ ($report ?? '') === 'networks' ? 'active' : '' }}" onclick="location='/reports?report=networks'">By network</button>
                </div>
                <div class="table-search" style="margin-left:auto;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" placeholder="Search…" oninput="filterReportRows(this.value)">
                </div>
            </div>
        </div>
    </form>

    <div class="table-card" style="margin-top:-24px;">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        @if (($report ?? 'daily') === 'daily')
                            <th>Date</th>
                            <th>Opening Cash</th>
                            <th>Opening Float</th>
                            <th>Deposits</th>
                            <th>Withdrawals</th>
                            <th>Volume</th>
                            <th>Commission</th>
                            <th>Net Revenue</th>
                            <th>Count</th>
                        @else
                            <th>Network</th>
                            <th>Count</th>
                            <th>Volume</th>
                            <th>Commission</th>
                            <th>Share</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="reportBody">
                    @if (($report ?? 'daily') === 'daily')
                        @forelse ($daily as $day)
                            <tr data-search="{{ strtolower($day['date']) }}">
                                <td class="cell-title">{{ $day['date'] }}</td>
                                <td>@money($day['opening_cash'])</td>
                                <td>@money($day['opening_float'])</td>
                                <td>@money($day['deposits'])</td>
                                <td>@money($day['withdrawals'])</td>
                                <td class="cell-title">@money($day['volume'])</td>
                                <td>@money($day['commission'])</td>
                                <td style="color:{{ $day['net_revenue'] >= 0 ? 'var(--acacia-600)' : 'var(--danger)' }};font-weight:700;">@money($day['net_revenue'])</td>
                                <td>{{ $day['count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty-state">No daily data</td></tr>
                        @endforelse
                    @else
                        @forelse ($byNetwork as $network)
                            <tr data-search="{{ strtolower($network['name']) }}">
                                <td>
                                    <span class="net-dot" style="background:{{ $network['color'] }};"></span>
                                    <span class="cell-title">{{ $network['name'] }}</span>
                                </td>
                                <td>{{ $network['count'] }}</td>
                                <td class="cell-title">@money($network['volume'])</td>
                                <td>@money($network['commission'])</td>
                                <td>
                                    @if ($cards['totalVolume'] > 0)
                                        <span class="tag tag-terracotta">{{ number_format($network['volume'] / $cards['totalVolume'] * 100, 1) }}%</span>
                                    @else
                                        <span class="tag tag-grey">0%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state">No network data</td></tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function filterReportRows(q) {
            q = q.toLowerCase();
            document.querySelectorAll('#reportBody tr[data-search]').forEach(tr => {
                tr.style.display = (!q || tr.dataset.search.includes(q)) ? 'table-row' : 'none';
            });
        }
    </script>
@endsection