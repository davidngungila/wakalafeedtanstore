@extends('layouts.app')

@section('title', 'Edit Transaction '.$transaction->reference)

@section('content')
    <div class="view-head">
        <div>
            <h2>Edit Transaction</h2>
            <p class="sub">{{ $transaction->reference }} · {{ txn_type_label($transaction->type) }} · @money($transaction->amount)</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('transactions.receipt', $transaction) }}" class="btn btn-ghost">View receipt</a>
            <a href="{{ route('transactions.index') }}" class="btn btn-ghost">← Back to transactions</a>
        </div>
    </div>

    <div class="panel" style="border-left:3px solid var(--gold-500);">
        <div class="panel-head">
            <h3>Assigned Area — Financial Impact Warning</h3>
            <span class="tag tag-gold">Admin only</span>
        </div>
        <div class="panel-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; font-size:13px;">
                <div class="detail-item"><div class="dk">Cash Point / Agent</div><div class="dv">{{ $transaction->agent?->name ?? '—' }} · {{ $transaction->agent?->code ?? '' }}</div><div class="cell-sub">Area: {{ $transaction->agent?->region ?? '—' }} / {{ $transaction->agent?->district ?? '—' }}</div></div>
                <div class="detail-item"><div class="dk">Current Network</div><div class="dv"><span class="net-dot" style="background:{{ $transaction->network?->color ?? '#999' }};"></span> {{ $transaction->network?->name ?? '—' }}</div></div>
                <div class="detail-item"><div class="dk">Daily Opening</div><div class="dv">{{ $transaction->dailyOpening ? $transaction->dailyOpening->opening_date->format('Y-m-d') . ' · ' . money($transaction->dailyOpening->cash_opening) : '— (no opening linked)' }}</div></div>
                <div class="detail-item"><div class="dk">Status</div><div class="dv"><span class="tag {{ status_badge($transaction->status) }}">{{ ucfirst($transaction->status) }}</span> @if($transaction->status === 'reversed') <span style="font-size:11px;color:var(--ink-soft);">Cannot edit reversed transactions</span> @endif</div></div>
            </div>
            <div style="margin-top:14px; padding:10px 12px; background:var(--gold-100); border:1px solid var(--line); border-radius:8px; font-size:12.5px; line-height:1.6; color:var(--coffee-700);">
                <strong>Editing affects the assigned area:</strong> Changing amount, type, or network will automatically reverse the old financial effects and re-apply the new ones against
                <strong>{{ $transaction->agent?->name ?? 'the assigned cash point' }}</strong> — cash balance, float (NetworkBalance for that network), daily opening totals, and the general ledger (journal entry <code>{{ $transaction->reference }}</code>).
                This keeps Reports, Finance, and Float in sync. Non-financial edits (name/phone/reference/notes) do not touch balances.
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Transaction Details</h3>
            <span class="link">Editing {{ $transaction->reference }}</span>
        </div>
        <div class="panel-body">
            @if($transaction->status === 'reversed')
                <div style="padding:12px; background:var(--danger-100); border-radius:8px; font-size:13px; margin-bottom:16px;">
                    This transaction is <strong>reversed</strong> and cannot be edited. Its balances have already been undone. Delete it instead if you need to remove it entirely.
                </div>
            @endif
            <form method="POST" action="{{ route('transactions.update', $transaction) }}" data-transaction-edit @if($transaction->status === 'reversed') onsubmit="return false;" @endif>
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="field">
                        <label>Network <span style="color:var(--danger);">*</span></label>
                        <select name="network_id" required @if($transaction->status === 'reversed') disabled @endif>
                            <option value="">Select network</option>
                            @foreach($combos['networks'] as $network)
                                <option value="{{ $network->id }}" {{ (string)old('network_id', $transaction->network_id) === (string)$network->id ? 'selected' : '' }}>{{ $network->name }}</option>
                            @endforeach
                        </select>
                        @error('network_id')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Transaction type <span style="color:var(--danger);">*</span></label>
                        <select name="type" required @if($transaction->status === 'reversed') disabled @endif>
                            <option value="deposit" {{ old('type', $transaction->type) === 'deposit' ? 'selected' : '' }}>Customer Deposit</option>
                            <option value="withdrawal" {{ old('type', $transaction->type) === 'withdrawal' ? 'selected' : '' }}>Customer Withdrawal</option>
                            <option value="send_money" {{ old('type', $transaction->type) === 'send_money' ? 'selected' : '' }}>Send Money</option>
                            <option value="bill_payment" {{ old('type', $transaction->type) === 'bill_payment' ? 'selected' : '' }}>Bill Payment</option>
                            <option value="airtime" {{ old('type', $transaction->type) === 'airtime' ? 'selected' : '' }}>Airtime</option>
                            <option value="data" {{ old('type', $transaction->type) === 'data' ? 'selected' : '' }}>Data Bundle</option>
                            <option value="bank_to_wallet" {{ old('type', $transaction->type) === 'bank_to_wallet' ? 'selected' : '' }}>Bank to Wallet</option>
                            <option value="wallet_to_bank" {{ old('type', $transaction->type) === 'wallet_to_bank' ? 'selected' : '' }}>Wallet to Bank</option>
                            <option value="float_deposit" {{ old('type', $transaction->type) === 'float_deposit' ? 'selected' : '' }}>Float Deposit</option>
                            <option value="float_topup" {{ old('type', $transaction->type) === 'float_topup' ? 'selected' : '' }}>Float Top-up</option>
                        </select>
                        @error('type')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Customer name</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $transaction->customer_name) }}" placeholder="e.g. Juma Athumani" @if($transaction->status === 'reversed') disabled @endif>
                        @error('customer_name')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Customer phone <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $transaction->customer_phone) }}" placeholder="07xxxxxxxx" required @if($transaction->status === 'reversed') disabled @endif>
                        @error('customer_phone')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Amount (TZS) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="amount" value="{{ old('amount', $transaction->amount) }}" min="1" step="any" placeholder="e.g. 100000" required @if($transaction->status === 'reversed') disabled @endif>
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Fee and commission will be recalculated automatically.</p>
                        @error('amount')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Provider reference</label>
                        <input type="text" name="provider_reference" value="{{ old('provider_reference', $transaction->provider_reference) }}" placeholder="e.g. Tnx 626... or PP..." @if($transaction->status === 'reversed') disabled @endif>
                        @error('provider_reference')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="field">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="2" maxlength="255" placeholder="Internal notes…" @if($transaction->status === 'reversed') disabled @endif>{{ old('notes', $transaction->notes) }}</textarea>
                    @error('notes')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                <div style="display:flex; gap:10px; margin-top:18px;">
                    @if($transaction->status !== 'reversed')
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Cancel</a>
                        <a href="{{ route('transactions.receipt', $transaction) }}" class="btn btn-ghost">View receipt</a>
                    @else
                        <a href="{{ route('transactions.index') }}" class="btn btn-ghost">← Back</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($transaction->status !== 'reversed')
    <div class="panel" style="margin-top:18px;">
        <div class="panel-head">
            <h3>Danger Zone</h3>
            <span class="tag tag-red">Irreversible</span>
        </div>
        <div class="panel-body">
            <p style="font-size:13px; color:var(--ink-soft); margin-bottom:12px;">Deleting this transaction will reverse its cash, float, daily opening, and journal effects from the assigned area and permanently remove the record.</p>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('confirmDeleteEdit').style.display='block'">Delete transaction</button>
            <div id="confirmDeleteEdit" style="display:none; margin-top:12px; padding:12px; background:var(--danger-100); border-radius:8px;">
                <p style="font-size:13px; margin-bottom:10px;">Are you sure you want to delete <strong>{{ $transaction->reference }}</strong>? This will affect assigned area balances.</p>
                <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" data-transaction-delete>
                    @csrf
                    @method('DELETE')
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-danger btn-sm">Yes, delete permanently</button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('confirmDeleteEdit').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="panel">
        <div class="panel-head">
            <h3>Current Financial Snapshot</h3>
        </div>
        <div class="panel-body">
            <div class="detail-grid">
                <div class="detail-item"><div class="dk">Amount</div><div class="dv">@money($transaction->amount)</div></div>
                <div class="detail-item"><div class="dk">Fee</div><div class="dv">@money($transaction->fee)</div></div>
                <div class="detail-item"><div class="dk">Commission</div><div class="dv">@money($transaction->commission)</div></div>
                <div class="detail-item"><div class="dk">Running Cash</div><div class="dv">{{ $transaction->running_cash_balance !== null ? money($transaction->running_cash_balance) : '—' }}</div></div>
                <div class="detail-item"><div class="dk">Running Float</div><div class="dv">{{ $transaction->running_float_balance !== null ? money($transaction->running_float_balance) : '—' }}</div></div>
                <div class="detail-item"><div class="dk">Operator</div><div class="dv">{{ $transaction->operator?->name ?? '—' }}</div></div>
                <div class="detail-item"><div class="dk">Created</div><div class="dv">{{ $transaction->created_at->format('d M Y H:i:s') }}</div></div>
                <div class="detail-item"><div class="dk">Provider Ref</div><div class="dv">{{ $transaction->provider_reference ?? '—' }}</div></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-transaction-edit]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: (data) => { toast(data.message || 'Updated', 'success'); setTimeout(() => window.location.href = '{{ route('transactions.index') }}', 700); } });
            });
        });
        document.querySelectorAll('[data-transaction-delete]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (!confirm('Delete this transaction and reverse its assigned area balances?')) return;
                submitForm(form, { method: 'DELETE', done: (data) => { toast(data.message || 'Deleted', 'success'); setTimeout(() => window.location.href = '{{ route('transactions.index') }}', 700); } });
            });
        });
    </script>
@endsection
