@extends('layouts.app')

@section('title', 'SMS #'.$smsMessage->id)

@section('content')
    <div class="view-head">
        <div>
            <h2>SMS #{{ $smsMessage->id }} · {{ $smsMessage->sender }}</h2>
            <p class="sub">{{ $smsMessage->server_received_at->format('d M Y H:i:s') }} · {{ $smsMessage->device?->name ?? '—' }} @if($smsMessage->device) ({{ $smsMessage->device->device_code }}) @endif</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('sms.index', ['device' => $smsMessage->device_id]) }}" class="btn btn-ghost">← Back to SMS (device {{ $smsMessage->device_id }})</a>
            <a href="{{ route('sms.index') }}" class="btn btn-ghost">All SMS</a>
            @if($smsMessage->transaction)
                <a href="{{ route('transactions.receipt', $smsMessage->transaction) }}" class="btn btn-primary">View transaction {{ $smsMessage->transaction->reference }}</a>
            @endif
        </div>
    </div>

    <div class="panel-grid">
        <div class="panel">
            <div class="panel-head">
                <h3>SMS Details</h3>
                <span class="tag {{ sms_status_badge($smsMessage->processing_status, $smsMessage->is_duplicate, $smsMessage->processing_error) }}">{{ sms_status_label($smsMessage->processing_status, $smsMessage->is_duplicate, $smsMessage->processing_error) }}</span>
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Sender</div><div class="dv">{{ $smsMessage->sender }}</div></div>
                    <div class="detail-item"><div class="dk">Provider</div><div class="dv">{{ $smsMessage->provider ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Device</div><div class="dv"><a href="{{ route('devices.show', $smsMessage->device) }}" style="color:var(--terracotta-600);">{{ $smsMessage->device?->name ?? '—' }}</a> · {{ $smsMessage->device?->device_code ?? '' }}</div></div>
                    <div class="detail-item"><div class="dk">Line / Slot</div><div class="dv">{{ $smsMessage->deviceLine?->displayName() ?? '—' }} · Slot {{ $smsMessage->sim_slot ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Network</div><div class="dv">@if($smsMessage->network)<span class="net-dot" style="background:{{ $smsMessage->network->color }};"></span> {{ $smsMessage->network->name }}@else — @endif</div></div>
                    <div class="detail-item"><div class="dk">Received at</div><div class="dv">{{ $smsMessage->server_received_at->format('d M Y H:i:s') }} (device: {{ $smsMessage->received_at?->format('d M Y H:i:s') ?? '—' }})</div></div>
                    <div class="detail-item"><div class="dk">Reference</div><div class="dv">{{ $smsMessage->transaction_reference ?? '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Type</div><div class="dv">{{ $smsMessage->transaction_type ? txn_type_label($smsMessage->transaction_type) : '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Amount</div><div class="dv">{{ $smsMessage->amount ? money($smsMessage->amount) : '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Customer</div><div class="dv">{{ $smsMessage->customer_name ?? '—' }} @if($smsMessage->customer_phone) ({{ $smsMessage->customer_phone }}) @endif</div></div>
                    <div class="detail-item"><div class="dk">Balance</div><div class="dv">{{ $smsMessage->balance ? money($smsMessage->balance) : '—' }}</div></div>
                    <div class="detail-item"><div class="dk">Status</div><div class="dv"><span class="tag {{ sms_status_badge($smsMessage->processing_status, $smsMessage->is_duplicate, $smsMessage->processing_error) }}">{{ $smsMessage->processing_status }}</span> @if($smsMessage->is_duplicate) <span class="tag tag-red">Duplicate</span> @endif</div></div>
                    @if($smsMessage->processing_error)
                        <div class="detail-item" style="grid-column:1/-1;"><div class="dk">Processing error</div><div class="dv" style="color:var(--danger);">{{ $smsMessage->processing_error }}</div></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Actions</h3>
            </div>
            <div class="panel-body">
                @if($smsMessage->transaction)
                    <div style="background:var(--acacia-100); border:1px solid var(--acacia-600); border-radius:8px; padding:14px; margin-bottom:12px;">
                        <strong style="color:var(--acacia-600);">Already processed</strong><br>
                        <span style="font-size:13px;">→ <a href="{{ route('transactions.receipt', $smsMessage->transaction) }}" style="color:var(--terracotta-600);">{{ $smsMessage->transaction->reference }} · {{ money($smsMessage->transaction->amount) }}</a></span>
                    </div>
                    <a href="{{ route('transactions.receipt', $smsMessage->transaction) }}" class="btn btn-primary">View transaction receipt</a>
                @else
                    <p style="font-size:13px; color:var(--ink-soft); margin-bottom:14px;">This SMS is currently <strong>{{ sms_status_label($smsMessage->processing_status, $smsMessage->is_duplicate, $smsMessage->processing_error) }}</strong>. You can approve, force compute it as a transaction, or use the manual form below.</p>
                    @if($smsMessage->processing_status === 'APPROVAL_PENDING')
                        <button type="button" class="btn btn-primary" onclick="openApproveModal()">Approve &amp; record transaction</button>
                        <p style="font-size:11px;color:var(--ink-soft);margin-top:8px;">This detected transaction is waiting for approval. Approving records it as a full transaction with float/cash adjustments.</p>
                    @endif
                    <form method="POST" action="{{ route('sms.force', $smsMessage) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-primary">Force compute to transaction</button>
                    </form>
                    <p style="font-size:11px; color:var(--ink-soft); margin-top:8px;">Force will try strict template first, then fallback to generic Tsh/Tsh amount + Tnx extraction even for promo/unknown. Use when you are sure this is a real financial SMS.</p>
                @endif
                <div style="margin-top:14px; display:flex; gap:8px; flex-wrap:wrap;">
                    <a href="{{ route('sms.index', ['device' => $smsMessage->device_id]) }}" class="btn btn-ghost">Back to device SMS</a>
                    <a href="{{ route('transactions.index', ['q' => $smsMessage->transaction_reference ?? $smsMessage->sender]) }}" class="btn btn-ghost">Search transactions</a>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Full Message</h3>
            <span class="link">{{ $smsMessage->sender }}</span>
        </div>
        <div class="panel-body">
            <pre style="white-space:pre-wrap; word-break:break-word; font-family:ui-monospace,monospace; font-size:13.5px; line-height:1.7; background:var(--sand-100); border:1px solid var(--line); border-radius:8px; padding:16px; margin:0; color:var(--coffee-900);">{{ $smsMessage->message_body }}</pre>
            <div style="margin-top:12px; display:flex; gap:8px;">
                <button class="btn btn-ghost btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('smsBodyFull').innerText).then(()=>toast('Copied','success'))">Copy message</button>
                <span id="smsBodyFull" style="display:none;">{{ $smsMessage->message_body }}</span>
            </div>
        </div>
    </div>

    @if(!$smsMessage->transaction)
        <div class="panel">
            <div class="panel-head">
                <h3>Manual Process to Transaction</h3>
                <span class="link">Fill and submit</span>
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('sms.process', $smsMessage) }}" data-sms-manual-form>
                    @csrf
                    <div class="form-row">
                        <div class="field"><label>Network</label><select name="network_id" required><option value="">Select network</option>@foreach(\App\Models\Network::orderBy('name')->get() as $nw)<option value="{{ $nw->id }}" {{ $smsMessage->network_id == $nw->id ? 'selected' : '' }}>{{ $nw->name }}</option>@endforeach</select></div>
                        <div class="field"><label>Type</label><select name="type" required><option value="deposit" {{ $smsMessage->transaction_type === 'deposit' ? 'selected' : '' }}>Customer Deposit</option><option value="withdrawal" {{ $smsMessage->transaction_type === 'withdrawal' ? 'selected' : '' }}>Customer Withdrawal</option><option value="send_money">Send Money</option><option value="bill_payment">Bill Payment</option><option value="airtime">Airtime</option><option value="data">Data Bundle</option><option value="bank_to_wallet" {{ str_contains(strtolower($smsMessage->message_body), 'union financial') || str_contains(strtolower($smsMessage->message_body), 'kiasi:tsh') ? 'selected' : '' }}>Bank to Wallet</option><option value="wallet_to_bank">Wallet to Bank</option><option value="float_deposit" {{ $smsMessage->transaction_type === 'float_deposit' ? 'selected' : '' }}>Float Deposit</option></select></div>
                    </div>
                    <div class="form-row">
                        <div class="field"><label>Amount (TZS)</label><input type="number" name="amount" value="{{ old('amount', $smsMessage->amount ?? '') }}" min="1" step="any" required></div>
                        <div class="field"><label>Reference (optional)</label><input type="text" name="reference" value="{{ old('reference', $smsMessage->transaction_reference ?? '') }}" placeholder="e.g. 26918503705735"></div>
                    </div>
                    <div class="form-row">
                        <div class="field"><label>Customer name</label><input type="text" name="customer_name" value="{{ old('customer_name', $smsMessage->customer_name ?? '') }}"></div>
                        <div class="field"><label>Customer phone</label><input type="text" name="customer_phone" value="{{ old('customer_phone', $smsMessage->customer_phone ?? '') }}" required placeholder="07xxxxxxxx"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Process to transaction</button>
                </form>
            </div>
        </div>
    @endif

    @if($smsMessage->transaction)
        <div class="panel">
            <div class="panel-head">
                <h3>Linked Transaction</h3>
                <a href="{{ route('transactions.receipt', $smsMessage->transaction) }}" class="link">View receipt</a>
            </div>
            <div class="panel-body">
                <div class="detail-grid">
                    <div class="detail-item"><div class="dk">Reference</div><div class="dv">{{ $smsMessage->transaction->reference }}</div></div>
                    <div class="detail-item"><div class="dk">Provider ref</div><div class="dv">{{ $smsMessage->transaction->provider_reference }}</div></div>
                    <div class="detail-item"><div class="dk">Amount</div><div class="dv">@money($smsMessage->transaction->amount)</div></div>
                    <div class="detail-item"><div class="dk">Commission</div><div class="dv">@money($smsMessage->transaction->commission)</div></div>
                    <div class="detail-item"><div class="dk">Status</div><div class="dv"><span class="tag {{ status_badge($smsMessage->transaction->status) }}">{{ ucfirst($smsMessage->transaction->status) }}</span></div></div>
                    <div class="detail-item"><div class="dk">Network</div><div class="dv">{{ $smsMessage->transaction->network?->name ?? '—' }}</div></div>
                </div>
            </div>
        </div>
    @endif

    <!-- Approve transaction confirm modal -->
    @if($smsMessage->processing_status === 'APPROVAL_PENDING')
        <div class="modal-backdrop" id="approveSmsModal">
            <div class="modal" style="max-width:540px;">
                <div class="modal-head">
                    <h3>Record this detected transaction?</h3>
                    <button class="modal-close" onclick="closeModal('approveSmsModal')">✕</button>
                </div>
                <div class="modal-body">
                    <div id="approveSmsError" class="box-alert" style="display:none;"></div>
                    <p style="font-size:13px;color:var(--ink-soft);margin:0 0 10px;">This auto-detected message will be recorded as a full transaction with float/cash adjustments. Only approve it if the transaction is genuine.</p>
                    <div class="detail-grid">
                        <div class="detail-item"><div class="dk">Sender</div><div class="dv">{{ $smsMessage->sender }}</div></div>
                        <div class="detail-item"><div class="dk">Network</div><div class="dv">@if($smsMessage->network)<span class="net-dot" style="background:{{ $smsMessage->network->color }};"></span> {{ $smsMessage->network->name }}@else — @endif</div></div>
                        <div class="detail-item"><div class="dk">Type</div><div class="dv">{{ $smsMessage->transaction_type ? txn_type_label($smsMessage->transaction_type) : '—' }}</div></div>
                        <div class="detail-item"><div class="dk">Amount</div><div class="dv">{{ $smsMessage->amount ? money($smsMessage->amount) : '—' }}</div></div>
                        <div class="detail-item"><div class="dk">Customer</div><div class="dv">{{ $smsMessage->customer_name ?? '—' }} @if($smsMessage->customer_phone) ({{ $smsMessage->customer_phone }}) @endif</div></div>
                        <div class="detail-item"><div class="dk">Reference</div><div class="dv">{{ $smsMessage->transaction_reference ?? '—' }}</div></div>
                    </div>
                    <div class="receipt" style="margin-top:12px;">
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);font-weight:700;margin-bottom:8px;">Detected message</div>
                        <pre style="white-space:pre-wrap;word-break:break-word;font-family:inherit;font-size:13.5px;line-height:1.6;margin:0;color:var(--coffee-700);max-height:140px;overflow:auto;">{{ $smsMessage->message_body }}</pre>
                    </div>
                </div>
                <div class="modal-foot">
                    <button class="btn btn-ghost" onclick="closeModal('approveSmsModal')">Cancel</button>
                    <button class="btn btn-primary" id="approveSmsBtn" onclick="submitApprove()">Yes, record transaction</button>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        function openApproveModal() { openModal('approveSmsModal'); }

        function submitApprove() {
            const btn = document.getElementById('approveSmsBtn');
            btn.disabled = true;
            btn.textContent = 'Recording…';
            fetch('{{ route('sms.approve', $smsMessage) }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({}),
            })
            .then(r => r.json().catch(() => ({})).then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                if (!ok || !d.success) throw new Error(d.message || 'Approval failed.');
                toast(d.message, 'success');
                closeModal('approveSmsModal');
                setTimeout(() => window.location.reload(), 700);
            })
            .catch(err => {
                document.getElementById('approveSmsError').style.display = 'block';
                document.getElementById('approveSmsError').textContent = err.message || 'Something went wrong.';
                btn.disabled = false;
                btn.textContent = 'Yes, record transaction';
            });
        }

        document.querySelectorAll('[data-sms-manual-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: (data) => { toast(data.message, 'success'); setTimeout(() => window.location.reload(), 700); } });
            });
        });
    </script>
@endsection
