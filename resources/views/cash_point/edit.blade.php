@extends('layouts.app')

@section('title', 'Edit '.$cashPoint->name)

@section('content')
    <div class="view-head">
        <div>
            <h2>Edit Cash Point</h2>
            <p class="sub">{{ $cashPoint->name }} · {{ $cashPoint->code }}</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('cash-point.show', $cashPoint) }}" class="btn btn-ghost">← View cash point</a>
            <a href="{{ route('cash-point.index') }}" class="btn btn-ghost">Back</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Cash Point Information</h3>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('cash-point.update.id', $cashPoint) }}" data-cashpoint-form>
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="field"><label>Code</label><input type="text" name="code" value="{{ old('code', $cashPoint->code) }}" required></div>
                    <div class="field"><label>Name</label><input type="text" name="name" value="{{ old('name', $cashPoint->name) }}" required></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Owner name</label><input type="text" name="owner_name" value="{{ old('owner_name', $cashPoint->owner_name) }}"></div>
                    <div class="field"><label>Phone</label><input type="text" name="phone" value="{{ old('phone', $cashPoint->phone) }}" required></div>
                </div>
                <div class="field"><label>National ID</label><input type="text" name="national_id" value="{{ old('national_id', $cashPoint->national_id) }}"></div>
                <div class="form-row">
                    <div class="field"><label>Region</label><input type="text" name="region" value="{{ old('region', $cashPoint->region) }}"></div>
                    <div class="field"><label>District</label><input type="text" name="district" value="{{ old('district', $cashPoint->district) }}"></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Ward</label><input type="text" name="ward" value="{{ old('ward', $cashPoint->ward) }}"></div>
                    <div class="field"><label>Street</label><input type="text" name="street" value="{{ old('street', $cashPoint->street) }}"></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Agent Level</label>
                        <select name="agent_level" required>
                            @foreach(['bronze','silver','gold','platinum'] as $lvl)
                                <option value="{{ $lvl }}" {{ $cashPoint->agent_level === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Status</label>
                        <select name="status" required>
                            @foreach(['active','suspended','inactive'] as $st)
                                <option value="{{ $st }}" {{ $cashPoint->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('cash-point.show', $cashPoint) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-cashpoint-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => { toast('Cash point updated.', 'success'); setTimeout(() => window.location.href = '{{ route('cash-point.show', $cashPoint) }}', 600); } });
            });
        });
    </script>
@endsection
