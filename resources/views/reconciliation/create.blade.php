@extends('layouts.app')

@section('title', 'New Reconciliation')

@section('content')
    <div class="view-head">
        <div>
            <h2>New Reconciliation</h2>
            <p class="sub">Reconcile {{ \Illuminate\Support\Carbon::parse($run['date'])->format('l, j F Y') }} — opening balances plus the day's customer activity must tie to the closing balances you count.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('reconciliation.index') }}" class="btn btn-ghost">← Back to reconciliation</a>
        </div>
    </div>

    @if($isAdmin)
        <div class="panel" style="border-left:3px solid var(--terracotta-600);">
            <div class="panel-head">
                <h3>Admin — Select Date for Reconciliation</h3>
                <span class="tag tag-terracotta">Any previous day</span>
            </div>
            <div class="panel-body">
                <form method="GET" action="{{ route('reconciliation.create') }}" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
                    <div class="field" style="margin-bottom:0;">
                        <label>Reconciliation date</label>
                        <input type="date" name="date" value="{{ $selectedDate }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()">
                    </div>
                    <button type="submit" class="btn btn-primary">Load day</button>
                    <a href="{{ route('reconciliation.create') }}" class="btn btn-ghost">Today</a>
                    @if($availableDates->isNotEmpty())
                        <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center; margin-left:8px;">
                            <span style="font-size:12px; color:var(--ink-soft);">Recent openings:</span>
                            @foreach($availableDates->take(5) as $d)
                                <a href="{{ route('reconciliation.create', ['date' => $d]) }}" class="tag {{ $d === $selectedDate ? 'tag-green' : 'tag-terracotta' }}" style="text-decoration:none;">{{ $d }}</a>
                            @endforeach
                        </div>
                    @endif
                </form>
                @if($existing)
                    <div style="margin-top:12px; padding:10px 12px; background:var(--gold-100); border:1px solid var(--line); border-radius:8px; font-size:13px;">
                        <strong>Already reconciled for {{ $selectedDate }}:</strong> Status <span class="tag {{ $existing->status === 'reconciled' ? 'tag-green' : ($existing->status === 'variance' ? 'tag-red' : 'tag-gold') }}">{{ ucfirst($existing->status) }}</span> · Expected cash @money($existing->expected_cash) · Counted @money($existing->counted_cash) · <a href="{{ route('reconciliation.show', $existing) }}">View existing</a> — you can still create another for this date, or edit the existing.
                    </div>
                @else
                    <div style="margin-top:12px; font-size:12px; color:var(--ink-soft);">No reconciliation yet for {{ $selectedDate }} — this form will create one. All options (cash, float per network, tie-out) are loaded for that day's opening + activity.</div>
                @endif
                <div style="margin-top:10px; font-size:12px; color:var(--ink-soft); line-height:1.6;">
                    Admin can load <strong>any previous day</strong> and reconcile it with full data: opening cash/float, deposits/withdrawals, expected closing, counted closing, variances. This updates Reports and the selected day's reconciliation; other days remain unchanged.
                </div>
            </div>
        </div>
    @endif

    <div class="panel" style="max-width:980px; border-left:3px solid var(--acacia-600);">
        <div class="panel-head">
            <h3>All Opening Data for {{ $selectedDate }}</h3>
            <div style="display:flex; gap:8px;">
                <a href="{{ route('float.opening.edit', ['date' => $selectedDate]) }}" class="btn btn-ghost btn-sm">Edit opening</a>
                <a href="{{ route('daily-opening.index') }}" class="btn btn-ghost btn-sm">All openings</a>
            </div>
        </div>
        <div class="panel-body">
            @if($dayOpening)
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Date</div><div class="dv">{{ $dayOpening->opening_date->format('Y-m-d') }} · {{ $dayOpening->opening_date->format('l') }}</div></div>
                    <div class="detail-item"><div class="dk">Status</div><div class="dv"><span class="tag {{ $dayOpening->is_closed ? 'tag-grey' : 'tag-green' }}">{{ $dayOpening->is_closed ? 'Closed' : 'Open' }}</span> {{ $dayOpening->closed_at ? 'at '.$dayOpening->closed_at->format('H:i') : '' }}</div></div>
                    <div class="detail-item"><div class="dk">Cash Opening</div><div class="dv">@money($dayOpening->cash_opening)</div></div>
                    <div class="detail-item"><div class="dk">Total Float Opening</div><div class="dv">@money($dayOpening->totalFloatOpening())</div></div>
                    <div class="detail-item"><div class="dk">Cash Closing</div><div class="dv">{{ $dayOpening->cash_closing !== null ? money($dayOpening->cash_closing) : '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Total Float Closing</div><div class="dv">{{ $dayOpening->float_closings ? money(array_sum($dayOpening->float_closings)) : '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Transactions (stored)</div><div class="dv">{{ $dayOpening->total_transactions }} txs · Vol @money($dayOpening->total_volume) · Comm @money($dayOpening->total_commission)</div></div>
                    <div class="detail-item"><div class="dk">By</div><div class="dv">{{ $dayOpening->user?->name ?? '—' }}</div></div>
                </div>
                <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
                    @foreach($run['networks'] as $row)
                        <span class="tag" style="background:var(--white); border:1px solid var(--line);"><span class="net-dot" style="background:{{ $row['color'] }};"></span> {{ $row['name'] }}: @money($row['opening'])</span>
                    @endforeach
                </div>
                @if($dayOpening->notes)<div style="margin-top:10px; font-size:13px;"><strong>Notes:</strong> {{ $dayOpening->notes }}</div>@endif
                <div style="margin-top:10px;"><a href="{{ route('daily-opening.show', $dayOpening) }}" class="btn btn-ghost btn-sm">View opening details</a></div>
            @else
                <p style="color:var(--ink-soft);">No Daily Opening for <strong>{{ $selectedDate }}</strong> — <a href="{{ route('float.opening.edit', ['date' => $selectedDate]) }}">Create opening</a> to set cash & float. Reconciliation will use previous closing cash + live balances as fallback.</p>
                <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                    @foreach($run['networks'] as $row)
                        <span class="tag" style="background:var(--white); border:1px solid var(--line);"><span class="net-dot" style="background:{{ $row['color'] }};"></span> {{ $row['name'] }}: @money($row['opening']) <span style="color:var(--ink-soft);">(live)</span></span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="panel" style="max-width:980px;">
        <div class="panel-head">
            <h3>Transactions Done for {{ $selectedDate }} ({{ $dayTransactions->count() }} completed, {{ $dayFloatTransactions->count() }} float)</h3>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="{{ route('transactions.create') }}?date={{ $selectedDate }}" class="btn btn-ghost btn-sm">+ Add transaction for this date (admin)</a>
                <a href="{{ route('transactions.index', ['date' => $selectedDate]) }}" class="btn btn-ghost btn-sm">View all</a>
                <a href="{{ route('float.create', ['date' => $selectedDate]) }}" class="btn btn-ghost btn-sm">+ Add float for this date</a>
            </div>
        </div>
        <div class="panel-body" style="padding:0;">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Network</th>
                            <th>Amount</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dayTransactions as $t)
                            <tr>
                                <td class="cell-sub">{{ $t->created_at->format('H:i:s') }}</td>
                                <td><div class="cell-title">{{ $t->reference }}</div><div class="cell-sub">{{ $t->provider_reference ?? '' }}</div></td>
                                <td>{{ txn_type_label($t->type) }}</td>
                                <td><span class="net-dot" style="background:{{ $t->network?->color }};"></span> {{ $t->network?->name }}</td>
                                <td class="cell-title">@money($t->amount)</td>
                                <td>{{ $t->customer_name ?? '—' }}<div class="cell-sub">{{ $t->customer_phone }}</div></td>
                                <td><span class="tag {{ status_badge($t->status) }}">{{ $t->status }}</span></td>
                                <td><a href="{{ route('transactions.receipt', $t) }}" class="btn btn-ghost btn-sm">View</a> @if(is_admin()) <a href="{{ route('transactions.edit', $t) }}" class="btn btn-ghost btn-sm">Edit</a> @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty-state">No transactions for {{ $selectedDate }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($dayTransactions->isNotEmpty())
                <div style="padding:12px 16px; background:var(--sand-50); border-top:1px solid var(--line); display:flex; gap:12px; flex-wrap:wrap; font-size:13px;">
                    <span><strong>Total for {{ $selectedDate }}:</strong> {{ $dayTransactions->count() }} txs · Vol @money($dayTransactions->sum('amount')) · Comm @money($dayTransactions->sum('commission')) · Fee @money($dayTransactions->sum('fee'))</span>
                    <span style="color:var(--ink-soft);">Cash in (deposits): @money($dayTransactions->whereIn('type', ['deposit','float_deposit'])->sum('amount')) · Cash out (withdrawals): @money($dayTransactions->where('type','withdrawal')->sum('amount'))</span>
                </div>
            @endif
            @if($dayFloatTransactions->isNotEmpty())
                <div style="padding:12px 16px; border-top:1px solid var(--line);">
                    <strong>Float movements for {{ $selectedDate }} ({{ $dayFloatTransactions->count() }}):</strong>
                    <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                        @foreach($dayFloatTransactions as $ft)
                            <span class="tag {{ $ft->type === 'float_topup' || $ft->type === 'cash_in' ? 'tag-green' : 'tag-terracotta' }}">{{ $ft->network?->name }}: {{ str_replace('_',' ',$ft->type) }} @money($ft->amount) at {{ $ft->created_at->format('H:i') }} {{ $ft->notes ? '· '.$ft->notes : '' }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <form action="{{ route('reconciliation.store') }}" method="POST" data-recon-form>
        @csrf

        <div class="panel" style="max-width:980px;">
            <div class="panel-head">
                <h3>Cash reconciliation</h3>
                <span class="link">Opening cash + customer deposits − customer withdrawals = closing cash</span>
            </div>
            <div class="panel-body">
                <div class="form-row">
                    <div class="field">
                        <label>Reconciliation date</label>
                        <input type="date" name="reconciliation_date" value="{{ old('reconciliation_date', $run['date']) }}" required>
                    </div>
                    <div class="field">
                        <label>Counted closing cash in till (TZS)</label>
                        <input type="number" name="counted_cash" id="countedCash" min="0" step="0.01" value="{{ old('counted_cash', $run['expectedCash']) }}" class="cash-counted" placeholder="0.00" required>
                    </div>
                </div>

                <div class="balance-strip" style="margin-bottom:0;">
                    <div class="balance-box" style="--stat-tint:var(--sand-100);">
                        <div class="bb-label">Opening cash</div>
                        <div class="bb-amount">@money($run['openingCash'])</div>
                        <div class="bb-sub">Cash on hand at the start of the day.</div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--acacia-100);">
                        <div class="bb-label">+ Customer deposits (all networks)</div>
                        <div class="bb-amount" data-goes-in-amount>+@money($run['cashDeposits'])</div>
                        <div class="bb-sub">Money received into the till.</div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--terracotta-100);">
                        <div class="bb-label">− Customer withdrawals (all networks)</div>
                        <div class="bb-amount">−@money($run['cashWithdrawals'])</div>
                        <div class="bb-sub">Cash paid out of the till.</div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--gold-100);--stat-fg:#8a6418;">
                        <div class="bb-label">= Expected closing cash</div>
                        <div class="bb-amount" id="expectedCashDisplay">@money($run['expectedCash'])</div>
                        <div class="bb-sub">Chargeable expected in the till.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel" style="max-width:980px;">
            <div class="panel-head">
                <h3>Float reconciliation per network</h3>
                <span class="link">Opening + withdrawals + float top-ups + bank in − deposits = closing — all networks shown, top-ups added</span>
            </div>
            <div class="panel-body">
                <div class="table-card">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Network</th>
                                    <th>Opening</th>
                                    <th>+ W/drawals</th>
                                    <th>− Deposits</th>
                                    <th>+ Top-ups</th>
                                    <th>+ Bank in</th>
                                    <th>= Expected</th>
                                    <th>Counted</th>
                                    <th>Variance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($run['networks'] as $row)
                                    <tr data-net-row data-net-id="{{ $row['id'] }}">
                                        <td>
                                            <span class="cell-title">
                                                <span class="net-dot" style="background:{{ $row['color'] }};margin-right:7px;vertical-align:middle;"></span>
                                                {{ $row['name'] }}
                                            </span>
                                        </td>
                                        <td data-run-opening="@money($row['opening'])">@money($row['opening'])</td>
                                        <td>+@money($row['withdrawals'])</td>
                                        <td>−@money($row['deposits'])</td>
                                        <td style="color:var(--acacia-600);">+@money($row['float_topups'] ?? 0)</td>
                                        <td style="color:var(--acacia-600);">+@money($row['bank_ins'] ?? 0)</td>
                                        <td data-run-expected="@money($row['expected'])" style="font-weight:700;">@money($row['expected'])</td>
                                        <td>
                                            <input type="number" name="counted_floats[{{ $row['id'] }}]" min="0" step="0.01"
                                                value="{{ old('counted_floats.'.$row['id'], $row['expected']) }}"
                                                class="float-counted" data-network="{{ $row['id'] }}" placeholder="0.00"
                                                style="max-width:160px;">
                                        </td>
                                        <td data-net-var class="cell-sub" style="white-space:nowrap;color:var(--coffee-700);">TZS 0</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="empty-state"><h4>No active networks</h4><p>Enable at least one network to reconcile its float.</p></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel" style="max-width:980px;">
            <div class="panel-head">
                <h3>Tie-out check</h3>
                <span class="link">Opening cash on hand + opening float must equal counted closing cash in hand + counted closing float</span>
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
                        <div class="bb-amount" id="openingTotalDisplay">@money($run['openingCash'] + $run['openingFloat'])</div>
                    </div>
                    <div class="balance-box">
                        <div class="bb-label">Counted closing cash in hand</div>
                        <div class="bb-amount" id="countedCashTotal">@money($run['expectedCash'])</div>
                    </div>
                    <div class="balance-box">
                        <div class="bb-label">+ Counted closing float</div>
                        <div class="bb-amount" id="countedFloatTotal">@money($run['expectedFloat'])</div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--acacia-100);">
                        <div class="bb-label">Tie-out (must be 0)</div>
                        <div class="bb-amount" id="tieOutDisplay">TZS 0</div>
                        <div class="bb-sub">Opening total minus counted closing total.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel" style="max-width:980px;">
            <div class="panel-head">
                <h3>Results</h3>
                <span class="link">{{ $agent->name }}</span>
            </div>
            <div class="panel-body">
                <div class="balance-strip" style="margin-bottom:0;">
                    <div class="balance-box">
                        <div class="bb-label">Cash variance</div>
                        <div class="bb-amount" id="cashVarDisplay">TZS 0</div>
                        <div class="bb-sub">Counted cash minus expected closing cash.</div>
                    </div>
                    <div class="balance-box">
                        <div class="bb-label">Float variance</div>
                        <div class="bb-amount" id="floatVarDisplay">TZS 0</div>
                        <div class="bb-sub">Counted closing float minus expected closing float.</div>
                    </div>
                    <div class="balance-box" style="--stat-tint:var(--sand-100);">
                        <div class="bb-label">Overall result</div>
                        <div class="bb-amount" id="resultDisplay">Perfect match</div>
                        <div class="bb-sub">Reconciled when all variances and the tie-out are zero.</div>
                    </div>
                </div>

                <div class="field" style="margin-top:16px;">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="2" maxlength="255" placeholder="Shift handover notes, variance explanations, etc.">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M20 6 9 17l-5-5"></path></svg>
                    Save reconciliation
                </button>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        (function () {
            const run = {!! json_encode($run) !!};

            const cashInput = document.getElementById('countedCash');
            const floatInputs = document.querySelectorAll('.float-counted');

            function fmt(n) {
                const rounded = Math.round(Math.abs(n) * 100) / 100;
                return 'TZS ' + Number(rounded).toLocaleString('en-US', { maximumFractionDigits: 2 });
            }

            function sign(n) {
                return n > 0 ? '+' : (n < 0 ? '-' : '');
            }

            function setDisplay(el, n) {
                el.textContent = sign(n) + fmt(n);
                el.style.color = Math.abs(n) < 0.005 ? 'var(--coffee-900)' : (n < 0 ? 'var(--danger)' : '#8a6418');
            }

            function recalc() {
                const expectedCash = run.expectedCash;
                const countedCash = parseFloat(cashInput.value || 0);
                const cashVar = countedCash - expectedCash;
                setDisplay(document.getElementById('cashVarDisplay'), cashVar);

                let countedFloat = 0;
                floatInputs.forEach(input => {
                    const row = input.closest('[data-net-row]');
                    const expected = row.querySelector('[data-run-expected]').textContent.replace(/[^\d.-]/g, '');
                    const varCell = row.querySelector('[data-net-var]');
                    const counted = parseFloat(input.value || 0);
                    const variance = counted - parseFloat(expected);
                    const sign = variance > 0 ? '+' : (variance < 0 ? '-' : '');
                    varCell.textContent = sign + fmt(variance);
                    varCell.style.color = Math.abs(variance) < 0.005 ? 'var(--coffee-700)' : (variance < 0 ? 'var(--danger)' : '#8a6418');
                    countedFloat += counted;
                });

                const expectedFloat = run.expectedFloat;
                const floatVar = countedFloat - expectedFloat;
                setDisplay(document.getElementById('floatVarDisplay'), floatVar);

                const openingTotal = run.openingCash + run.openingFloat;
                const countedTotal = countedCash + countedFloat;
                const tieOut = openingTotal - countedTotal;
                const tieOutEl = document.getElementById('tieOutDisplay');
                tieOutEl.textContent = sign(tieOut) + fmt(tieOut);
                tieOutEl.style.color = Math.abs(tieOut) < 0.005 ? 'var(--acacia-600)' : 'var(--danger)';

                document.getElementById('countedCashTotal').textContent = fmt(countedCash);
                document.getElementById('countedFloatTotal').textContent = fmt(countedFloat);

                const result = document.getElementById('resultDisplay');
                if (Math.abs(cashVar) < 0.005 && Math.abs(floatVar) < 0.005 && Math.abs(tieOut) < 0.005) {
                    result.textContent = 'Perfect match';
                    result.style.color = 'var(--acacia-600)';
                } else {
                    result.textContent = 'Variance found';
                    result.style.color = '#8a6418';
                }
            }

            cashInput.addEventListener('input', recalc);
            floatInputs.forEach(i => i.addEventListener('input', recalc));
            recalc();
        })();

        document.querySelectorAll('[data-recon-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => window.location.href = '{{ route('reconciliation.index') }}', 700) });
            });
        });
    </script>
@endsection