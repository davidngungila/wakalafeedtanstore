@extends('layouts.app')

@section('title', 'Recorrect Reconciliation ' . $reconciliation->code)

@section('content')
    <div class="view-head">
        <div>
            <h2>Recorrect Reconciliation {{ $reconciliation->code }}</h2>
            <p class="sub">{{ $reconciliation->reconciliation_date->format('l, d M Y') }} — {{ $reconciliation->agent?->code }} — <span class="tag {{ $reconciliation->status === 'reconciled' ? 'tag-green' : 'tag-gold' }}">{{ ucfirst($reconciliation->status) }}</span> — edit until everything is correct</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('reconciliation.show', $reconciliation) }}" class="btn btn-ghost">← Back to {{ $reconciliation->code }}</a>
        </div>
    </div>

    <form method="POST" action="{{ route('reconciliation.update', $reconciliation) }}" data-recon-edit>
        @csrf
        @method('PUT')
        <div class="panel" style="max-width:980px;">
            <div class="panel-head">
                <h3>Recorrect — Cash & Float Counted</h3>
                <span class="link">Change counted to match expected until variance 0</span>
            </div>
            <div class="panel-body">
                <div class="balance-strip" style="margin-bottom:16px; gap:14px;">
                    <div class="balance-box">
                        <div class="bb-label">Expected cash</div>
                        <div class="bb-amount">@money($run['expectedCash'])</div>
                    </div>
                    <div class="balance-box">
                        <div class="bb-label">Counted cash *</div>
                        <input type="number" name="counted_cash" value="{{ old('counted_cash', $run['countedCash']) }}" min="0" step="0.01" required style="width:100%; padding:8px; border:1.5px solid var(--line); border-radius:8px; font-weight:700;">
                    </div>
                    <div class="balance-box">
                        <div class="bb-label">Cash variance</div>
                        <div class="bb-amount" id="editCashVar">{{ ($run['countedCash'] - $run['expectedCash']) > 0 ? '+' : '' }}@money($run['countedCash'] - $run['expectedCash'])</div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Network</th>
                                    <th>Opening</th>
                                    <th>+ W/drawals</th>
                                    <th>- Deposits</th>
                                    <th>+ Top-ups</th>
                                    <th>= Expected</th>
                                    <th>Counted *</th>
                                    <th>Variance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($run['networks'] as $row)
                                <tr>
                                    <td><span class="net-dot" style="background:{{ $networks->firstWhere('name', $row['network'])?->color ?? '#999' }};"></span> {{ $row['network'] }}</td>
                                    <td>@money($row['opening'])</td>
                                    <td>+@money($row['withdrawals'])</td>
                                    <td>-@money($row['deposits'])</td>
                                    <td>+@money($row['float_topups'] ?? 0)</td>
                                    <td><strong>@money($row['expected'])</strong></td>
                                    <td><input type="number" name="counted_floats[{{ $row['network'] }}]" value="{{ old('counted_floats.'.$row['network'], $row['counted']) }}" min="0" step="0.01" required style="max-width:140px; padding:6px; border:1.5px solid var(--line); border-radius:6px;"></td>
                                    <td>{{ $row['counted'] - $row['expected'] > 0 ? '+' : '' }}@money($row['counted'] - $row['expected'])</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="font-weight:700; background:var(--sand-50);">
                                    <td>Total</td>
                                    <td>@money($run['openingCash'] + $run['openingFloat'])</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>@money($run['expectedCash'] + $run['expectedFloat'])</td>
                                    <td>@money($run['countedCash'] + $run['countedFloat'])</td>
                                    <td>@money($run['tieOut'] * -1)</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="field" style="margin-top:16px;">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" maxlength="500">{{ old('notes', $reconciliation->notes) }}</textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M20 6 9 17l-5-5"></path></svg>
                    Save recorrection
                </button>
                <a href="{{ route('reconciliation.show', $reconciliation) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-recon-edit]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => window.location.href = '{{ route('reconciliation.show', $reconciliation) }}', 600) });
            });
        });
    </script>
@endsection
