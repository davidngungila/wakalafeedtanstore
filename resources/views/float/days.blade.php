@extends('layouts.app')

@section('title', 'Manage All Days — Float')

@section('content')
    <div class="view-head">
        <div>
            <h2>Manage All Days — Float & Cash Openings</h2>
            <p class="sub">Each day added — full single day management. Dedicated page; single-day view (<code>/float?date=</code>) now shows only its own day.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('float.index') }}" class="btn btn-ghost">← Back to Float (today)</a>
            <a href="{{ route('float.create', ['date' => \Illuminate\Support\Facades\Crypt::encryptString(today()->toDateString()), 'type' => 'cash_to_float']) }}" class="btn btn-primary">Cash → Float</a>
            <a href="{{ route('float.opening.edit', ['date' => \Illuminate\Support\Facades\Crypt::encryptString(today()->toDateString())]) }}" class="btn btn-primary">+ Add / Edit Opening</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Each Day Added — Table with Action Icons</h3>
            <span class="tag tag-terracotta">{{ $openings->total() }} days</span>
        </div>
        <div class="panel-body" style="padding:0;">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Cash Opening</th>
                            <th>Float Total</th>
                            <th>Txs / Vol</th>
                            <th>Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($openings as $op)
                        <tr>
                            <td>
                                <span class="cell-title">{{ $op->opening_date->format('Y-m-d') }}</span>
                                <span class="cell-sub">{{ $op->opening_date->format('l') }}</span>
                            </td>
                            <td class="cell-title">@money($op->cash_opening)</td>
                            <td>@money($op->totalFloatOpening())</td>
                            <td>{{ $op->total_transactions }} txs<br><span class="cell-sub">@money($op->total_volume)</span></td>
                            <td><span class="tag {{ $op->is_closed ? 'tag-grey' : 'tag-green' }}">{{ $op->is_closed ? 'Closed' : 'Open' }}</span></td>
                            <td style="white-space:nowrap; text-align:center;">
                                <a href="{{ route('float.create', ['date' => \Illuminate\Support\Facades\Crypt::encryptString($op->opening_date->toDateString()), 'type' => 'cash_to_float']) }}" title="Transfer cash to float for this day" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:inline-flex;align-items:center;justify-content:center;color:var(--acacia-600);margin-right:4px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M12 5v14"></path><path d="m19 12-7 7-7-7"></path></svg>
                                </a>
                                <a href="{{ route('float.day', ['date' => \Illuminate\Support\Facades\Crypt::encryptString($op->opening_date->toDateString())]) }}" title="View day — only its opening (its page)" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:inline-flex;align-items:center;justify-content:center;color:var(--ink);margin-right:4px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>
                                <a href="{{ route('float.opening.edit', ['date' => \Illuminate\Support\Facades\Crypt::encryptString($op->opening_date->toDateString())]) }}" title="Edit opening" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:inline-flex;align-items:center;justify-content:center;color:var(--acacia-600);margin-right:4px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 1 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </a>
                                <button type="button" onclick="deleteDay('{{ $op->opening_date->toDateString() }}', '{{ $op->opening_date->format('Y-m-d') }}', false)" title="Delete day (opening only)" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--line);background:var(--white);display:inline-flex;align-items:center;justify-content:center;color:var(--danger);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="empty-state"><h4>No days added yet</h4><p>Create your first opening.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding:12px;">
                {{ $openings->links() }}
            </div>
            <div style="padding:10px 12px; background:var(--sand-50); border-top:1px solid var(--line); font-size:12px; color:var(--ink-soft);">
                Single-day page <code>/float/day/{encrypted}</code> (and legacy <code>/float?date=eyJ...</code>) is clean — each shows only its own opening. Use this table as <strong>index</strong> for all days. Use the green arrow to transfer cash to that day’s float and enter the amount. Icons: ↓ Transfer cash to float, 👁 View dedicated day page (only that day opened), ✏️ Edit opening, 🗑️ Delete day.
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        async function deleteDay(dateStr, display, withTransactions) {
            if (!confirm('Delete opening for ' + display + (withTransactions ? ' + ALL transactions & float for that day?' : ' (opening only)?') + ' This cannot be undone.')) return;
            try {
                const url = '/float/opening/' + encodeURIComponent(dateStr) + (withTransactions ? '?with_transactions=1' : '');
                const resp = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.success) {
                    toast(data.message || 'Day deleted', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    toast(data.message || 'Failed to delete day', 'error');
                }
            } catch (e) { toast('Network error', 'error'); }
        }
    </script>
@endsection
