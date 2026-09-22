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

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Corrections</h3>
                <span class="link">{{ $reconciliation->corrections->count() }} recorded</span>
            </div>
            <div class="panel-body">
                @forelse ($reconciliation->corrections as $correction)
                    <div class="activity-row">
                        <div class="activity-ico" style="background:var(--terracotta-100);color:var(--terracotta-600);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                        </div>
                        <div class="activity-text" style="flex:1;min-width:0;">
                            <div>
                                <b>{{ $correction->typeLabel() }}</b>
                                <span class="tag {{ $correction->signedAmount() > 0 ? 'tag-terracotta' : 'tag-red' }}" style="margin-left:6px;">
                                    {{ $correction->signedAmount() > 0 ? '+' : '' }}@money($correction->amount)
                                </span>
                            </div>
                            <div class="activity-time">
                                Ref: <strong>{{ $correction->reference }}</strong>
                                · {{ $correction->scope === 'cash' ? 'Cash' : $correction->network?->name }}
                                @if ($correction->notes)
                                    · {{ $correction->notes }}
                                @endif
                            </div>
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

        <div class="panel">
            <div class="panel-head">
                <h3>Add correction</h3>
                <span class="link">Fix differences with a reference</span>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('reconciliation.corrections.store', $reconciliation) }}" data-correction-form>
                    @csrf
                    <div class="form-row">
                        <div class="field">
                            <label>Applies to</label>
                            <select name="scope" id="corrScope" required onchange="toggleCorrNetwork()">
                                <option value="cash">Cash in Till</option>
                                <option value="float">Network Float</option>
                            </select>
                        </div>
                        <div class="field" id="corrNetworkWrap" style="display:none;">
                            <label>Network</label>
                            <select name="network_id" id="corrNetwork">
                                <option value="">Select network</option>
                                @foreach ($networks as $network)
                                    <option value="{{ $network->id }}">{{ $network->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label>Correction type</label>
                        <select name="type" required>
                            @foreach (\App\Models\ReconciliationCorrection::types() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Reference <span style="font-weight:400;color:var(--ink-soft);font-size:12px;">(transaction / customer / reason)</span></label>
                            <input type="text" name="reference" maxlength="120" placeholder="e.g. TXN-88213 or customer name" required>
                        </div>
                        <div class="field">
                            <label>Amount (TZS)</label>
                            <input type="number" name="amount" min="0.01" step="0.01" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Notes (optional)</label>
                        <textarea name="notes" rows="2" maxlength="1000" placeholder="Additional details..."></textarea>
                    </div>
                    <div class="modal-foot" style="padding:0;margin-top:16px;">
                        <button type="submit" class="btn btn-primary">Record correction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleCorrNetwork() {
            const v = document.getElementById('corrScope').value;
            document.getElementById('corrNetworkWrap').style.display = v === 'float' ? 'block' : 'none';
            document.getElementById('corrNetwork').removeAttribute('required');
            if (v === 'float') document.getElementById('corrNetwork').setAttribute('required', 'required');
        }

        document.querySelectorAll('[data-correction-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 600) });
            });
        });
    </script>
@endsection