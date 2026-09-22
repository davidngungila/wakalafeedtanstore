@extends('layouts.app')

@section('title', 'Add correction — Reconciliation #' . $reconciliation->code)

@section('content')
    <div class="view-head">
        <div>
            <h2>Add correction</h2>
            <p class="sub">Fix differences with a reference · Reconciliation #{{ $reconciliation->code }}</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('reconciliation.show', $reconciliation) }}" class="btn btn-ghost">← Back to session</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="box-alert">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="panel">
        <div class="panel-head">
            <h3>Record a correction</h3>
            <span class="link">Reconciliation #{{ $reconciliation->code }}</span>
        </div>
        <form method="POST" action="{{ route('reconciliation.corrections.store', $reconciliation) }}" data-correction-form>
            @csrf
            <div class="panel-body">
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
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Correction type</label>
                        <select name="type" required>
                            @foreach (\App\Models\ReconciliationCorrection::types() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Reference <span style="font-weight:400;color:var(--ink-soft);font-size:12px;">(transaction / customer / reason)</span></label>
                        <input type="text" name="reference" maxlength="120" placeholder="e.g. TXN-88213 or customer name" value="{{ old('reference') }}" required>
                    </div>
                    <div class="field">
                        <label>Amount (TZS)</label>
                        <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" placeholder="0.00" required>
                    </div>
                    <div class="field" style="grid-column:1 / -1;">
                        <label>Notes (optional)</label>
                        <textarea name="notes" rows="3" maxlength="1000" placeholder="Additional details...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <a href="{{ route('reconciliation.show', $reconciliation) }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Record correction</button>
            </div>
        </form>
    </div>

    <div class="modal-backdrop" id="confirmCorrectionModal">
        <div class="modal">
            <div class="modal-head">
                <h3>Confirm correction</h3>
                <button class="modal-close" onclick="closeModal('confirmCorrectionModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13.5px;color:var(--ink-soft);margin-bottom:14px;">Are you sure you want to record this correction? It will be applied to the <b>Reconciliation #{{ $reconciliation->code }}</b> session.</p>
                <div class="receipt">
                    <div class="receipt-row"><span>Applies to</span><b id="cfmAppliesTo">—</b></div>
                    <div class="receipt-row"><span>Correction type</span><b id="cfmType">—</b></div>
                    <div class="receipt-row"><span>Reference</span><b id="cfmReference">—</b></div>
                    <div class="receipt-row"><span>Amount</span><b id="cfmAmount">—</b></div>
                    <div class="receipt-row"><span>Notes</span><b id="cfmNotes">—</b></div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('confirmCorrectionModal')">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmCorrectionBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M20 6 9 17l-5-5"></path></svg>
                    Yes, record correction
                </button>
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

        function cfmSet(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value || '—';
        }

        const correctionForm = document.querySelector('[data-correction-form]');

        correctionForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!correctionForm.reportValidity()) return;

            const scope = correctionForm.querySelector('[name="scope"]');
            const network = correctionForm.querySelector('[name="network_id"]');
            const type = correctionForm.querySelector('[name="type"]');
            const ref = correctionForm.querySelector('[name="reference"]');
            const amount = correctionForm.querySelector('[name="amount"]');
            const notes = correctionForm.querySelector('[name="notes"]');

            const appliesTo = scope.value === 'float'
                ? (network.selectedOptions[0]?.textContent.trim() || 'Network Float')
                : 'Cash in Till';

            cfmSet('cfmAppliesTo', appliesTo);
            cfmSet('cfmType', type.selectedOptions[0]?.textContent.trim());
            cfmSet('cfmReference', ref.value.trim());
            cfmSet('cfmAmount', 'TZS ' + Number(amount.value).toLocaleString('en-US', { maximumFractionDigits: 2 }));
            cfmSet('cfmNotes', notes.value.trim());

            openModal('confirmCorrectionModal');
        });

        document.getElementById('confirmCorrectionBtn').addEventListener('click', () => {
            closeModal('confirmCorrectionModal');
            submitForm(correctionForm, { method: 'POST', done: () => setTimeout(() => window.location.href = '{{ route('reconciliation.show', $reconciliation) }}', 600) });
        });
    </script>
@endsection