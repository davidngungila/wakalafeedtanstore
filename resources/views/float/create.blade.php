@extends('layouts.app')

@section('title', 'New Float Entry')

@section('content')
    <div class="view-head">
        <div>
            <h2>New Float / Cash Entry</h2>
            <p class="sub">Record a new float top-up, pull, or cash movement.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('float.index') }}" class="btn btn-ghost">← Back to float</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Float Entry Details</h3>
            <span class="link">{{ $isAdmin ? 'Admin — any date' : 'Today' }} · {{ $viewDate->format('d M Y') }}</span>
        </div>
        <div class="panel-body">
            <form action="{{ route('float.store') }}" method="POST" data-float-form>
                @csrf
                @if($isAdmin)
                    <div class="field">
                        <label>Float date (admin — for selected day)</label>
                        <input type="date" name="float_date" value="{{ old('float_date', $selectedDate) }}" max="{{ today()->toDateString() }}">
                        <p style="font-size:11px; color:var(--ink-soft); margin-top:4px;">Pick the day this float movement actually happened. It will be counted for that day's reports/reconciliation and use that day's opening if exists. Leave as today for live float.</p>
                    </div>
                @endif
                <div class="form-row">
                    <div class="field">
                        <label>Network</label>
                        <select name="network_id" required>
                            @foreach ($networks as $id => $name)
                                <option value="{{ $id }}" {{ old('network_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select name="type" required>
                            <option value="float_topup" {{ old('type') === 'float_topup' ? 'selected' : '' }}>Float top-up (float in)</option>
                            <option value="float_pull" {{ old('type') === 'float_pull' ? 'selected' : '' }}>Float pull (float out)</option>
                            <option value="cash_in" {{ old('type') === 'cash_in' ? 'selected' : '' }}>Cash deposited to network</option>
                            <option value="cash_out" {{ old('type') === 'cash_out' ? 'selected' : '' }}>Cash withdrawn from network</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label>Amount (TZS)</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" min="1" step="any" placeholder="e.g. 500000" required>
                </div>
                <div class="field">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Optional reference…">{{ old('notes') }}</textarea>
                </div>
                @if($todayOpening)
                    <div style="margin-top:12px; padding:8px 10px; background:var(--sand-100); border:1px solid var(--line); border-radius:6px; font-size:12px; color:var(--ink-soft);">Opening for {{ $viewDate->format('Y-m-d') }}: Cash @money($todayOpening->cash_opening) · Float total @money($todayOpening->totalFloatOpening())</div>
                @endif
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="submit" class="btn btn-primary">Save entry</button>
                    <a href="{{ route('float.index', $isAdmin ? ['date' => $selectedDate] : []) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-float-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => setTimeout(() => window.location.href = '{{ route('float.index') }}', 600) });
            });
        });
    </script>
@endsection
