@extends('layouts.app')

@section('title', 'Cash Point')

@section('content')
    <div class="view-head">
        <div>
            <h2>{{ $agent->name }}</h2>
            <p class="sub">
                {{ $agent->code }} ·
                <span class="tag {{ agent_level_badge($agent->agent_level) }}">{{ agent_level_label($agent->agent_level) }}</span>
                <span class="tag {{ status_badge($agent->status) }}">{{ ucfirst($agent->status) }}</span>
                · Single cash point — whole system manages this wakala only
            </p>
        </div>
        <div class="view-actions">
            <a class="btn btn-ghost" href="{{ route('float.index') }}">Manage float</a>
            @if (is_admin())
                <button class="btn btn-primary" onclick="openCashPointDrawer()">Edit profile</button>
            @endif
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path></svg></div><span class="stat-trend up">Till</span></div>
            <div class="stat-value">@money($summary['cash'])</div>
            <div class="stat-label">Cash at till</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg></div></div>
            <div class="stat-value">@money($summary['float'])</div>
            <div class="stat-label">Total float balance</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money($summary['volume'])</div>
            <div class="stat-label">Completed volume</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg></div></div>
            <div class="stat-value">{{ $summary['count'] }}</div>
            <div class="stat-label">Total transactions</div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Network float balances</h3>
                <span class="link">Live</span>
            </div>
            <div class="panel-body">
                <div class="activity-list">
                    @forelse ($agent->balances as $balance)
                        <div class="activity-row">
                            <div class="activity-ico" style="background:{{ $balance->network?->color }}22;color:{{ $balance->network?->color }};">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                            </div>
                            <div class="activity-text">
                                <b>{{ $balance->network?->name }}</b>
                                <div class="activity-time">Opening: <strong>@money($balance->opening_balance)</strong> · Current: <strong style="color:var(--coffee-900);">@money($balance->balance)</strong></div>
                            </div>
                        </div>
                    @empty
                        <p class="empty-state" style="padding:30px 10px;">No network balances yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Cash point details</h3>
                @if (is_admin())
                    <button class="link" onclick="openCashPointDrawer()">Edit</button>
                @endif
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Code</div><div class="dv">{{ $agent->code }}</div></div>
                    <div class="detail-item"><div class="dk">Owner</div><div class="dv">{{ $agent->owner_name ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Phone</div><div class="dv">{{ $agent->phone }}</div></div>
                    <div class="detail-item"><div class="dk">National ID</div><div class="dv">{{ $agent->national_id ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Level</div><div class="dv">{{ agent_level_label($agent->agent_level) }}</div></div>
                    <div class="detail-item"><div class="dk">Status</div><div class="dv">{{ ucfirst($agent->status) }}</div></div>
                    <div class="detail-item"><div class="dk">Region</div><div class="dv">{{ $agent->region ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">District</div><div class="dv">{{ $agent->district ?? '—' }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Recent transactions</h3>
            <a href="{{ route('transactions.index') }}" class="link">View all</a>
        </div>
        <div class="table-scroll">
            <table style="min-width:680px;">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $txn)
                        <tr>
                            <td>
                                <div class="cell-title">{{ $txn->reference }}</div>
                                <div class="cell-sub">{{ $txn->created_at->format('d M Y, H:i') }}</div>
                            </td>
                            <td>
                                <div class="cell-title">{{ $txn->customer_name ?? '—' }}</div>
                                <div class="cell-sub">{{ $txn->customer_phone }}</div>
                            </td>
                            <td>{{ txn_type_label($txn->type) }}</td>
                            <td>
                                <span class="net-dot" style="background:{{ $txn->network?->color }};"></span>
                                {{ $txn->network?->name }}
                            </td>
                            <td class="cell-title">@money($txn->amount)</td>
                            <td>@money($txn->commission)</td>
                            <td><span class="tag {{ status_badge($txn->status) }}">{{ ucfirst($txn->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit cash point drawer -->
    <div class="modal-backdrop" id="cashPointDrawer">
        <div class="modal">
            <div class="modal-head">
                <h3>Edit cash point</h3>
                <button class="modal-close" onclick="closeModal('cashPointDrawer')">✕</button>
            </div>
            <form action="{{ route('cash-point.update') }}" method="POST" data-cashpoint-form>
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="field">
                            <label>Payment code</label>
                            <input type="text" name="code" value="{{ $agent->code }}" required>
                        </div>
                        <div class="field">
                            <label>Business name</label>
                            <input type="text" name="name" value="{{ $agent->name }}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Owner name</label>
                            <input type="text" name="owner_name" value="{{ $agent->owner_name ?? '' }}">
                        </div>
                        <div class="field">
                            <label>Phone</label>
                            <input type="text" name="phone" value="{{ $agent->phone }}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>National ID</label>
                            <input type="text" name="national_id" value="{{ $agent->national_id ?? '' }}">
                        </div>
                        <div class="field">
                            <label>Level</label>
                            <select name="agent_level">
                                <option value="bronze" @selected($agent->agent_level === 'bronze')>Bronze</option>
                                <option value="silver" @selected($agent->agent_level === 'silver')>Silver</option>
                                <option value="gold" @selected($agent->agent_level === 'gold')>Gold</option>
                                <option value="platinum" @selected($agent->agent_level === 'platinum')>Platinum</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Region</label>
                            <input type="text" name="region" value="{{ $agent->region ?? '' }}">
                        </div>
                        <div class="field">
                            <label>District</label>
                            <input type="text" name="district" value="{{ $agent->district ?? '' }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Ward</label>
                            <input type="text" name="ward" value="{{ $agent->ward ?? '' }}">
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" @selected($agent->status === 'active')>Active</option>
                                <option value="suspended" @selected($agent->status === 'suspended')>Suspended</option>
                                <option value="inactive" @selected($agent->status === 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label>Street</label>
                        <input type="text" name="street" value="{{ $agent->street ?? '' }}">
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('cashPointDrawer')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save cash point</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openCashPointDrawer() { openModal('cashPointDrawer'); }
        document.querySelectorAll('[data-cashpoint-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: () => setTimeout(() => location.reload(), 600) });
            });
        });
    </script>
@endsection