@extends('layouts.app')

@section('title', 'Daily Openings History')

@section('content')
    <div class="view-head">
        <div>
            <h2>Daily Openings</h2>
            <p class="sub">History of daily opening and closing records.</p>
        </div>
        @include('exports._export-modal', ['route' => $exportRoute, 'columns' => $exportColumns, 'title' => 'Daily Openings'])
    </div>

    @if ($errors->any())
        <div class="box-alert">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="table-card">
        <div class="table-scroll">
            <table style="min-width:900px;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Opening Cash</th>
                        <th>Closing Cash</th>
                        <th>Opening Float</th>
                        <th>Closing Float</th>
                        <th>Volume</th>
                        <th>Commission</th>
                        <th>Txns</th>
                        <th>Cashier</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($openings as $opening)
                        <tr>
                            <td>
                                <div class="cell-title">{{ $opening->opening_date->format('d M Y') }}</div>
                                <div class="cell-sub">{{ $opening->opening_date->format('l') }}</div>
                            </td>
                            <td>
                                <span class="tag {{ $opening->is_closed ? 'tag-grey' : 'tag-green' }}">
                                    {{ $opening->is_closed ? 'Closed' : 'Open' }}
                                </span>
                            </td>
                            <td>@money($opening->cash_opening)</td>
                            <td>@money($opening->cash_closing ?? 0)</td>
                            <td>@money(array_sum($opening->float_openings ?? []))</td>
                            <td>@money(array_sum($opening->float_closings ?? []))</td>
                            <td>@money($opening->total_volume)</td>
                            <td>@money($opening->total_commission)</td>
                            <td>{{ $opening->total_transactions }}</td>
                            <td>{{ $opening->user?->name ?? '—' }}</td>
                            <td>
                                <div class="row-actions">
                                    <button onclick="window.location='{{ route('daily-opening.show', $opening) }}'" title="View">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="empty-state"><h4>No daily openings recorded yet</h4><p>Record your first daily opening to start tracking.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $openings->links() }}
@endsection