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

    <div class="panel" style="max-width:720px;">
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
                        <input type="text" name="reference" maxlength="120" placeholder="e.g. TXN-88213 or customer name" value="{{ old('reference') }}" required>
                    </div>
                    <div class="field">
                        <label>Amount (TZS)</label>
                        <input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                    </div>
                </div>
                <div class="field">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="2" maxlength="1000" placeholder="Additional details...">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="modal-foot">
                <a href="{{ route('reconciliation.show', $reconciliation) }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Record correction</button>
            </div>
        </form>
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
                submitForm(form, { method: 'POST', done: () => setTimeout(() => window.location.href = '{{ route('reconciliation.show', $reconciliation) }}', 600) });
            });
        });
    </script>
@endsection