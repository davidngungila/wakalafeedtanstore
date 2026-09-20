@extends('layouts.app')

@section('title', 'Daily Opening - ' . $dailyOpening->opening_date->format('j M Y'))

@section('content')
    <div class="view-head">
        <div>
            <h2>Daily Opening</h2>
            <p class="sub">{{ $dailyOpening->opening_date->format('l, j F Y') }} · {{ $dailyOpening->is_closed ? '<span class="tag tag-grey">Closed</span>' : '<span class="tag tag-green">Open</span>' }}</p>
        </div>
        <div class="view-actions">
            @if (! $dailyOpening->is_closed)
                <button class="btn btn-primary" onclick="openCloseModal()">Close Day</button>
            @endif
            <a href="{{ route('daily-opening.index') }}" class="btn btn-ghost">History</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="box-alert">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="stat-grid">
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle></svg></div></div>
            <div class="stat-value">@money($dailyOpening->cash_opening)</div>
            <div class="stat-label">Opening Cash</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg></div></div>
            <div class="stat-value">@money(array_sum($dailyOpening->float_openings ?? []))</div>
            <div class="stat-label">Opening Float</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--terracotta-100);--stat-fg:var(--terracotta-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg></div></div>
            <div class="stat-value">@money($todayVolume)</div>
            <div class="stat-label">Today's Volume</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"></path><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></div></div>
            <div class="stat-value">@money($todayCommission)</div>
            <div class="stat-label">Commission Earned</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--acacia-100);--stat-fg:var(--acacia-600);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg></div></div>
            <div class="stat-value">{{ $todayCount }}</div>
            <div class="stat-label">Transactions</div>
        </div>
        <div class="stat-card" style="--stat-tint:var(--danger-100);--stat-fg:var(--danger);">
            <div class="stat-top"><div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg></div></div>
            <div class="stat-value">@money($cashCurrent - $expectedClosingCash)</div>
            <div class="stat-label">Cash Variance (actual − expected)</div>
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Running Balances</h3>
            </div>
            <div class="panel-body">
                <div class="activity-list">
                    <div class="activity-row">
                        <div class="activity-ico" style="background:var(--gold-100);color:var(--gold-600);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle></svg>
                        </div>
                        <div class="activity-text">
                            <b>Cash in Hand</b>
                            <div class="activity-time">
                                Opening: <strong>@money($dailyOpening->cash_opening)</strong> ·
                                Expected: <strong style="color:var(--acacia-600);">@money($expectedClosingCash)</strong> ·
                                Current: <strong style="color:var(--coffee-900);">@money($cashCurrent)</strong>
                            </div>
                        </div>
                        <div style="font-weight:600;color:var(--coffee-900);">
                            @money($cashCurrent - $expectedClosingCash)
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Network Float Running Balances</h3>
            </div>
            <div class="panel-body">
                <div class="activity-list">
                    @foreach ($networks as $network)
                        @php
                            $openingFloat = $dailyOpening->getFloatOpening($network->id);
                            $currentFloat = $currentBalances[$network->id]->balance ?? 0;
                            $variance = $currentFloat - $openingFloat;
                        @endphp
                        <div class="activity-row">
                            <div class="activity-ico" style="background:{{ $network->color }}22;color:{{ $network->color }};">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                            </div>
                            <div class="activity-text" style="flex:1;min-width:0;">
                                <b>{{ $network->name }}</b>
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
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Today's Transactions</h3>
            <a href="{{ route('transactions.index', ['status' => 'completed']) }}" class="link">View all</a>
        </div>
        <div class="table-scroll">
            <table style="min-width:720px;">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Network</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="dailyTxnRows">
                    @php
                        $todayTxns = \App\Models\Transaction::where('agent_id', $agent->id)
                            ->whereDate('created_at', today())
                            ->with(['network', 'operator'])
                            ->latest()
                            ->get();
                    @endphp
                    @forelse ($todayTxns as $txn)
                        <tr data-id="{{ $txn->id }}">
                            <td>
                                <div class="cell-title">{{ $txn->created_at->format('H:i:s') }}</div>
                            </td>
                            <td>
                                <div class="cell-title">{{ $txn->reference }}</div>
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
                        <tr><td colspan="8" class="empty-state">No transactions today.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Close Day Modal -->
    @if (! $dailyOpening->is_closed)
    <div class="modal-backdrop" id="closeDayModal">
        <div class="modal" style="max-width:520px;">
            <div class="modal-head">
                <h3>Close Day</h3>
                <button class="modal-close" onclick="closeModal('closeDayModal')">✕</button>
            </div>
            <form id="closeDayForm" method="POST" action="{{ route('daily-opening.close', $dailyOpening) }}">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <p style="font-size:13.5px;color:var(--ink-soft);margin-bottom:16px;">Enter the actual counted cash and float balances at end of day.</p>

                    <div class="field">
                        <label>Closing Cash (TZS)</label>
                        <input type="number" name="cash_closing" step="any" min="0" required placeholder="e.g. 750000">
                        <p style="font-size:12px;color:var(--ink-soft);margin-top:4px;">System cash: <strong>@money($cashCurrent)</strong> | Expected: <strong>@money($expectedClosingCash)</strong></p>
                    </div>

                    <div class="activity-list" style="max-height:300px;overflow-y:auto;margin:16px 0;">
                        @foreach ($networks as $network)
                            @php
                                $currentFloat = $currentBalances[$network->id]->balance ?? 0;
                            @endphp
                            <div class="activity-row" style="align-items:center;">
                                <div class="activity-ico" style="background:{{ $network->color }}22;color:{{ $network->color }};">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                                </div>
                                <div class="activity-text" style="flex:1;min-width:0;">
                                    <b>{{ $network->name }}</b>
                                    <div class="activity-time">System: <strong>@money($currentFloat)</strong></div>
                                </div>
                                <div class="field" style="min-width:180px;margin:0;">
                                    <input type="number" name="float_closings[{{ $network->id }}]" step="any" min="0" value="{{ $currentFloat }}" required style="width:100%;">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="field">
                        <label>Notes (optional)</label>
                        <textarea name="notes" rows="2" placeholder="Variance notes...">{{ $dailyOpening->notes }}</textarea>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('closeDayModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Close Day</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@section('scripts')
    <script>
        @php
            $dailyTxns = $todayTxns->map(fn ($t) => [
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
        const dailyTxns = @json($dailyTxns);

        function dailyFmt(n) { return 'TZS ' + Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }

        bindRowClick('#dailyTxnRows tr[data-id]', tr => {
            const t = dailyTxns.find(x => Number(x.id) === Number(tr.dataset.id));
            if (!t) return [];
            return [
                ['Reference', t.reference],
                ['Provider ref', t.provider_reference || '—'],
                ['Date', t.created_at],
                ['Type', t.type.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' ')],
                ['Network', t.network ? { __html: `<span class="net-dot" style="background:${t.network_color || '#999'};"></span> ${t.network}` } : '—'],
                ['Customer', t.customer_name || '—'],
                ['Phone', t.customer_phone],
                ['Amount', dailyFmt(t.amount)],
                ['Fee', dailyFmt(t.fee)],
                ['Commission', dailyFmt(t.commission)],
                ['Status', { __html: statusBadgeHtml(t.status) }],
                ...(t.reversal_reason ? [['Reversal reason', t.reversal_reason]] : []),
                ...(t.notes ? [['Notes', t.notes]] : []),
                ['Operator', t.operator || '{{ auth()->user()->name }}'],
            ];
        }, 'Transaction details');

        function openCloseModal() { openModal('closeDayModal'); }

        document.getElementById('closeDayForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            submitForm(e.target, { method: 'PUT', done: () => setTimeout(() => location.reload(), 600) });
        });
    </script>
@endsection