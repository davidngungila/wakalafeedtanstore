@extends('layouts.app')

@section('title', 'Income Statement')

@section('content')
    <div class="view-head">
        <div>
            <h2>Income Statement</h2>
            <p class="sub">Revenue less expenses from posted journal entries for this period.</p>
        </div>
        <div class="view-actions">
            <button class="btn btn-ghost" onclick="window.print()">Print</button>
        </div>
    </div>

    @include('finance._nav')

    <form method="GET" action="{{ route('finance.statements.income') }}">
        <div class="table-toolbar" style="background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);margin-bottom:24px;">
            <select name="range" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);" onchange="this.form.submit()">
                <option value="today" {{ request('range') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="7d" {{ request('range') === '7d' ? 'selected' : '' }}>Last 7 days</option>
                <option value="month" {{ request('range') === 'month' || ! request('range') ? 'selected' : '' }}>This month</option>
                <option value="year" {{ request('range') === 'year' ? 'selected' : '' }}>This year</option>
            </select>
            <span style="margin-left:auto;font-weight:600;color:var(--ink-soft);">{{ $statement['from']->format('d M Y') }} — {{ $statement['to']->format('d M Y') }}</span>
        </div>
    </form>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div></div>
            <div class="stat-value">@money($statement['revenueTotal'])</div>
            <div class="stat-label">Total revenue</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 7-9 9-5-5-4 4"></path><path d="M21 3v4h-4"></path></svg></div></div>
            <div class="stat-value">@money($statement['expenseTotal'])</div>
            <div class="stat-label">Total expenses</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money(max($statement['netIncome'], 0))</div>
            <div class="stat-label">Net income ({{ $statement['netIncome'] >= 0 ? 'profit' : 'loss' }})</div>
        </div>
    </div>

    <div class="settings-layout" style="align-items:start;">
        <div style="grid-column:1 / -1;">
            <div class="table-card">
                <div class="table-toolbar"><span style="font-weight:700;color:var(--coffee-900);">Revenue</span></div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Code</th><th>Account</th><th style="text-align:right;">Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($statement['revenue'] as $row)
                                <tr>
                                    <td class="cell-title">{{ $row['code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="cell-title" style="text-align:right;">@money($row['amount'])</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty-state">No revenue posted in this period.</td></tr>
                            @endforelse
                            <tr>
                                <td colspan="2" class="cell-sub"><strong>Total revenue</strong></td>
                                <td class="cell-title" style="text-align:right;">@money($statement['revenueTotal'])</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-card">
                <div class="table-toolbar"><span style="font-weight:700;color:var(--coffee-900);">Expenses</span></div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Code</th><th>Account</th><th style="text-align:right;">Amount</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($statement['expenses'] as $row)
                                <tr>
                                    <td class="cell-title">{{ $row['code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="cell-title" style="text-align:right;">@money($row['amount'])</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty-state">No expenses posted in this period.</td></tr>
                            @endforelse
                            <tr>
                                <td colspan="2" class="cell-sub"><strong>Total expenses</strong></td>
                                <td class="cell-title" style="text-align:right;">@money($statement['expenseTotal'])</td>
                            </tr>
                            <tr class="receipt-row">
                                <td colspan="2" class="cell-title">Net income</td>
                                <td class="cell-title" style="text-align:right;">@money($statement['netIncome'])</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection