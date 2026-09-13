@extends('layouts.app')

@section('title', 'Cash & Float')

@section('content')
    <div class="view-head">
        <div>
            <h2>Cash &amp; Float</h2>
            <p class="sub">Manage mobile-money float on your networks and track cash moving in and out of the till.</p>
        </div>
        <div class="view-actions">
            <button class="btn btn-primary" onclick="openModal('floatModal')">+ New float / cash entry</button>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg></div><span class="stat-trend up">{{ $summary['networks'] }} networks</span></div>
            <div class="stat-value">@money($summary['totalFloat'])</div>
            <div class="stat-label">Total float balance</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg></div></div>
            <div class="stat-value">@money($summary['floatOut'])</div>
            <div class="stat-label">Float in circulation</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path></svg></div></div>
            <div class="stat-value">@money($summary['totalCash'])</div>
            <div class="stat-label">Cash at till</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money($summary['floatCapacity'])</div>
            <div class="stat-label">Combined float capacity</div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Network</th>
                        <th>Opening</th>
                        <th>Current balance</th>
                        <th>Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balances as $balance)
                        <tr>
                            <td>
                                <span class="net-dot" style="background:{{ $balance->network?->color }};"></span>
                                {{ $balance->network?->name }}
                            </td>
                            <td>@money($balance->opening_balance)</td>
                            <td class="cell-title">@money($balance->balance)</td>
                            <td>
                                @if ($balance->balance >= $balance->opening_balance)
                                    <span class="tag tag-green">+@money($balance->balance - $balance->opening_balance)</span>
                                @else
                                    <span class="tag tag-red">-@money($balance->opening_balance - $balance->balance)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state"><h4>No float balances</h4><p>Add your first float entry above.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Recent float activity</h3>
            <span class="link">Latest 50</span>
        </div>
        <div class="table-scroll">
            <table style="min-width:700px;">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Operator</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($floatTransactions as $ft)
                        <tr>
                            <td>
                                <div class="cell-title">{{ $ft->reference }}</div>
                                <div class="cell-sub">{{ $ft->created_at->format('d M Y · H:i') }}</div>
                            </td>
                            <td>
                                <span class="tag {{ $ft->type === 'float_topup' || $ft->type === 'cash_in' ? 'tag-green' : 'tag-terracotta' }}">
                                    {{ $ft->type === 'cash_in' ? 'Cash in' : ($ft->type === 'cash_out' ? 'Cash out' : ($ft->type === 'float_topup' ? 'Float top-up' : 'Float pull')) }}
                                </span>
                            </td>
                            <td>
                                <span class="net-dot" style="background:{{ $ft->network?->color }};"></span>
                                {{ $ft->network?->name }}
                            </td>
                            <td class="cell-title">@money($ft->amount)</td>
                            <td>{{ $ft->operator?->name ?? '—' }}</td>
                            <td><span class="tag tag-green">Completed</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No float transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- New float / cash entry modal -->
    <div class="modal-backdrop" id="floatModal">
        <div class="modal">
            <div class="modal-head">
                <h3>New float / cash entry</h3>
                <button class="modal-close" onclick="closeModal('floatModal')">✕</button>
            </div>
            <form action="{{ route('float.store') }}" method="POST" data-float-form>
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="field">
                            <label>Network</label>
                            <select name="network_id" required>
                                @foreach ($networks as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Type</label>
                            <select name="type">
                                <option value="float_topup">Float top-up (float in)</option>
                                <option value="float_pull">Float pull (float out)</option>
                                <option value="cash_in">Cash deposited to network</option>
                                <option value="cash_out">Cash withdrawn from network</option>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label>Amount (TZS)</label>
                        <input type="number" name="amount" min="1" step="any" placeholder="e.g. 500000" required>
                    </div>
                    <div class="field">
                        <label>Notes</label>
                        <textarea name="notes" rows="2" placeholder="Optional reference…"></textarea>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('floatModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save entry</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-float-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 600) });
            });
        });
    </script>
@endsection