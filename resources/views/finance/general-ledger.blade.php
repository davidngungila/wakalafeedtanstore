@extends('layouts.app')

@section('title', 'General Ledger')

@section('content')
    <div class="view-head">
        <div>
            <h2>General Ledger</h2>
            <p class="sub">Account-by-account history of posted journal lines with running balances.</p>
        </div>
        <div class="view-actions">
            <button class="btn btn-ghost" onclick="window.print()">Print</button>
        </div>
        @include('exports._export-modal', ['route' => $exportRoute, 'columns' => $exportColumns, 'title' => 'General Ledger'])
    </div>

    @include('finance._nav')

    <form method="GET" action="{{ route('finance.ledger.index') }}">
        <div class="table-toolbar" style="background:var(--white);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);margin-bottom:24px;">
            <select name="account_id" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);" onchange="this.form.submit()">
                @forelse (($accountsForView ?? $accounts) as $option)
                    @php $optId = $option['encrypted'] ?? $option->id; $optRawId = $option['id'] ?? $option->id; @endphp
                    <option value="{{ $optId }}" {{ $optRawId === $selectedAccountId ? 'selected' : '' }}>{{ $option['code'] ?? $option->code }} — {{ $option['name'] ?? $option->name }}</option>
                @empty
                    <option value="">No accounts yet</option>
                @endforelse
            </select>
            <select name="range" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);" onchange="this.form.submit()">
                <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Today</option>
                <option value="7d" {{ $range === '7d' ? 'selected' : '' }}>Last 7 days</option>
                <option value="month" {{ $range === 'month' ? 'selected' : '' }}>This month</option>
                <option value="all" {{ $range === 'all' ? 'selected' : '' }}>All time</option>
            </select>
            @if($account)
                <a href="{{ route('finance.ledger.index', ['account_id' => $account->getRouteKey(), 'range' => $range]) }}" class="btn btn-ghost" style="margin-left:auto;">View single</a>
            @endif
        </div>
    </form>

    @if ($account === null)
        <div class="table-card">
            <div class="empty-state">You need at least one active account to open the ledger.</div>
        </div>
    @else
        <div class="balance-strip">
            <div class="balance-box" style="border-left:4px solid var(--terracotta-600);">
                <div class="bb-label">{{ $account->code }} · Opening balance</div>
                <div class="bb-amount">@money($ledgerReport['opening'])</div>
                <div class="bb-sub">Balance before this period</div>
            </div>
            <div class="balance-box" style="border-left:4px solid var(--acacia-600);">
                <div class="bb-label">{{ $account->code }} · Closing balance</div>
                <div class="bb-amount">@money($ledgerReport['closing'])</div>
                <div class="bb-sub">Balance after this period</div>
            </div>
            <div class="balance-box">
                <div class="bb-label">Type</div>
                <div class="bb-sub" style="font-size:15px;font-weight:600;color:var(--coffee-900);">{{ account_type_label($account->type) }}</div>
            </div>
        </div>

        <div class="table-card" style="margin-top:-8px;">
            <div class="table-toolbar">
                <span style="font-weight:700;color:var(--coffee-900);">{{ $account->code }} — {{ $account->name }}</span>
                @if (count($ledgerReport['rows']))
                    <span class="tag tag-grey" style="margin-left:auto;">{{ count($ledgerReport['rows']) }} lines</span>
                @endif
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="cell-sub" style="border-bottom:1px solid var(--line);"><strong>Opening balance</strong></td>
                            <td class="cell-title">@money($ledgerReport['opening'])</td>
                        </tr>
                        @forelse ($ledgerReport['rows'] as $row)
                            @php $entry = \App\Models\JournalEntry::where('reference', $row['reference'])->first(); @endphp
                            <tr @if($entry) class="row-click" style="cursor:pointer;" onclick="window.location='{{ route('finance.journals.show', $entry) }}'" @endif>
                                <td>{{ $row['date'] }}</td>
                                <td class="cell-title">
                                    @if($entry)
                                        <a href="{{ route('finance.journals.show', $entry) }}" onclick="event.stopPropagation()" style="color:var(--terracotta-600);text-decoration:none;font-weight:700;">{{ $row['reference'] }}</a>
                                    @else
                                        {{ $row['reference'] }}
                                    @endif
                                </td>
                                <td class="cell-sub">{{ Str::limit($row['description'], 60) }}</td>
                                <td>{{ $row['debit'] > 0 ? money($row['debit']) : '—' }}</td>
                                <td>{{ $row['credit'] > 0 ? money($row['credit']) : '—' }}</td>
                                <td class="cell-title">@money($row['balance'])</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-state">No posted lines in this period.</td></tr>
                        @endforelse
                        @if (count($ledgerReport['rows']))
                            <tr class="receipt-row">
                                <td colspan="5" class="cell-sub" style="border-top:1px solid var(--line);"><strong>Closing balance</strong></td>
                                <td class="cell-title" style="border-top:1px solid var(--line);">@money($ledgerReport['closing'])</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection