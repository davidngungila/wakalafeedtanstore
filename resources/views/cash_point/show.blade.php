@extends('layouts.app')

@section('title', $cashPoint->name ?? 'Cash Point')

@section('content')
    <div class="view-head">
        <div>
            <h2>{{ $cashPoint->name ?? 'Cash Point' }}</h2>
            <p class="sub">{{ $cashPoint->code ?? '' }} · {{ $cashPoint->phone ?? '' }} · {{ ucfirst($cashPoint->status ?? '') }}</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('cash-point.index') }}" class="btn btn-ghost">← Back to cash point</a>
            <a href="{{ route('cash-point.edit', $cashPoint) }}" class="btn btn-primary">Edit cash point</a>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Cash Point Details</h3>
                <span class="tag {{ $cashPoint->status === 'active' ? 'tag-green' : 'tag-grey' }}">{{ ucfirst($cashPoint->status ?? '') }}</span>
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Code</div><div class="dv">{{ $cashPoint->code }}</div></div>
                    <div class="detail-item"><div class="dk">Name</div><div class="dv">{{ $cashPoint->name }}</div></div>
                    <div class="detail-item"><div class="dk">Owner</div><div class="dv">{{ $cashPoint->owner_name ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Phone</div><div class="dv">{{ $cashPoint->phone }}</div></div>
                    <div class="detail-item"><div class="dk">National ID</div><div class="dv">{{ $cashPoint->national_id ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Agent Level</div><div class="dv">{{ ucfirst($cashPoint->agent_level) }}</div></div>
                    <div class="detail-item"><div class="dk">Status</div><div class="dv">{{ ucfirst($cashPoint->status) }}</div></div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Location & Balances</h3>
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Region</div><div class="dv">{{ $cashPoint->region ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">District</div><div class="dv">{{ $cashPoint->district ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Ward</div><div class="dv">{{ $cashPoint->ward ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Street</div><div class="dv">{{ $cashPoint->street ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Cash Balance</div><div class="dv">@money($cashPoint->cash_balance)</div></div>
                    <div class="detail-item"><div class="dk">Total Float</div><div class="dv">@money($cashPoint->totalFloat())</div></div>
                </div>
                <div style="margin-top:14px; display:flex; gap:8px;">
                    <a href="{{ route('cash-point.edit', $cashPoint) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('cash-point.index') }}" class="btn btn-ghost">Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Network Balances</h3>
        </div>
        <div class="panel-body">
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Network</th><th>Balance</th><th>Opening</th></tr></thead>
                    <tbody>
                        @forelse($cashPoint->balances as $bal)
                            <tr><td><span class="net-dot" style="background:{{ $bal->network?->color ?? '#999' }};"></span> {{ $bal->network?->name ?? '—' }}</td><td>@money($bal->balance)</td><td>@money($bal->opening_balance)</td></tr>
                        @empty
                            <tr><td colspan="3" class="empty-state">No balances</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
