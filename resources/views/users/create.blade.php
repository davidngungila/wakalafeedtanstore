@extends('layouts.app')

@section('title', 'Add User')

@section('content')
    <div class="view-head">
        <div>
            <h2>Add User</h2>
            <p class="sub">Create a new account and optionally send the login credentials by SMS.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('users.index') }}" class="btn btn-ghost">← Back to users</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>User Information</h3>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('users.store') }}" data-user-form>
                @csrf
                <div class="form-row">
                    <div class="field"><label>Full name</label><input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Baraka Mushi" required></div>
                    <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" placeholder="name@company.com" required></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Phone</label><input type="text" name="phone" value="{{ old('phone') }}" placeholder="07xxxxxxxx"></div>
                    <div class="field"><label>Password</label><input type="text" name="password" value="{{ old('password') }}" placeholder="Min 6 characters" minlength="6" required></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Role</label>
                        <select name="role" required>
                            <option value="cashier" {{ old('role', 'cashier') === 'cashier' ? 'selected' : '' }}>Cashier</option>
                            <option value="supervisor" {{ old('role') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                    <div class="field"><label>Status</label>
                        <select name="is_active">
                            <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="field"><label>Linked cash point</label>
                    <select name="agent_id">
                        <option value="">— Not linked —</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}" {{ (string) old('agent_id') === (string) $agent->id ? 'selected' : '' }}>{{ $agent->name }} ({{ $agent->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-top:8px;">
                    <label style="display:flex; align-items:center; gap:8px; text-transform:none; font-size:13px;">
                        <input type="checkbox" name="send_credentials_sms" value="1" style="width:16px; height:16px;" {{ old('send_credentials_sms', '1') === '1' ? 'checked' : '' }}>
                        Send the login credentials by SMS to this phone
                    </label>
                </div>
                @error('password')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                @error('email')<p style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="submit" class="btn btn-primary">Save user</button>
                    <a href="{{ route('users.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-user-form]').forEach(form => {
            form.addEventListener('submit', () => {
                const btn = form.querySelector('[type="submit"]');
                if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
            });
        });
    </script>
@endsection
