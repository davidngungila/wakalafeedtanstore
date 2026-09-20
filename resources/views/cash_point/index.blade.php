@extends('layouts.app')

@section('title', 'Cash Point')

@section('content')
    <div class="view-head">
        <div>
            <h2>{{ $agent->name }}</h2>
            <p class="sub">
                {{ $agent->code }} ·
                {!! '<span class="tag '.agent_level_badge($agent->agent_level).'">'.agent_level_label($agent->agent_level).'</span>' !!}
                {!! '<span class="tag '.status_badge($agent->status).'">'.ucfirst($agent->status).'</span>' !!}
                <span style="white-space:nowrap;">&nbsp;· Single cash point — whole system manages this wakala only</span>
            </p>
        </div>
        <div class="view-actions">
            <a class="btn btn-ghost" href="{{ route('float.index') }}">Manage float</a>
            @if (is_admin())
                <button class="btn btn-primary" onclick="openCashPointDrawer()">Edit profile</button>
            @endif
        </div>
    </div>

    @if (! $isOpeningDone)
        <div class="box-alert" style="background:var(--gold-100);border-left-color:var(--gold-600);">
            <strong>Daily opening not recorded yet.</strong> You must record opening cash and float balances before processing transactions.
            <a href="{{ route('daily-opening.create') }}" class="btn btn-primary" style="margin-left:12px;">Record Daily Opening</a>
        </div>
    @else
        <div class="panel" style="margin-bottom:16px;">
            <div class="panel-head">
                <h3>Today's Session {!! $todayStats['is_closed'] ? '<span class="tag tag-grey">Closed</span>' : '<span class="tag tag-green">Open</span>' !!}</h3>
                <a href="{{ route('daily-opening.show', $todayOpening) }}" class="link">View details</a>
            </div>
            <div class="panel-body">
                <div class="activity-list">
                    <div class="activity-row" style="flex-wrap:wrap;gap:8px 14px;">
                        <div class="activity-ico" style="background:var(--gold-100);color:var(--gold-600);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle></svg>
                        </div>
                        <div class="activity-text" style="flex:1 1 320px;min-width:0;">
                            <b>Cash in Hand</b>
                            <div style="display:flex;flex-wrap:wrap;gap:6px 14px;font-size:12.5px;color:var(--ink-soft);line-height:1.7;margin-top:4px;">
                                <span>Opening: <strong style="color:var(--ink);">@money($todayStats['cash_opening'])</strong></span>
                                <span>Deposits: <strong style="color:var(--ink);">@money($todayStats['today_deposits'])</strong></span>
                                <span>Withdrawals: <strong style="color:var(--ink);">@money($todayStats['today_withdrawals'])</strong></span>
                                <span>Commission: <strong style="color:var(--ink);">@money($todayStats['commission'])</strong></span>
                                <span>Expected: <strong style="color:var(--acacia-600);">@money($todayStats['expected_closing_cash'])</strong></span>
                                <span>Current: <strong style="color:var(--coffee-900);">@money($todayStats['cash_current'])</strong></span>
                            </div>
                        </div>
                        <div style="flex:0 0 auto;margin-left:auto;font-weight:600;color:{{ ($todayStats['cash_current'] - $todayStats['expected_closing_cash']) >= 0 ? 'var(--acacia-600)' : 'var(--danger)' }};">
                            @money($todayStats['cash_current'] - $todayStats['expected_closing_cash'])
                        </div>
                    </div>
                    @foreach ($agent->balances as $balance)
                        @php
                            $openingFloat = $todayOpening->getFloatOpening($balance->network_id);
                            $currentFloat = $balance->balance;
                            $variance = $currentFloat - $openingFloat;
                        @endphp
                        <div class="activity-row">
                            <div class="activity-ico" style="background:{{ $balance->network?->color }}22;color:{{ $balance->network?->color }};">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                            </div>
                            <div class="activity-text" style="flex:1;min-width:0;">
                                <b>{{ $balance->network?->name }}</b>
                                <div class="activity-time">
                                    Opening: <strong>@money($openingFloat)</strong> ·
                                    Current: <strong style="color:var(--coffee-900);">@money($currentFloat)</strong>
                                </div>
                            </div>
                            <div style="font-weight:600;color:{{ $variance >= 0 ? 'var(--acacia-600)' : 'var(--danger)' }};">
                                @money($variance)
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
                <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
                <div class="stat-value">@money($todayStats['volume'])</div>
                <div class="stat-label">Today's Volume</div>
            </div>
            <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
                <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"></path><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></div></div>
                <div class="stat-value">@money($todayStats['commission'])</div>
                <div class="stat-label">Commission Earned</div>
            </div>
            <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
                <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg></div></div>
                <div class="stat-value">{{ $todayStats['count'] }}</div>
                <div class="stat-label">Today's Transactions</div>
            </div>
        </div>
    @endif

    @if ($isOpeningDone)
    <div class="panel-grid">
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
                <tbody id="cpTxnRows">
                    @forelse ($recentTransactions as $txn)
                        <tr data-id="{{ $txn->id }}">
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
    @endif
@endsection

@section('scripts')
    <script>
        @php
            $cpTxns = $recentTransactions->map(fn ($t) => [
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
        const cpTxns = @json($cpTxns);

        function cpFmt(n) { return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }

        bindRowClick('#cpTxnRows tr[data-id]', tr => {
            const t = cpTxns.find(x => Number(x.id) === Number(tr.dataset.id));
            if (!t) return [];
            return [
                ['Reference', t.reference],
                ['Provider ref', t.provider_reference || '—'],
                ['Date', t.created_at],
                ['Type', t.type.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' ')],
                ['Network', t.network ? { __html: `<span class="net-dot" style="background:${t.network_color || '#999'};"></span> ${t.network}` } : '—'],
                ['Customer', t.customer_name || '—'],
                ['Phone', t.customer_phone],
                ['Amount', cpFmt(t.amount)],
                ['Fee', cpFmt(t.fee)],
                ['Commission', cpFmt(t.commission)],
                ['Status', { __html: statusBadgeHtml(t.status) }],
                ...(t.reversal_reason ? [['Reversal reason', t.reversal_reason]] : []),
                ...(t.notes ? [['Notes', t.notes]] : []),
                ['Operator', t.operator || '{{ auth()->user()->name }}'],
            ];
        }, 'Transaction details');

        function openCashPointDrawer() { openModal('cashPointDrawer'); }
        document.querySelectorAll('[data-cashpoint-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: () => setTimeout(() => location.reload(), 600) });
            });
        });
    </script>
@endsection