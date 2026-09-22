@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <style>
        .seg{display:flex;gap:6px;background:var(--sand-100);border:1px solid var(--line);border-radius:20px;padding:3px;}
        .seg-btn{border:none;background:transparent;font-size:12px;font-weight:700;color:var(--ink-soft);padding:5px 13px;border-radius:16px;cursor:pointer;}
        .seg-btn.is-active{background:var(--white);color:var(--terracotta-600);box-shadow:var(--shadow-sm);}
        .chart-box{position:relative;height:250px;}
    </style>

    <div class="view-head">
        <div>
            <h2>Dashboard</h2>
            <p class="sub">{{ now()->format('l, j F Y') }} · Live overview of your cash point.</p>
        </div>
        <div class="view-actions">
            <a class="btn btn-ghost" href="{{ route('transactions.index') }}">View all transactions</a>
            <a href="{{ route('transactions.create') }}" class="btn btn-primary">+ New transaction</a>
        </div>
    </div>

    @if ($cashPoint === null || empty($cashPoint->code) || empty($cashPoint->name) || empty($cashPoint->phone))
        <div style="display:flex;align-items:center;gap:14px;justify-content:space-between;flex-wrap:wrap;background:var(--terracotta-100);color:var(--terracotta-600);border:1px solid var(--terracotta-500);border-radius:14px;padding:14px 18px;margin-bottom:20px;font-weight:600;">
            <div>
                The cash point (wakala) is not set up yet — the system cannot record transactions until it is.
                @if (! is_admin())
                    Ask an administrator to set it up in Settings → Cash Point.
                @endif
            </div>
            @if (is_admin())
                <a class="btn btn-primary" href="{{ route('cash-point.index') }}" style="background:var(--terracotta-600);border-color:var(--terracotta-600);">Set up cash point</a>
            @endif
        </div>
    @endif

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect></svg>
                </div>
                <span class="stat-trend up">Till</span>
            </div>
            <div class="stat-value">@money($cashAvailable)</div>
            <div class="stat-label">Cash at till</div>
        </div>

        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                </div>
                <span class="stat-trend up">{{ $networkBalances->count() }} nets</span>
            </div>
            <div class="stat-value">@money($floatAvailable)</div>
            <div class="stat-label">Mobile-money float available</div>
        </div>

        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19 7-7 3 3-7 7-3-3z"></path><path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path><path d="m2 2 7.586 7.586"></path><circle cx="11" cy="11" r="2"></circle></svg>
                </div>
                <span class="stat-trend up">Today</span>
            </div>
            <div class="stat-value">@money($todayDeposits)</div>
            <div class="stat-label">Total customer deposits</div>
        </div>

        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 12 4l9 7"></path><path d="M5 10v10h14V10"></path><path d="M9 20v-6h6v6"></path></svg>
                </div>
                <span class="stat-trend down">Today</span>
            </div>
            <div class="stat-value">@money($todayWithdrawals)</div>
            <div class="stat-label">Total customer withdrawals</div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Transaction volume trend</h3>
                <div class="seg" id="dashPeriod">
                    <button type="button" class="seg-btn" data-period="7">7 days</button>
                    <button type="button" class="seg-btn is-active" data-period="30">30 days</button>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box"><canvas id="volumeChart"></canvas></div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Volume by network</h3>
                <span class="link">Share</span>
            </div>
            <div class="panel-body">
                @php
                    $segments = collect();
                    $acc = 0;
                    foreach ($networkTotals as $n) {
                        $pct = $networkDonutMax > 0 ? $n->completed_volume / $networkDonutMax * 100 : 0;
                        if ($pct <= 0) { continue; }
                        $from = $acc;
                        $acc += $pct;
                        $segments->push(['from' => $from, 'to' => $acc, 'color' => $n->color ?: '#A98968']);
                    }
                    $grad = $segments->map(fn ($s) => $s['color'].' '.(round($s['from'],2)).'% '.(round($s['to'],2)).'%')->implode(', ');
                @endphp
                <div class="donut-wrap">
                    <div class="donut-chart" style="background:conic-gradient({{ $grad }});">
                        <div class="donut-center" style="background:var(--white);border-radius:50%;inset:14px;position:absolute;">
                            <b>@money($networkTotals->sum('completed_volume'))</b>
                            <span>Total TZS</span>
                        </div>
                    </div>
                    <div class="legend">
                        @foreach ($networkTotals as $n)
                            <div class="legend-item">
                                <span class="legend-dot" style="background:{{ $n->color ?: '#A98968' }};"></span>
                                <span>{{ $n->name }}</span>
                                <b>{{ $networkDonutMax > 0 ? round($n->completed_volume / $networkDonutMax * 100) : 0 }}%</b>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Deposits vs withdrawals</h3>
                <span class="link">TZS</span>
            </div>
            <div class="panel-body">
                <div class="bars" id="bars-7" hidden>
                    @foreach ($series7['deposits'] as $i => $d)
                        <div class="bar-col">
                            <div class="bar-wrap">
                                <div class="bar" style="height:{{ max(3, round($d / $series7['chartMax'] * 100)) }}%;" title="Deposits @money($d)"></div>
                                <div class="bar bar-gold" style="height:{{ max(3, round($series7['withdrawals'][$i] / $series7['chartMax'] * 100)) }}%;" title="Withdrawals @money($series7['withdrawals'][$i])"></div>
                            </div>
                            <div class="bar-label">{{ $series7['labels'][$i] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="bars" id="bars-30">
                    @foreach ($series30['deposits'] as $i => $d)
                        <div class="bar-col">
                            <div class="bar-wrap">
                                <div class="bar" style="height:{{ max(3, round($d / $series30['chartMax'] * 100)) }}%;" title="Deposits @money($d)"></div>
                                <div class="bar bar-gold" style="height:{{ max(3, round($series30['withdrawals'][$i] / $series30['chartMax'] * 100)) }}%;" title="Withdrawals @money($series30['withdrawals'][$i])"></div>
                            </div>
                            <div class="bar-label">{{ $series30['labels'][$i] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="kpi-row">
                    <div class="kpi-item"><b>@money($monthCommission)</b><span>Commission this month</span></div>
                    <div class="kpi-item"><b>@money($monthFees)</b><span>Fees charged this month</span></div>
                    <div class="kpi-item"><b>{{ $pendingCount }}</b><span>Pending transactions</span></div>
                    <div class="kpi-item"><b>{{ $failedCount }}</b><span>Failed transactions</span></div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Commission by network</h3>
                <span class="link">Ranked</span>
            </div>
            <div class="panel-body">
                <div class="chart-box"><canvas id="commissionChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Mobile-money float trend</h3>
                <span class="link">Live</span>
            </div>
            <div class="panel-body">
                <div class="chart-box"><canvas id="floatChart"></canvas></div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Transaction status</h3>
                <span class="link"></span>
            </div>
            <div class="panel-body">
                <div class="chart-box"><canvas id="statusChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Recent transactions</h3>
                <a href="{{ route('transactions.index') }}" class="link">View all</a>
            </div>
            <div class="table-scroll">
                <table style="min-width:560px;">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer / Type</th>
                            <th>Network</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="dashTxnRows">
                        @forelse ($recentTransactions as $txn)
                            <tr data-id="{{ $txn->id }}">
                                <td>
                                    <div class="cell-title">{{ $txn->reference }}</div>
                                    <div class="cell-sub">{{ $txn->created_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    <div class="cell-title">{{ $txn->customer_name ?? '—' }}</div>
                                    <div class="cell-sub">{{ txn_type_label($txn->type) }}</div>
                                </td>
                                <td>
                                    <span class="net-dot" style="background:{{ $txn->network->color ?? '#A98968' }};"></span>
                                    {{ $txn->network->name ?? '—' }}
                                </td>
                                <td class="cell-title">@money($txn->amount)</td>
                                <td><span class="tag {{ status_badge($txn->status) }}">{{ ucfirst($txn->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Network float balances</h3>
                <span class="link">Live</span>
            </div>
            <div class="panel-body">
                <div class="activity-list">
                    @foreach ($networkBalances as $nb)
                        <div class="activity-row">
                            <div class="activity-ico" style="background:{{ $nb['color'] }}22;color:{{ $nb['color'] }};">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                            </div>
                            <div class="activity-text">
                                <b>{{ $nb['name'] }}</b>
                                <div class="activity-time">Available float: <strong style="color:var(--coffee-900);">@money($nb['balance'])</strong></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Recent activity</h3>
            <span class="link"></span>
        </div>
        <div class="panel-body">
            <div class="activity-list">
                @foreach ($recentActivity as $item)
                    <div class="activity-row">
                        <div class="activity-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        </div>
                        <div class="activity-text">
                            <b>{{ $item['text'] }}</b>
                            <div class="activity-time">
                                <span>{{ $item['meta'] }}</span> ·
                                <span>{{ $item['time']->diffForHumans() }}</span>
                                @if (! in_array(strtolower($item['status']), ['completed', 'active'], true))
                                    <span class="tag {{ status_badge($item['status']) }}">{{ ucfirst($item['status']) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        @php
            $dashTxns = $recentTransactions->map(fn ($t) => [
                'id' => $t->id,
                'reference' => $t->reference,
                'type' => $t->type,
                'status' => $t->status,
                'customer_name' => $t->customer_name,
                'customer_phone' => $t->customer_phone,
                'amount' => (float) $t->amount,
                'fee' => (float) $t->fee,
                'commission' => (float) $t->commission,
                'provider_reference' => $t->provider_reference,
                'notes' => $t->notes,
                'reversal_reason' => $t->reversal_reason,
                'network' => $t->network?->name,
                'network_color' => $t->network?->color,
                'created_at' => $t->created_at->format('d M Y H:i'),
                'operator' => $t->operator?->name,
            ])->values();
        @endphp
        const dashTxns = @json($dashTxns);

        const DASH_TYPES = {
            deposit: 'Customer Deposit', withdrawal: 'Customer Withdrawal', send_money: 'Send Money',
            bill_payment: 'Bill Payment', airtime: 'Airtime', data: 'Data Bundle',
            bank_to_wallet: 'Bank to Wallet', wallet_to_bank: 'Wallet to Bank',
        };

        function dashFmt(n) { return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }

        bindRowClick('#dashTxnRows tr[data-id]', tr => {
            const t = dashTxns.find(x => Number(x.id) === Number(tr.dataset.id));
            if (!t) return [];
            return [
                ['Reference', t.reference],
                ['Provider ref', t.provider_reference || '—'],
                ['Date', t.created_at],
                ['Type', DASH_TYPES[t.type] || t.type],
                ['Network', t.network ? { __html: `<span class="net-dot" style="background:${t.network_color || '#999'};"></span> ${t.network}` } : '—'],
                ['Customer', t.customer_name || '—'],
                ['Phone', t.customer_phone],
                ['Amount', dashFmt(t.amount)],
                ['Fee', dashFmt(t.fee)],
                ['Commission', dashFmt(t.commission)],
                ['Status', { __html: statusBadgeHtml(t.status) }],
                ...(t.reversal_reason ? [['Reversal reason', t.reversal_reason]] : []),
                ...(t.notes ? [['Notes', t.notes]] : []),
                ['Operator', t.operator || '{{ auth()->user()->name }}'],
            ];
        }, 'Transaction details');

        @php
            $commData = $commissionByNetwork->map(fn ($n) => [
                'name' => $n->name,
                'value' => (float) $n->completed_commission,
                'color' => $n->color ?: '#A98968',
            ])->values();
        @endphp
        const dashSeries = { 7: @json($series7), 30: @json($series30) };
        const commData = @json($commData);
        const statusKeys = ['completed', 'pending', 'failed', 'reversed'];
        const statusColors = { completed: '#5E6E3F', pending: '#D4A24C', failed: '#B33A3A', reversed: '#7A5C42' };

        const PALETTE = { volume: '#C2592B', volumeFill: 'rgba(194,89,43,.18)', float: '#5E6E3F', floatFill: 'rgba(94,110,63,.20)' };

        let currentPeriod = 30;
        let volumeChart, floatChart, statusChart, commissionChart;

        function renderBars(period) {
            document.getElementById('bars-7').hidden = period !== 7;
            document.getElementById('bars-30').hidden = period !== 30;
        }

        function updateCharts() {
            const s = dashSeries[currentPeriod];

            volumeChart.data.labels = s.labels;
            volumeChart.data.datasets[0].data = s.volume;
            volumeChart.data.datasets[0].pointRadius = currentPeriod === 7 ? 3 : 0;

            floatChart.data.labels = s.labels;
            floatChart.data.datasets[0].data = s.float;
            floatChart.data.datasets[0].pointRadius = currentPeriod === 7 ? 3 : 0;

            statusChart.data.labels = s.labels;
            statusKeys.forEach((k, i) => { statusChart.data.datasets[i].data = s.statuses[k]; });

            volumeChart.update();
            floatChart.update();
            statusChart.update();
            renderBars(currentPeriod);
        }

        function bindPeriodToggle() {
            document.querySelectorAll('#dashPeriod .seg-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentPeriod = parseInt(btn.dataset.period, 10);
                    document.querySelectorAll('#dashPeriod .seg-btn').forEach(b => b.classList.toggle('is-active', b === btn));
                    updateCharts();
                });
            });
        }

        (function initDashCharts() {
            if (typeof Chart === 'undefined') { return; }

            const baseOpts = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#6B5A48', font: { size: 12 } } } },
                scales: { x: { ticks: { color: '#7A5C42', font: { size: 10 } }, grid: { color: '#F0E7D6' } }, y: { ticks: { color: '#7A5C42', font: { size: 10 } }, grid: { color: '#F0E7D6' } } },
            };

            const s = dashSeries[currentPeriod];

            volumeChart = new Chart(document.getElementById('volumeChart'), {
                type: 'line',
                data: {
                    labels: s.labels,
                    datasets: [{
                        label: 'Transaction volume (TZS)',
                        data: s.volume,
                        borderColor: PALETTE.volume,
                        backgroundColor: PALETTE.volumeFill,
                        fill: true,
                        tension: .4,
                        pointRadius: 0,
                        borderWidth: 2,
                    }],
                },
                options: { ...baseOpts, scales: { ...baseOpts.scales, y: { ...baseOpts.scales.y, beginAtZero: true } } },
            });

            floatChart = new Chart(document.getElementById('floatChart'), {
                type: 'line',
                data: {
                    labels: s.labels,
                    datasets: [{
                        label: 'Available float (TZS)',
                        data: s.float,
                        borderColor: PALETTE.float,
                        backgroundColor: PALETTE.floatFill,
                        fill: true,
                        tension: .4,
                        pointRadius: 0,
                        borderWidth: 2,
                    }],
                },
                options: { ...baseOpts, scales: { ...baseOpts.scales, y: { ...baseOpts.scales.y, beginAtZero: true } } },
            });

            statusChart = new Chart(document.getElementById('statusChart'), {
                type: 'bar',
                data: {
                    labels: s.labels,
                    datasets: statusKeys.map(k => ({ label: ucFirst(k), data: s.statuses[k], backgroundColor: statusColors[k] })),
                },
                options: { ...baseOpts, scales: { ...baseOpts.scales, x: { ...baseOpts.scales.x, stacked: true }, y: { ...baseOpts.scales.y, stacked: true, beginAtZero: true } } },
            });

            commissionChart = new Chart(document.getElementById('commissionChart'), {
                type: 'bar',
                data: {
                    labels: commData.map(d => d.name),
                    datasets: [{
                        label: 'Commission (TZS)',
                        data: commData.map(d => d.value),
                        backgroundColor: commData.map(d => d.color),
                        borderRadius: 6,
                    }],
                },
                options: { ...baseOpts, indexAxis: 'y', scales: { ...baseOpts.scales, x: { ...baseOpts.scales.x, beginAtZero: true } } },
            });

            renderBars(currentPeriod);
            bindPeriodToggle();
        })();

        function ucFirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
    </script>
@endsection