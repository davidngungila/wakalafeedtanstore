@extends('layouts.app')

@section('title', 'Reconciliation #' . $reconciliation->code)

@section('content')
    <div class="view-head">
        <div>
            <h2>Reconciliation #{{ $reconciliation->code }}</h2>
            <p class="sub">
                {{ $reconciliation->reconciliation_date->format('l, j F Y') }}
                · <span class="tag {{ status_badge($reconciliation->status) }}">{{ ucfirst($reconciliation->status) }}</span>
                @if ($reconciliation->agent)
                    · {{ $reconciliation->agent->code }}
                @endif
            </p>
        </div>
        <div class="view-actions">
            <a href="{{ route('reconciliation.index') }}" class="btn btn-ghost">← Back to list</a>
            @if(is_admin())
                <button type="button" onclick="openModal('deleteReconShowModal')" class="btn btn-danger">Delete reconciled</button>
            @endif
        </div>

    <div class="modal-backdrop" id="deleteReconShowModal">
        <div class="modal" style="max-width:440px;">
            <div class="modal-head">
                <h3>Delete Reconciliation</h3>
                <button class="modal-close" onclick="closeModal('deleteReconShowModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13.5px;color:var(--ink-soft);">Delete reconciliation <strong>{{ $reconciliation->code }}</strong> for <strong>{{ $reconciliation->reconciliation_date->format('Y-m-d') }}</strong>? This cannot be undone. Its {{ $reconciliation->corrections->count() }} correction(s) will also be removed.</p>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" onclick="closeModal('deleteReconShowModal')">Cancel</button>
                <form id="deleteReconShowForm" method="POST" action="{{ route('reconciliation.destroy', $reconciliation) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
    </div>

    @if ($errors->any())
        <div class="box-alert">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if (session('status'))
        <div class="box-alert" style="background:var(--acacia-50,var(--acacia-100));color:var(--acacia-700,var(--acacia-600));">
            {{ session('status') }}
        </div>
    @endif

    <div class="balance-strip">
        <div class="balance-box" style="--stat-tint:var(--sand-100);">
            <div class="bb-label">Expected cash</div>
            <div class="bb-amount">@money($reconciliation->expected_cash)</div>
            <div class="bb-sub">System balance before this session.</div>
        </div>
        <div class="balance-box" style="--stat-tint:var(--terracotta-100);">
            <div class="bb-label">Counted cash</div>
            <div class="bb-amount">@money($reconciliation->counted_cash)</div>
            <div class="bb-sub">Cash physically in the till.</div>
        </div>
        <div class="balance-box" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
            <div class="bb-label">Cash variance</div>
            <div class="bb-amount">{{ $reconciliation->cash_variance > 0 ? '+' : '' }}@money($reconciliation->cash_variance)</div>
            <div class="bb-sub">Counted minus expected.</div>
        </div>
        <div class="balance-box" style="--stat-tint:var(--terracotta-100);">
            <div class="bb-label">Float variance</div>
            <div class="bb-amount">{{ $reconciliation->float_variance > 0 ? '+' : '' }}@money($reconciliation->float_variance)</div>
            <div class="bb-sub">Counted float minus expected float.</div>
        </div>
    </div>

    <div class="table-card">
        <div class="panel-head" style="padding:16px 20px;border-bottom:1px solid var(--line);">
            <h3>Reconciliation run</h3>
            <span class="link">Opening → activity → expected closing</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Opening</th>
                        <th>Deposits</th>
                        <th>Withdrawals</th>
                        <th>= Expected closing</th>
                        <th>Counted</th>
                        <th>Variance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="cell-title">Cash in Till</div>
                            <div class="cell-sub">Opening + deposits − withdrawals</div>
                        </td>
                        <td>@money($run['openingCash'])</td>
                        <td>+@money($run['cashDeposits'])</td>
                        <td>−@money($run['cashWithdrawals'])</td>
                        <td>@money($run['expectedCash'])</td>
                        <td>@money($run['countedCash'])</td>
                        <td>
                            <span class="tag {{ abs($run['cashVariance']) < 0.005 ? 'tag-green' : ($run['cashVariance'] > 0 ? 'tag-gold' : 'tag-red') }}">
                                {{ $run['cashVariance'] > 0 ? '+' : '' }}@money($run['cashVariance'])
                            </span>
                        </td>
                    </tr>
                    @forelse ($run['networks'] as $row)
                        <tr>
                            <td>
                                <div class="cell-title">{{ $row['network'] }} Float</div>
                                <div class="cell-sub">Opening − deposits + withdrawals + top-ups</div>
                            </td>
                            <td>@money($row['opening'])</td>
                            <td>−@money($row['deposits'])</td>
                            <td>+@money($row['withdrawals'])</td>
                            <td>@money($row['expected'])</td>
                            <td>@money($row['counted'])</td>
                            <td>
                                <span class="tag {{ abs($row['variance']) < 0.005 ? 'tag-green' : ($row['variance'] > 0 ? 'tag-gold' : 'tag-red') }}">
                                    {{ $row['variance'] > 0 ? '+' : '' }}@money($row['variance'])
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state"><p>No network float rows recorded for this session.</p></td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    @php
                        $totalOpening = $run['openingCash'] + $run['openingFloat'];
                        $totalDeposits = $run['cashDeposits'] + collect($run['networks'])->sum('deposits');
                        $totalWithdrawals = $run['cashWithdrawals'] + collect($run['networks'])->sum('withdrawals');
                        $totalExpected = $run['expectedCash'] + $run['expectedFloat'];
                        $totalCounted = $run['countedCash'] + $run['countedFloat'];
                        $totalVariance = $run['cashVariance'] + ($run['countedFloat'] - $run['expectedFloat']);
                    @endphp
                    <tr style="font-weight:700; background:var(--sand-50); border-top:2px solid var(--line);">
                        <td>Total</td>
                        <td>@money($totalOpening)</td>
                        <td>+@money($totalDeposits)</td>
                        <td>−@money($totalWithdrawals)</td>
                        <td>@money($totalExpected)</td>
                        <td>@money($totalCounted)</td>
                        <td>
                            <span class="tag {{ abs($totalVariance) < 0.005 ? 'tag-green' : ($totalVariance > 0 ? 'tag-gold' : 'tag-red') }}">
                                {{ $totalVariance > 0 ? '+' : '' }}@money($totalVariance)
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Tie-out check</h3>
            <span class="link">Expected total must equal counted total — float top-ups are bank-replenished and already in expected (not a variance)</span>
        </div>
        <div class="panel-body">
            <div class="balance-strip" style="margin-bottom:0;">
                <div class="balance-box" style="--stat-tint:var(--sand-100);">
                    <div class="bb-label">Opening cash on hand</div>
                    <div class="bb-amount">@money($run['openingCash'])</div>
                </div>
                <div class="balance-box" style="--stat-tint:var(--sand-100);">
                    <div class="bb-label">+ Opening float (total)</div>
                    <div class="bb-amount">+@money($run['openingFloat'])</div>
                </div>
                <div class="balance-box" style="--stat-tint:var(--sand-100);">
                    <div class="bb-label">= Opening total</div>
                    <div class="bb-amount">@money($run['openingCash'] + $run['openingFloat'])</div>
                </div>
                <div class="balance-box" style="--stat-tint:var(--gold-100);">
                    <div class="bb-label">Expected closing total</div>
                    <div class="bb-amount">@money($run['expectedCash'] + $run['expectedFloat'])</div>
                    <div class="bb-sub">Cash @money($run['expectedCash']) + Float @money($run['expectedFloat']) — includes top-ups.</div>
                </div>
                <div class="balance-box">
                    <div class="bb-label">Counted closing cash in hand</div>
                    <div class="bb-amount">@money($run['countedCash'])</div>
                </div>
                <div class="balance-box">
                    <div class="bb-label">+ Counted closing float</div>
                    <div class="bb-amount">+@money($run['countedFloat'])</div>
                </div>
                <div class="balance-box" style="--stat-tint:{{ abs($run['tieOut']) < 0.005 ? 'var(--acacia-100)' : 'var(--gold-100)' }};">
                    <div class="bb-label">Tie-out (must be 0)</div>
                    <div class="bb-amount">
                        @if (abs($run['tieOut']) < 0.005)
                            <span style="color:var(--acacia-600);">@money(0) ✓</span>
                        @else
                            <span style="color:#8a6418;">{{ $run['tieOut'] > 0 ? '+' : '' }}@money($run['tieOut'])</span>
                        @endif
                    </div>
                    <div class="bb-sub">Expected total minus counted total (sum of variances; float top-up injection ignored).</div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="panel-head" style="padding:16px 20px;border-bottom:1px solid var(--line);">
            <h3>Channels breakdown</h3>
            <span class="link">Settlement per channel</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>System (expected)</th>
                        <th>Counted</th>
                        <th>Variance</th>
                        <th>Settled by corrections</th>
                        <th>Remaining</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="channelRows">
                    @forelse ($channels as $channel)
                        <tr>
                            <td>
                                <div class="cell-title">{{ $channel['label'] }}</div>
                            </td>
                            <td>@money($channel['system'])</td>
                            <td>@money($channel['counted'])</td>
                            <td>
                                <span class="tag {{ $channel['variance'] == 0 ? 'tag-green' : ($channel['variance'] > 0 ? 'tag-gold' : 'tag-red') }}">
                                    {{ $channel['variance'] > 0 ? '+' : '' }}@money($channel['variance'])
                                </span>
                            </td>
                            <td>
                                @if ($channel['settled'] == 0)
                                    <span class="cell-sub">—</span>
                                @else
                                    <span class="tag tag-terracotta">{{ $channel['settled'] > 0 ? '+' : '' }}@money($channel['settled'])</span>
                                @endif
                            </td>
                            <td>
                                @if (abs($channel['remaining']) < 0.005)
                                    <span class="tag tag-green">0</span>
                                @else
                                    <span class="tag {{ $channel['remaining'] > 0 ? 'tag-gold' : 'tag-red' }}">
                                        {{ $channel['remaining'] > 0 ? '+' : '' }}@money($channel['remaining'])
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if (abs($channel['remaining']) < 0.005)
                                    <span class="tag tag-green">Settled</span>
                                @else
                                    <span class="tag tag-gold">Open</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state"><h4>No channel data</h4><p>This session has no recorded balances.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($reconciliation->notes)
        <div class="panel">
            <div class="panel-head"><h3>Notes</h3></div>
            <div class="panel-body">
                <p style="font-size:13.5px;color:var(--ink-soft);">{{ $reconciliation->notes }}</p>
                <p class="sub" style="margin-top:8px;">Recorded by {{ $reconciliation->reconciler?->name ?? '—' }} on {{ $reconciliation->created_at->format('d M Y H:i') }}</p>
            </div>
        </div>
    @endif

    <div class="panel">
        <div class="panel-head">
            <h3>Corrections</h3>
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="link">{{ $reconciliation->corrections->count() }} recorded</span>
                <a href="{{ route('reconciliation.corrections.create', $reconciliation) }}" class="btn btn-primary btn-sm">+ Add correction</a>
            </div>
        </div>
        <div class="panel-body">
                @forelse ($reconciliation->corrections as $correction)
                    <div class="activity-row">
                        <div class="activity-ico" style="background:var(--terracotta-100);color:var(--terracotta-600);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                        </div>
                        <div class="activity-text" style="flex:1;min-width:0;">
                            <div class="activity-time" style="margin-bottom:4px;">{{ $correction->scope === 'cash' ? 'Cash in Till' : $correction->network?->name }}</div>
                            <div class="activity-row" style="gap:6px;align-items:center;margin-bottom:4px;">
                                <b>{{ $correction->typeLabel() }}</b>
                                <span class="tag {{ $correction->signedAmount() > 0 ? 'tag-terracotta' : 'tag-red' }}">
                                    {{ $correction->signedAmount() > 0 ? '+' : '' }}@money($correction->amount)
                                </span>
                            </div>
                            <div class="cell-sub" style="margin-bottom:4px;">
                                Reference: <strong style="color:var(--ink,var(--coffee-700));">{{ $correction->reference }}</strong>
                            </div>
                            @if ($correction->notes)
                                <div class="activity-time" style="margin-bottom:4px;">{{ $correction->notes }}</div>
                            @endif
                            <div class="activity-time">Added by {{ $correction->creator?->name ?? '—' }} · {{ $correction->created_at->format('d M Y H:i') }}</div>
                        </div>
                        <form method="POST" action="{{ route('reconciliation.corrections.destroy', [$reconciliation, $correction]) }}" onsubmit="return confirm('Remove this correction?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost" title="Remove correction">✕</button>
                        </form>
                    </div>
                @empty
                    <p class="empty-state" style="padding:20px;text-align:center;">No corrections yet. Add corrections below to settle the variance.</p>
                @endforelse
            </div>
        </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('deleteReconShowForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            submitForm(e.target, { method: 'POST', done: () => window.location.href = '{{ route('reconciliation.index') }}' });
        });
    </script>
@endsection