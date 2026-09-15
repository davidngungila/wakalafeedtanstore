@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')
    <div class="view-head">
        <div>
            <h2>Balance Sheet</h2>
            <p class="sub">Assets equal liabilities plus equity as of the selected date.</p>
        </div>
        <div class="view-actions">
            <span class="tag {{ abs($sheet['assetTotal'] - $sheet['liabilityTotal'] - $sheet['equityTotal'] - $sheet['netIncome']) < 0.01 ? 'tag-green' : 'tag-red' }}">
                {{ abs($sheet['assetTotal'] - $sheet['liabilityTotal'] - $sheet['equityTotal'] - $sheet['netIncome']) < 0.01 ? 'Balanced' : 'Out of balance' }}
            </span>
            <button class="btn btn-ghost" onclick="window.print()">Print</button>
        </div>
    </div>

    @include('finance._nav')

    <form method="GET" action="{{ route('finance.statements.balance') }}">
        <div class="table-toolbar" style="background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);margin-bottom:24px;">
            <label style="font-weight:600;color:var(--coffee-700);">As of</label>
            <input type="date" name="as_of" value="{{ $sheet['asOf']->toDateString() }}" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
            <button class="btn btn-soft btn-sm">Update</button>
        </div>
    </form>

    <div class="settings-layout" style="align-items:start;">
        <div style="grid-column:1 / -1;">
            <div class="table-card">
                <div class="table-toolbar"><span style="font-weight:700;color:var(--coffee-900);">Assets</span></div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Code</th><th>Account</th><th style="text-align:right;">Balance</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($sheet['assets'] as $row)
                                <tr>
                                    <td class="cell-title">{{ $row['code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="cell-title" style="text-align:right;">@money($row['amount'])</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty-state">No assets on the books as of {{ $sheet['asOf']->format('d M Y') }}.</td></tr>
                            @endforelse
                            <tr>
                                <td colspan="2" class="cell-sub"><strong>Total assets</strong></td>
                                <td class="cell-title" style="text-align:right;">@money($sheet['assetTotal'])</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-card">
                <div class="table-toolbar"><span style="font-weight:700;color:var(--coffee-900);">Liabilities &amp; Equity</span></div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Code</th><th>Account</th><th style="text-align:right;">Balance</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($sheet['liabilities'] as $row)
                                <tr>
                                    <td class="cell-title">{{ $row['code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="cell-title" style="text-align:right;">@money($row['amount'])</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="2" class="cell-sub"><strong>Total liabilities</strong></td>
                                <td class="cell-title" style="text-align:right;">@money($sheet['liabilityTotal'])</td>
                            </tr>
                            @foreach ($sheet['equity'] as $row)
                                <tr>
                                    <td class="cell-title">{{ $row['code'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="cell-title" style="text-align:right;">@money($row['amount'])</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="2" class="cell-sub"><strong>Total equity</strong></td>
                                <td class="cell-title" style="text-align:right;">@money($sheet['equityTotal'])</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="cell-title" style="color:var(--acacia-600);">Current period net income</td>
                                <td class="cell-title" style="text-align:right;color:var(--acacia-600);">@money($sheet['netIncome'])</td>
                            </tr>
                            <tr class="receipt-row">
                                <td colspan="2" class="cell-title">Total liabilities + equity</td>
                                <td class="cell-title" style="text-align:right;">@money($sheet['liabilityTotal'] + $sheet['equityTotal'] + $sheet['netIncome'])</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection