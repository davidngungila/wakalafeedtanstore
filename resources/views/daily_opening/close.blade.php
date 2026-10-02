@extends('layouts.app')

@section('title', 'Close Day')

@php
    $floatRows = $networks->map(function ($network) use ($currentBalances, $dailyOpening): array {
        $system = (float) ($currentBalances[$network->id]->balance ?? 0);

        return [
            'id' => $network->id,
            'name' => $network->name,
            'color' => $network->color,
            'opening' => $dailyOpening->getFloatOpening($network->id),
            'system' => $system,
        ];
    });
@endphp

@section('content')
    <div class="view-head">
        <div>
            <h2>Close Day</h2>
            <p class="sub">
                {{ $dailyOpening->opening_date->format('l, j F Y') }} ·
                <span class="tag tag-gold">{{ \App\Support\Shift::label($dailyOpening->shift ?? 'full') }}</span>
                · Count the cash and float on hand, then confirm.
            </p>
        </div>
        <a class="btn btn-ghost" href="{{ route('daily-opening.show', $dailyOpening) }}" style="text-decoration:none;">Back to day</a>
    </div>

    @if ($reconciliationForDay === null)
        <div class="panel" style="border-left:4px solid var(--gold-500);margin-bottom:18px;">
            <div class="panel-body" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <span class="tag tag-gold">Not reconciled</span>
                <p style="margin:0;font-size:13.5px;color:var(--ink-soft);flex:1;min-width:220px;">
                    This day has no float reconciliation yet. You can still close it, but reconciling first keeps the float audit trail complete.
                </p>
                <a class="btn btn-ghost btn-sm" href="{{ route('reconciliation.create', ['date' => $dailyOpening->opening_date->toDateString()]) }}" style="text-decoration:none;">Reconcile now</a>
            </div>
        </div>
    @else
        <div class="panel" style="border-left:4px solid var(--{{ $reconciliationForDay->status === 'reconciled' ? 'acacia-600' : 'danger' }});margin-bottom:18px;">
            <div class="panel-body" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <span class="tag {{ $reconciliationForDay->status === 'reconciled' ? 'tag-green' : 'tag-red' }}">{{ ucfirst($reconciliationForDay->status) }}</span>
                <p style="margin:0;font-size:13.5px;color:var(--ink-soft);flex:1;min-width:220px;">
                    Float reconciliation is {{ $reconciliationForDay->status === 'reconciled' ? 'matched' : 'reporting a variance' }} for this date.
                </p>
                <a class="btn btn-ghost btn-sm" href="{{ route('reconciliation.show', $reconciliationForDay) }}" style="text-decoration:none;">View reconciliation</a>
            </div>
        </div>
    @endif

    @if ($hasUnapprovedReconciliation)
        <div class="panel" style="border-left:4px solid var(--danger);margin-bottom:18px;">
            <div class="panel-body">
                <p style="margin:0;font-size:13.5px;color:var(--ink-soft);line-height:1.7;">
                    A reconciliation from an earlier day is still awaiting supervisor approval. Closing today does not block it,
                    but the next shift cannot be opened until that approval lands.
                </p>
            </div>
        </div>
    @endif

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>Cash</h3>
                <span class="link">Expected @money($expectedClosingCash)</span>
            </div>
            <div class="panel-body">
                <div class="detail-grid" style="margin-bottom:18px;">
                    <div class="detail-item"><div class="dk">Opening cash</div><div class="dv">@money($dailyOpening->cash_opening)</div></div>
                    <div class="detail-item"><div class="dk">Deposits in</div><div class="dv">+@money($todayDeposits)</div></div>
                    <div class="detail-item"><div class="dk">Withdrawals out</div><div class="dv">−@money($todayWithdrawals)</div></div>
                    <div class="detail-item"><div class="dk">Commission</div><div class="dv">+@money($todayCommission)</div></div>
                    <div class="detail-item"><div class="dk">Expected closing</div><div class="dv">@money($expectedClosingCash)</div></div>
                    <div class="detail-item"><div class="dk">System cash</div><div class="dv">@money($cashCurrent)</div></div>
                </div>

                <div class="field" style="margin-bottom:10px;">
                    <label for="cashClosing">Counted closing cash (TZS)</label>
                    <input type="number" name="cash_closing" id="cashClosing" step="any" min="0" required
                        value="{{ old('cash_closing', $cashCurrent) }}" data-expected="{{ $expectedClosingCash }}" placeholder="0.00">
                </div>
                <p style="margin:0;font-size:13px;font-weight:700;" id="cashVarianceDisplay">Count the till to see the variance.</p>
                <p style="margin:6px 0 0;font-size:12px;color:var(--ink-soft);">
                    Day totals — {{ $todayCount }} {{ Str::plural('transaction', $todayCount) }},
                    @money($todayVolume) volume, @money($todayFees) fees.
                </p>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Summary</h3>
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Float opening</div><div class="dv">@money($dailyOpening->totalFloatOpening())</div></div>
                    <div class="detail-item"><div class="dk">System float</div><div class="dv">@money($floatCurrent)</div></div>
                    <div class="detail-item"><div class="dk">Commission earned</div><div class="dv">@money($todayCommission)</div></div>
                    <div class="detail-item"><div class="dk">Closed by</div><div class="dv">{{ auth()->user()->name }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('daily-opening.close', $dailyOpening) }}" id="closeDayForm">
        @csrf
        <input type="hidden" name="_method" value="PUT">

        <div class="panel">
            <div class="panel-head">
                <h3>Counted float per network</h3>
                <span class="link" id="floatVarianceDisplay">Total variance TZS 0</span>
            </div>
            <div class="table-card">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Network</th>
                                <th>Opening</th>
                                <th>System</th>
                                <th>Counted</th>
                                <th>Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($floatRows as $row)
                                <tr data-float-row data-system="{{ $row['system'] }}">
                                    <td>
                                        <span class="cell-title">
                                            <span class="net-dot" style="background:{{ $row['color'] }};margin-right:7px;vertical-align:middle;"></span>
                                            {{ $row['name'] }}
                                        </span>
                                    </td>
                                    <td>@money($row['opening'])</td>
                                    <td>@money($row['system'])</td>
                                    <td>
                                        <input type="number" name="float_closings[{{ $row['id'] }}]" step="any" min="0" required
                                            value="{{ old('float_closings.'.$row['id'], $row['system']) }}"
                                            data-float-counted style="max-width:150px;">
                                    </td>
                                    <td data-float-variance class="cell-sub" style="white-space:nowrap;color:var(--coffee-700);">TZS 0</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="font-weight:700;background:var(--sand-50);border-top:2px solid var(--line);">
                                <td>Total</td>
                                <td>@money($dailyOpening->totalFloatOpening())</td>
                                <td>@money($floatCurrent)</td>
                                <td id="totalCountedFloat">@money($floatCurrent)</td>
                                <td id="totalFloatVariance" style="color:var(--coffee-700);">TZS 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="panel-body">
                <div class="field">
                    <label for="closeNotes">Notes</label>
                    <textarea id="closeNotes" name="notes" rows="3" maxlength="500"
                        placeholder="Explain any cash or float variance. Required when there is a cash variance.">{{ old('notes', $dailyOpening->notes) }}</textarea>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <button type="submit" class="btn btn-danger">Close Day</button>
                    <span style="font-size:12.5px;color:var(--ink-soft);">
                        Closing locks the counted balances and sets them as the opening position for the next shift.
                    </span>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        (function () {
            const fmt = (n) => 'TZS ' + Number(Math.round(Math.abs(n) * 100) / 100).toLocaleString('en-US', { maximumFractionDigits: 2 });
            const signed = (n) => (n > 0 ? '+' : (n < 0 ? '-' : '')) + fmt(n);
            const paint = (el, value, neutralColor) => {
                el.textContent = signed(value);
                el.style.color = Math.abs(value) < 0.005 ? neutralColor : (value < 0 ? 'var(--danger)' : '#8a6418');
            };

            const cashInput = document.getElementById('cashClosing');
            const cashVariance = document.getElementById('cashVarianceDisplay');
            const expectedCash = parseFloat(cashInput.dataset.expected || 0);

            const recalcCash = () => {
                const variance = parseFloat(cashInput.value || 0) - expectedCash;
                paint(cashVariance, variance, 'var(--coffee-900)');
                cashInput.style.borderColor = Math.abs(variance) < 0.005 ? 'var(--acacia-600)' : 'var(--gold-500)';
            };

            const rows = Array.from(document.querySelectorAll('[data-float-row]'));
            const totalCounted = document.getElementById('totalCountedFloat');
            const totalVariance = document.getElementById('totalFloatVariance');
            const floatVarianceDisplay = document.getElementById('floatVarianceDisplay');

            const recalcFloat = () => {
                let counted = 0;
                let variance = 0;
                rows.forEach(row => {
                    const input = row.querySelector('[data-float-counted]');
                    const value = parseFloat(input.value || 0);
                    counted += value;
                    variance += value - parseFloat(row.dataset.system || 0);
                    paint(row.querySelector('[data-float-variance]'), value - parseFloat(row.dataset.system || 0), 'var(--coffee-700)');
                });
                totalCounted.textContent = fmt(counted);
                paint(totalVariance, variance, 'var(--coffee-700)');
                floatVarianceDisplay.textContent = 'Total variance ' + signed(variance);
                floatVarianceDisplay.style.color = Math.abs(variance) < 0.005 ? 'var(--acacia-600)' : 'var(--danger)';
            };

            cashInput.addEventListener('input', recalcCash);
            rows.forEach(row => row.querySelector('[data-float-counted]').addEventListener('input', recalcFloat));
            recalcCash();
            recalcFloat();

            document.getElementById('closeDayForm').addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(e.target, {
                    method: 'PUT',
                    done: (data) => setTimeout(() => { window.location.href = data.redirect; }, 500),
                });
            });
        })();
    </script>
@endsection