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
                <div class="detail-item"><div class="dk">Cash Point / Agent</div><div class="dv">{{ $transaction->agent?->name ?? '—' }} · {{ $transaction->agent?->code ?? '' }}</div><div class="cell-sub">Area: {{ $transaction->agent?->region ?? '—' }} / {{ $transaction->agent?->district ?? '—' }} · Level {{ $transaction->agent?->agent_level ?? '—' }}</div></div>
                <div class="detail-item"><div class="dk">Current Network</div><div class="dv"><span class="net-dot" style="background:{{ $transaction->network?->color ?? '#999' }};"></span> {{ $transaction->network?->name ?? '—' }} ({{ $transaction->network?->code ?? '—' }})</div></div>
                <div class="detail-item"><div class="dk">Daily Opening</div><div class="dv">{{ $transaction->dailyOpening ? $transaction->dailyOpening->opening_date->format('Y-m-d') . ' · ' . money($transaction->dailyOpening->cash_opening) : '— (no opening linked)' }}</div><div class="cell-sub">Vol @money($transaction->dailyOpening?->total_volume ?? 0) · Comm @money($transaction->dailyOpening?->total_commission ?? 0) · {{ $transaction->dailyOpening?->total_transactions ?? 0 }} txs</div></div>
                <div class="detail-item"><div class="dk">Status</div><div class="dv"><span class="tag {{ status_badge($transaction->status) }}">{{ ucfirst($transaction->status) }}</span> @if($transaction->status === 'reversed') <span style="font-size:11px;color:{{ is_admin() ? 'var(--acacia-600)' : 'var(--ink-soft)' }};">{{ is_admin() ? 'Reversed — admin can still edit (will re-activate as completed)' : 'Cannot edit reversed transactions' }}</span> @endif</div></div>
            </div>
            <div style="margin-top:14px; padding:10px 12px; background:var(--gold-100); border:1px solid var(--line); border-radius:8px; font-size:12.5px; line-height:1.6; color:var(--coffee-700);">
                <strong>Editing affects the assigned area:</strong> Changing amount, type, or network will automatically reverse the old financial effects and re-apply the new ones against
                <strong>{{ $transaction->agent?->name ?? 'the assigned cash point' }}</strong> — cash balance, float (NetworkBalance for that network), daily opening totals, and the general ledger (journal entry <code>{{ $transaction->reference }}</code>).
                This keeps Reports, Finance, and Float in sync. Non-financial edits (name/phone/reference/notes) do not touch balances. Assigning an SMS will mark that SMS as <code>RECORDED</code> and link it.
            </div>
        </div>
    </div>

    @if($transaction->smsMessages->isNotEmpty())
        <div class="panel">
            <div class="panel-head">
                <h3>Currently Linked SMS ({{ $transaction->smsMessages->count() }})</h3>
                <span class="link">{{ $transaction->provider_reference ?? 'no ref' }}</span>
            </div>
            <div class="panel-body" style="display:flex; flex-direction:column; gap:10px;">
                @foreach($transaction->smsMessages as $sms)
                    <div style="background:var(--sand-100); border:1px solid var(--line); border-radius:8px; padding:12px;">
                        <div style="display:flex; justify-content:space-between; gap:8px; font-size:12.5px; margin-bottom:6px;">
                            <span><strong>{{ $sms->sender }}</strong> · {{ $sms->device?->name ?? '—' }} · {{ $sms->server_received_at?->format('d M Y H:i') }}</span>
                            <span class="tag {{ sms_status_badge($sms->processing_status, $sms->is_duplicate, $sms->processing_error) }}">{{ sms_status_label($sms->processing_status, $sms->is_duplicate, $sms->processing_error) }}</span>
                        </div>
                        <div style="background:var(--white); border:1px solid var(--line); border-radius:6px; padding:8px; font-size:12px; white-space:pre-wrap; word-break:break-word; font-family:ui-monospace,monospace;">{{ $sms->message_body }}</div>
                        <div style="font-size:11px; color:var(--ink-soft); margin-top:6px;">Ref: {{ $sms->transaction_reference ?? '—' }} · Type {{ $sms->transaction_type ?? '—' }} · Amount {{ $sms->amount ? money($sms->amount) : '—' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="panel">
        <div class="panel-head">
            <h3>Transaction Details</h3>
            <span class="link">Editing {{ $transaction->reference }}</span>
        </div>
        <div class="panel-body">
            @if($transaction->status === 'reversed' && !is_admin())
                <div style="padding:12px; background:var(--danger-100); border-radius:8px; font-size:13px; margin-bottom:16px;">
                    This transaction is <strong>reversed</strong> and cannot be edited. Its balances have already been undone. Delete it instead if you need to remove it entirely.
                </div>
            @elseif($transaction->status === 'reversed' && is_admin())
                <div style="padding:12px; background:var(--acacia-100); border-radius:8px; font-size:13px; margin-bottom:16px; border:1px solid var(--acacia-600);">
                    <span style="color:var(--acacia-600); font-weight:700;">Admin:</span> This transaction is <strong>reversed</strong> — you can still edit it. Saving will re-activate it as <code>completed</code>, re-apply new cash/float balances, and re-post the journal.
                </div>
            @endif
            <form method="POST" action="{{ route('transactions.update', $transaction) }}" data-transaction-edit @if($transaction->status === 'reversed' && !is_admin()) onsubmit="return false;" @endif>
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="field">
                        <label>Network <span style="color:var(--danger);">*</span></label>
                        <select name="network_id" id="editNetwork" required @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>
                            <option value="">Select network</option>
                            @foreach($combos['networks'] as $network)
                                <option value="{{ $network->id }}" {{ (string)old('network_id', $transaction->network_id) === (string)$network->id ? 'selected' : '' }}>{{ $network->name }}</option>
                            @endforeach
                        </select>
                        @error('network_id')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Transaction type <span style="color:var(--danger);">*</span></label>
                        <select name="type" id="editType" required @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>
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
                            <option value="cash_to_float" {{ old('type', $transaction->type) === 'cash_to_float' ? 'selected' : '' }}>Cash to Float</option>
                        </select>
                        @error('type')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Customer name</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $transaction->customer_name) }}" placeholder="e.g. Juma Athumani" @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>
                        @error('customer_name')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Customer phone <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $transaction->customer_phone) }}" placeholder="07xxxxxxxx" required @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>
                        @error('customer_phone')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Amount (TZS) <span style="color:var(--danger);">*</span></label>
                        <input type="number" name="amount" id="editAmount" value="{{ old('amount', $transaction->amount) }}" min="1" step="any" placeholder="e.g. 100000" required @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Fee and commission will be recalculated automatically.</p>
                        @error('amount')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Provider reference</label>
                        <input type="text" name="provider_reference" id="editProviderRef" value="{{ old('provider_reference', $transaction->provider_reference) }}" placeholder="e.g. Tnx 626... or PP..." @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">If this matches an SMS, that SMS will be auto-linked as RECORDED.</p>
                        @error('provider_reference')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label>Transaction date & time <span style="color:var(--danger);">*</span></label>
                        <input type="datetime-local" name="transaction_date" id="editDate" value="{{ old('transaction_date', $transaction->created_at->format('Y-m-d').'T'.$transaction->created_at->format('H:i')) }}" required @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">When it actually happened. Changing moves it between Daily Openings, Reconciliation days, Reports & Journal dates. Was: {{ $transaction->created_at->format('d M Y H:i') }}</p>
                        @error('transaction_date')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>Daily Opening (auto)</label>
                        <div style="padding:10px 12px; border:1.5px solid var(--line); border-radius:8px; background:var(--sand-50); font-size:13px;">
                            @if($transaction->dailyOpening)
                                {{ $transaction->dailyOpening->opening_date->format('Y-m-d') }} · Vol @money($transaction->dailyOpening->total_volume)
                            @else
                                <span style="color:var(--ink-soft);">No opening linked — will auto-link to opening for new date if exists</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label>Assign / Reference SMS (optional — reference connect)</label>
                    <select name="sms_id" id="editSms">
                        <option value="">— No change —</option>
                        @foreach($smsMessages as $sms)
                            <option value="{{ $sms->id }}" {{ (string)old('sms_id', $selectedSms) === (string)$sms->id ? 'selected' : '' }}>{{ $sms->sender }} · {{ Str::limit($sms->message_body, 70) }} · {{ $sms->transaction_reference ?? 'no ref' }} · {{ $sms->server_received_at->format('d M H:i') }} · {{ $sms->processing_status }}</option>
                        @endforeach
                    </select>
                    <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">
                        Link an unlinked SMS (Stored/Failed/Parsed) to this transaction. Currently linked: {{ $linkedSmsIds ? implode(', #', $linkedSmsIds) : 'none' }}. Selecting will mark the SMS as <code>RECORDED</code> and set its <code>transaction_id</code>.
                        @if($transaction->provider_reference) Matching by provider reference <code>{{ $transaction->provider_reference }}</code> will also auto-link on save. @endif
                    </p>
                    @error('sms_id')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="2" maxlength="255" placeholder="Internal notes…" @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>{{ old('notes', $transaction->notes) }}</textarea>
                    @error('notes')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                @if(is_admin())
                <div class="field">
                    <label>Status — admin can change</label>
                    <select name="status" id="editStatus" @if($transaction->status === 'reversed' && !is_admin()) disabled @endif>
                        <option value="completed" {{ old('status', $transaction->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ old('status', $transaction->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ old('status', $transaction->status) === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="reversed" {{ old('status', $transaction->status) === 'reversed' ? 'selected' : '' }}>Reversed</option>
                    </select>
                    <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Change status directly — admin only. Reversed will undo balances if pending/completed → reversed; Completed will re-apply if reversed → completed.</p>
                    @error('status')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                @endif

                <div style="display:flex; gap:10px; margin-top:18px;">
                    @if($transaction->status !== 'reversed' || is_admin())
                        <button type="submit" class="btn btn-primary">Save changes @if($transaction->status === 'reversed') (re-activate) @endif</button>
                        <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Cancel</a>
                        <a href="{{ route('transactions.receipt', $transaction) }}" class="btn btn-ghost">View receipt</a>
                    @else
                        <a href="{{ route('transactions.index') }}" class="btn btn-ghost">← Back</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($transaction->status !== 'reversed' || is_admin())
    <div class="panel" id="impactPanel" style="margin-top:18px; border-left:3px solid var(--terracotta-600);">
        <div class="panel-head">
            <h3>Where this edit will affect — Live Preview</h3>
            <span class="tag tag-terracotta">Assigned area: {{ $agent?->name ?? '—' }}</span>
        </div>
        <div class="panel-body">
            <div id="impactPreview" style="font-size:13px; line-height:1.6;"></div>
            <div style="margin-top:12px; padding:10px 12px; background:var(--sand-100); border:1px solid var(--line); border-radius:8px; font-size:12px; color:var(--ink-soft);">
                <strong>Legend:</strong> Cash <span style="color:var(--success);">▲ increasing</span> means agent receives cash (deposit/airtime), <span style="color:var(--danger);">▼ decreasing</span> means agent pays out (withdrawal). Float <span style="color:var(--danger);">▼</span> = float out (given to network), <span style="color:var(--success);">▲</span> = float in. SMS linking does not affect balances, only inbox status.
            </div>
        </div>
    </div>
    @endif

    @if($transaction->status !== 'reversed')
    <div class="panel" style="margin-top:18px; border:1px solid var(--danger);">
        <div class="panel-head">
            <h3>Reverse Transaction</h3>
            <span class="tag tag-red">Affects all areas</span>
        </div>
        <div class="panel-body">
            <p style="font-size:13px; color:var(--ink-soft); margin-bottom:12px;">Reversing will <strong>undo</strong> this transaction's effects: cash, float per network, daily opening, journal (creates <code>RVS-...</code>), and recomputes reconciliation for <code>{{ $transaction->created_at->format('Y-m-d') }}</code>. Reports will exclude it.</p>
            <form data-transaction-reverse action="{{ route('transactions.reverse', $transaction) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="field">
                    <label>Reversal reason (optional)</label>
                    <textarea name="reason" rows="2" maxlength="255" placeholder="e.g. Duplicate, customer cancelled, wrong amount..."></textarea>
                </div>
                <button type="submit" class="btn btn-danger">Mark as Reversed</button>
                <span style="font-size:11px; color:var(--ink-soft); margin-left:8px;">Sets status to <code>reversed</code> — affects all areas</span>
            </form>
        </div>
    </div>
    @endif

    @if($transaction->status !== 'reversed' || is_admin())
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
                <div class="detail-item"><div class="dk">Running Float — {{ $transaction->network?->name ?? 'Network' }} (per network)</div><div class="dv">{{ $transaction->running_network_balance !== null ? money($transaction->running_network_balance) : ($transaction->running_float_balance !== null ? money($transaction->running_float_balance) : '—') }}</div></div>
                <div class="detail-item"><div class="dk">Running Float — Total (all)</div><div class="dv">{{ $transaction->running_float_balance !== null ? money($transaction->running_float_balance) : '—' }}</div></div>
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
                submitForm(form, { method: 'POST', done: (data) => { toast(data.message || 'Updated', 'success'); setTimeout(() => window.location.href = '{{ route('transactions.index') }}', 700); } });
            });
        });
        document.querySelectorAll('[data-transaction-delete]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (!confirm('Delete this transaction and reverse its assigned area balances?')) return;
                submitForm(form, { method: 'POST', done: (data) => { toast(data.message || 'Deleted', 'success'); setTimeout(() => window.location.href = '{{ route('transactions.index') }}', 700); } });
            });
        });
        document.querySelectorAll('[data-transaction-reverse]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (!confirm('Reverse this transaction? This will undo its effects on cash, float per network, daily opening, journal and reconciliation. Reports will exclude it.')) return;
                submitForm(form, { method: 'POST', done: (data) => { toast(data.message || 'Reversed', 'success'); setTimeout(() => location.reload(), 700); } });
            });
        });

        // Live impact preview
        (function(){
            const oldTxn = @json($oldTxnData);
            const networks = @json($networksData);
            const netMap = Object.fromEntries(networks.map(n=>[String(n.id), n]));
            const balances = @json($networkBalances);
            const agentCash = {{ (float) ($agent ? $agent->cash_balance : 0) }};
            const agentName = @json($agent ? $agent->name : 'Agent');
            const opening = @json($openingData);
            function floatDelta(type, amount, commission=0){ amount=Number(amount)||0; commission=Number(commission)||0; if(type==='cash_to_float') return amount-commission; if(['deposit','float_deposit','float_topup'].includes(type)) return -amount; if(['withdrawal','bank_to_wallet'].includes(type)) return amount; return -amount; }
            function cashDelta(type, amount){ amount=Number(amount)||0; if(!['deposit','withdrawal','float_deposit','float_topup','wallet_to_bank','airtime','cash_to_float'].includes(type)) return 0; let dir = ['deposit','float_deposit','float_topup','airtime'].includes(type) ? 1 : -1; return dir*amount; }
            function feeFor(type, amount){ amount=Number(amount)||0; if(type==='withdrawal') return Math.min(5000, Math.max(200, Math.round(amount*0.002*100)/100)); if(type==='bill_payment') return Math.round(amount*0.003*100)/100; if(['bank_to_wallet','wallet_to_bank'].includes(type)) return Math.round(amount*0.001*100)/100; return 0; }
            function money(n){ return 'TZS ' + Number(n).toLocaleString('en-US',{maximumFractionDigits:2}); }
            function arrow(delta){ if(delta>0) return '<span style="color:var(--success);">▲ +' + money(delta) + '</span>'; if(delta<0) return '<span style="color:var(--danger);">▼ ' + money(delta) + '</span>'; return '<span style="color:var(--ink-soft);">—</span>'; }
            function updatePreview(){
                const newAmount = parseFloat(document.getElementById('editAmount')?.value) || 0;
                const newType = document.getElementById('editType')?.value || oldTxn.type;
                const newNetworkId = document.getElementById('editNetwork')?.value || String(oldTxn.network_id);
                const newProviderRef = document.getElementById('editProviderRef')?.value?.trim() || '';
                const newSms = document.getElementById('editSms')?.value || '';
                const newDateRaw = document.getElementById('editDate')?.value || oldTxn.created_at;
                const newDate = newDateRaw ? newDateRaw.slice(0,10) : oldTxn.created_date;
                const oldDate = oldTxn.created_date;
                const isDateChange = newDate !== oldDate;
                const isDateTimeChange = newDateRaw !== oldTxn.created_at;
                const isFinancialChange = newAmount !== oldTxn.amount || newType !== oldTxn.type || String(newNetworkId) !== String(oldTxn.network_id);
                const oldFloat = floatDelta(oldTxn.type, oldTxn.amount);
                const newFloat = floatDelta(newType, newAmount);
                const oldCash = cashDelta(oldTxn.type, oldTxn.amount);
                const newCash = cashDelta(newType, newAmount);
                const netCash = newCash - oldCash;
                const oldFee = Number(oldTxn.fee)||0; const newFee = feeFor(newType, newAmount);
                // For network-specific preview
                const oldNetName = netMap[String(oldTxn.network_id)]?.name || oldTxn.network_name;
                const newNetName = netMap[String(newNetworkId)]?.name || '—';
                const oldBal = Number(balances[String(oldTxn.network_id)] ?? 0);
                const newBal = Number(balances[String(newNetworkId)] ?? 0);
                let floatHtml = '';
                if(String(oldTxn.network_id) === String(newNetworkId)){
                    const curFloat = oldBal;
                    const afterFloat = curFloat - oldFloat + newFloat;
                    floatHtml = `<div><strong>Float (${oldNetName})</strong>: ${money(curFloat)} → ${money(afterFloat)} ${arrow(newFloat - oldFloat)} <span style="color:var(--ink-soft);">(revert ${arrow(-oldFloat)} then apply ${arrow(newFloat)})</span></div>`;
                    if(!isFinancialChange) floatHtml = `<div><strong>Float (${oldNetName})</strong>: ${money(curFloat)} <span style="color:var(--ink-soft);">— no change (same network/type/amount)</span></div>`;
                } else {
                    const curOld = oldBal, afterOld = curOld - oldFloat;
                    const curNew = newBal, afterNew = curNew + newFloat;
                    floatHtml = `<div><strong>Float ${oldNetName} (old)</strong>: ${money(curOld)} → ${money(afterOld)} ${arrow(-oldFloat)} (reverted)</div><div><strong>Float ${newNetName} (new)</strong>: ${money(curNew)} → ${money(afterNew)} ${arrow(newFloat)} (applied)</div>`;
                }
                const curCashAfter = agentCash - oldCash + newCash;
                let cashHtml = `<div><strong>Cash (${agentName})</strong>: ${money(agentCash)} → ${money(curCashAfter)} ${arrow(netCash)} <span style="color:var(--ink-soft);">(was ${arrow(oldCash)} now ${arrow(newCash)})</span></div>`;
                if(!isFinancialChange) cashHtml = `<div><strong>Cash (${agentName})</strong>: ${money(agentCash)} <span style="color:var(--ink-soft);">— no change</span></div>`;
                let dateHtml = `<div><strong>Date</strong>: ${oldTxn.created_human} (${oldDate}) → ${newDateRaw ? new Date(newDateRaw).toLocaleString() + ' (' + newDate + ')' : '—'} ${isDateChange ? '<span style="color:var(--terracotta-600);">📅 day moves</span> <span style="color:var(--ink-soft);">— daily opening, reconciliation day, reports bucket & journal entry_date will shift</span>' : (isDateTimeChange ? '<span style="color:var(--ink-soft);">time shift only</span>' : '<span style="color:var(--ink-soft);">— no date change</span>')}</div>`;
                let openingHtml = '';
                if(isDateChange){
                    if(opening.exists){
                        const volAfterOld = opening.volume - oldTxn.amount;
                        const oldOpeningAfter = `<div><strong>Daily Opening ${opening.date} (old)</strong>: Vol ${money(opening.volume)} → ${money(volAfterOld)} ${arrow(-oldTxn.amount)} · Count ${opening.count} → ${Math.max(0, opening.count - 1)} <span style="color:var(--ink-soft);">(reverted)</span></div>`;
                        const newOpeningInfo = `<div><strong>Daily Opening ${newDate} (new)</strong>: will ${isFinancialChange ? 'apply ' + money(newAmount) : 'move ' + money(oldTxn.amount)} <span style="color:var(--success);">+${money(isFinancialChange?newAmount:oldTxn.amount)}</span> <span style="color:var(--ink-soft);">(if opening exists for that date; else transaction becomes unlinked but still counted via date for reports/reconciliation)</span></div>`;
                        openingHtml = oldOpeningAfter + newOpeningInfo;
                    } else {
                        openingHtml = `<div><strong>Daily Opening</strong>: No opening linked on ${oldDate} → will link to opening for <strong>${newDate}</strong> if exists, else remain unlinked. Reports & reconciliation use <code>whereDate(created_at)</code> so they auto-follow date.</div>`;
                    }
                } else if(opening.exists){
                    const volAfter = opening.volume - (isFinancialChange?oldTxn.amount:0) + (isFinancialChange?newAmount:0);
                    openingHtml = `<div><strong>Daily Opening ${opening.date}</strong>: Vol ${money(opening.volume)} → ${money(volAfter)} ${arrow((isFinancialChange?newAmount:0) - (isFinancialChange?oldTxn.amount:0))} · Count ${opening.count} → ${opening.count + (isFinancialChange?0:0)}</div>`;
                    if(!isFinancialChange) openingHtml = `<div><strong>Daily Opening ${opening.date}</strong>: Vol ${money(opening.volume)} <span style="color:var(--ink-soft);">— no change</span></div>`;
                } else {
                    openingHtml = `<div><strong>Daily Opening</strong>: <span style="color:var(--ink-soft);">No opening linked — will link to today's opening if exists, else no opening change</span></div>`;
                }
                const needsJournal = isFinancialChange || isDateChange;
                let journalHtml = `<div><strong>Journal (GL) ${oldTxn.provider_reference || oldTxn.amount}</strong>: ${needsJournal ? 'will be <span style="color:var(--danger);">deleted & re-posted</span> with ${isFinancialChange ? 'new amount ' + money(newAmount) + ' (fee ' + money(newFee) + ')' : 'same amount'}${isDateChange ? ' and <span style="color:var(--terracotta-600);">entry_date → ' + newDate + '</span>' : ''}` : '<span style="color:var(--ink-soft);">— no change</span>'}</div>`;
                let reconHtml = `<div><strong>Reconciliation</strong>: ${isDateChange ? `will recompute for <code>${oldDate}</code> and <code>${newDate}</code> (expected cash/float, variances, status)` : (isFinancialChange ? `will recompute for <code>${oldDate}</code> (expected cash/float)` : '<span style="color:var(--ink-soft);">— no recompute</span>')} <span style="color:var(--ink-soft);">— reports use live whereDate queries so they auto-reflect</span></div>`;
                let smsHtml = '';
                const sel = document.getElementById('editSms');
                if(sel && sel.value){
                    const txt = sel.options[sel.selectedIndex]?.text?.slice(0,80) || '';
                    smsHtml = `<div><strong>SMS link</strong>: will link <code>#${sel.value}</code> → <span style="color:var(--success);">RECORDED</span> <span style="color:var(--ink-soft);">(${txt}…)</span></div>`;
                } else if(newProviderRef && newProviderRef !== (oldTxn.provider_reference||'')){
                    smsHtml = `<div><strong>SMS link</strong>: provider ref changed to <code>${newProviderRef}</code> → any unlinked SMS with same ref will be auto-linked as RECORDED</div>`;
                } else {
                    smsHtml = `<div><strong>SMS link</strong>: <span style="color:var(--ink-soft);">— no SMS change</span></div>`;
                }
                const container = document.getElementById('impactPreview');
                if(container){
                    const noMoneyChange = !isFinancialChange && !isDateChange;
                    container.innerHTML = `
                        <div style="display:grid; gap:10px;">
                            ${dateHtml}
                            ${cashHtml}
                            ${floatHtml}
                            <div><strong>Fees</strong>: ${money(oldFee)} → ${money(newFee)} ${arrow(newFee - oldFee)} <span style="color:var(--ink-soft);"> (recalculated)</span></div>
                            ${openingHtml}
                            ${journalHtml}
                            ${reconHtml}
                            ${smsHtml}
                        </div>
                        ${noMoneyChange ? '<div style="margin-top:10px; padding:8px; background:var(--sand-50); border-radius:6px; font-size:12px; color:var(--ink-soft);">Only non-financial fields changed (name/phone/notes) — balances, opening and GL will <strong>not</strong> be touched. Only SMS linking / date may have moved reconciliation buckets.</div>' : ''}
                    `;
                }
            }
            ['editAmount','editType','editNetwork','editProviderRef','editSms','editDate'].forEach(id=>{
                const el=document.getElementById(id);
                if(el) el.addEventListener('input', updatePreview);
                if(el) el.addEventListener('change', updatePreview);
            });
            updatePreview();
        })();
    </script>
@endsection
