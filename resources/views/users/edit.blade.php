@extends('layouts.app')

@section('title', 'Edit '.$user->name)

@section('content')
    <div class="view-head">
        <div>
            <h2>Edit User</h2>
            <p class="sub">{{ $user->name }} · {{ $user->email }}</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('users.show', $user) }}" class="btn btn-ghost">← View user</a>
            <a href="{{ route('users.index') }}" class="btn btn-ghost">Back to users</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>User Information</h3>
            <span class="link">{{ ucfirst($user->role) }}</span>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('users.update', $user) }}" data-user-form>
                @csrf
                @method('PUT')
                <div class="form-row">
                    <div class="field"><label>Full name</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
                    <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Phone</label><input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="07xxxxxxxx"></div>
                    <div class="field"><label>Role</label>
                        <select name="role" required>
                            <option value="cashier" {{ $user->role === 'cashier' ? 'selected' : '' }}>Cashier</option>
                            <option value="supervisor" {{ $user->role === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field"><label>Cash Point</label>
                        <select name="agent_id">
                            <option value="">— No cash point —</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ (string)$user->agent_id === (string)$agent->id ? 'selected' : '' }}>{{ $agent->name }} ({{ $agent->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Status</label>
                        <select name="is_active">
                            <option value="1" {{ $user->is_active ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="field"><label>New password (leave blank to keep current)</label><input type="password" name="password" placeholder="Min 6 characters" autocomplete="new-password"></div>
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('users.show', $user) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Send SMS</h3>
            <span class="link">{{ $user->phone ?: 'No phone on file' }}</span>
        </div>
        <div class="panel-body">
            @if ($user->phone)
                @php
                    $templateVars = ['name' => $user->name ?? '', 'email' => $user->email ?? '', 'phone' => $user->phone ?? ''];
                    $templateTexts = collect(app(\App\Services\SmsTemplates::class)->all())->mapWithKeys(fn ($t, $k) => [$k => app(\App\Services\SmsTemplates::class)->render($k, $templateVars)])->all();
                @endphp
                <div class="field" style="margin-bottom:14px;">
                    <label>Template (optional)</label>
                        <select id="userSmsTemplate">
                            <option value="">— Custom message —</option>
                            @foreach (app(\App\Services\SmsTemplates::class)->all() as $key => $template)
                                @continue($key === 'credentials')
                                <option value="{{ $key }}">{{ $template['label'] }}</option>
                            @endforeach
                        </select>
                </div>
                <div class="field">
                    <label>Message</label>
                    <textarea id="userSmsText" rows="3" maxlength="1000" placeholder="Write a message to {{ $user->name }}…"></textarea>
                </div>
                <script>
                    const USER_SMS_TEMPLATES = @json($templateTexts);
                    document.getElementById('userSmsTemplate')?.addEventListener('change', function () {
                        const text = USER_SMS_TEMPLATES[this.value];
                        if (text !== undefined) { document.getElementById('userSmsText').value = text; }
                    });
                </script>
                <div style="display:flex; gap:10px; margin-top:14px; align-items:center;">
                    <button type="button" id="sendUserSmsBtn" class="btn btn-primary btn-sm">Send SMS</button>
                    <span id="userSmsStatus" style="font-size:13px; color:var(--ink-soft);"></span>
                </div>
            @else
                <p style="font-size:13px; color:var(--ink-soft);">Add a phone number to this user to send SMS.</p>
            @endif
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Login credentials SMS</h3>
            <span class="link">{{ $user->phone ?: 'No phone on file' }}</span>
        </div>
        <div class="panel-body">
            @if ($user->phone)
                <p style="font-size:13px; color:var(--ink-soft); margin-bottom:12px;">Sets a new password for {{ $user->name }} and sends the login credentials by SMS. Leave the password blank to generate one automatically.</p>
                <form method="POST" action="{{ route('users.credentials.sms', $user) }}" data-credentials-form>
                    @csrf
                    <div class="field">
                        <label>New password</label>
                        <input type="text" name="password" value="" minlength="6" placeholder="Leave blank to generate">
                    </div>
                    <div style="display:flex; gap:10px; margin-top:14px; align-items:center;">
                        <button type="submit" class="btn btn-primary btn-sm">Reset password &amp; send SMS</button>
                        <span id="credentialsSmsStatus" style="font-size:13px; color:var(--ink-soft);"></span>
                    </div>
                </form>
            @else
                <p style="font-size:13px; color:var(--ink-soft);">Add a phone number to this user to send the credentials SMS.</p>
            @endif
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h3>Danger Zone</h3>
        </div>
        <div class="panel-body">
            <p style="font-size:13px; color:var(--ink-soft); margin-bottom:12px;">Delete this user account. This action cannot be undone.</p>
            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete user</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('sendUserSmsBtn')?.addEventListener('click', async () => {
            const btn = document.getElementById('sendUserSmsBtn');
            const status = document.getElementById('userSmsStatus');
            const text = document.getElementById('userSmsText').value.trim();
            if (!text) { status.textContent = 'Type a message first.'; return; }
            btn.disabled = true;
            status.textContent = 'Sending…';
            try {
                const resp = await fetch('{{ route('settings.sms.send') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ to: '{{ $user->phone }}', text: text }),
                });
                const data = await resp.json().catch(() => ({}));
                status.textContent = (resp.ok && data.success) ? (data.message || 'SMS sent.') : (data.message || 'Failed to send SMS.');
                status.style.color = (resp.ok && data.success) ? 'var(--acacia-600)' : 'var(--danger)';
                if (resp.ok && data.success) document.getElementById('userSmsText').value = '';
            } catch (e) {
                status.textContent = 'Network error.';
                status.style.color = 'var(--danger)';
            } finally {
                btn.disabled = false;
            }
        });

        document.querySelectorAll('[data-user-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                // Use POST with _method=PUT for FormData compatibility (fixes "The name field is required" on PUT + FormData)
                submitForm(form, { method: 'POST', done: () => { toast('User updated successfully.', 'success'); setTimeout(() => window.location.href = '{{ route('users.show', $user) }}', 600); } });
            });
        });
    </script>
@endsection
