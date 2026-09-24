@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    @php
        $gen = $settings['general'] ?? [];
        $comm = $settings['commissions'] ?? [];
        $sec = $settings['security'] ?? [];
        $notif = $settings['notifications'] ?? [];
        $email = $settings['email'] ?? [];
    @endphp
    <div class="view-head">
        <div>
            <h2>System Settings</h2>
            <p class="sub">Configure the business profile, commissions, security rules and notifications.</p>
        </div>
        <div class="view-actions">
            <button class="btn btn-ghost" onclick="window.location.reload()">Reset form</button>
        </div>
    </div>

    <div class="settings-layout">
        <nav class="settings-nav">
            <a href="{{ route('settings.index') }}?pane=general" class="{{ $pane === 'general' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect></svg>
                General
            </a>
            <a href="{{ route('settings.index') }}?pane=commissions" class="{{ $pane === 'commissions' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M15 9h-3.5a1 1 0 0 0 0 2h1a1 1 0 0 1 0 2H9"></path><path d="M12 6v12"></path></svg>
                Commissions
            </a>
            <a href="{{ route('settings.index') }}?pane=security" class="{{ $pane === 'security' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Security
            </a>
            <a href="{{ route('settings.index') }}?pane=notifications" class="{{ $pane === 'notifications' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                Notifications
            </a>
            <a href="{{ route('settings.index') }}?pane=cashpoint" class="{{ $pane === 'cashpoint' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7l1.5-2.5h17L22 7z"></path><path d="M3 7h18v13H3z"></path><path d="M9 13h6"></path></svg>
                Cash Point
            </a>
            <a href="{{ route('settings.index') }}?pane=email" class="{{ $pane === 'email' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                Email
            </a>
        </nav>

        <div class="settings-panel" id="settingsPanel">
            @if ($pane === 'general')
                <h3>General</h3>
                <form method="POST" action="{{ route('settings.store') }}" data-settings-form>
                    @csrf
                    <div class="field"><label>Business name</label><input type="text" name="general[business_name]" value="{{ $gen['business_name'] ?? '' }}" placeholder="Wakala Feed Tan Store"></div>
                    <div class="field"><label>Address</label><input type="text" name="general[address]" value="{{ $gen['address'] ?? '' }}" placeholder="Mikocheni, Dar es Salaam"></div>
                    <div class="form-row">
                        <div class="field"><label>Contact email</label><input type="email" name="general[contact_email]" value="{{ $gen['contact_email'] ?? '' }}" placeholder="hello@company.com"></div>
                        <div class="field"><label>Contact phone</label><input type="text" name="general[contact_phone]" value="{{ $gen['contact_phone'] ?? '' }}" placeholder="+255 7xx xxx xxx"></div>
                    </div>
                    <div class="field"><label>Default currency</label>
                        <select name="general[currency]">
                            <option value="TZS" {{ ($gen['currency'] ?? 'TZS') === 'TZS' ? 'selected' : '' }}>TZS – Tanzanian Shilling</option>
                            <option value="KES" {{ ($gen['currency'] ?? '') === 'KES' ? 'selected' : '' }}>KES – Kenyan Shilling</option>
                            <option value="UGX" {{ ($gen['currency'] ?? '') === 'UGX' ? 'selected' : '' }}>UGX – Ugandan Shilling</option>
                            <option value="USD" {{ ($gen['currency'] ?? '') === 'USD' ? 'selected' : '' }}>USD – US Dollar</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save general settings</button>
                </form>
            @elseif ($pane === 'commissions')
                <h3>Commissions</h3>
                <form method="POST" action="{{ route('settings.store') }}" data-settings-form>
                    @csrf
                    <p style="font-size:13px;color:var(--ink-soft);margin-bottom:16px;">These defaults apply when a network has no specific rate configured.</p>
                    <div class="form-row">
                        <div class="field"><label>Default commission %</label><input type="number" name="commissions[default_rate]" step="any" min="0" value="{{ $comm['default_rate'] ?? 0.5 }}"></div>
                        <div class="field"><label>Minimum agent commission (TZS)</label><input type="number" name="commissions[min_agent_rate]" step="any" min="0" value="{{ $comm['min_agent_rate'] ?? 100 }}"></div>
                    </div>
                    <div class="form-row">
                        <div class="field"><label>Float top-up fee (TZS)</label><input type="number" name="commissions[topup_fee]" step="any" min="0" value="{{ $comm['topup_fee'] ?? 1500 }}"></div>
                        <div class="field"><label>Reversal fee (TZS)</label><input type="number" name="commissions[reversal_fee]" step="any" min="0" value="{{ $comm['reversal_fee'] ?? 0 }}"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save commission settings</button>
                </form>
            @elseif ($pane === 'security')
                <h3>Security</h3>
                <form method="POST" action="{{ route('settings.store') }}" data-settings-form>
                    @csrf
                    <div class="form-row">
                        <div class="field"><label>Max transaction limit (TZS)</label><input type="number" name="security[max_transaction_limit]" step="any" min="0" value="{{ $sec['max_transaction_limit'] ?? 3000000 }}" placeholder="3,000,000"></div>
                        <div class="field"><label>Min withdrawal limit (TZS)</label><input type="number" name="security[min_withdrawal_limit]" step="any" min="0" value="{{ $sec['min_withdrawal_limit'] ?? 1000 }}" placeholder="1,000"></div>
                    </div>
                    <div class="form-row">
                        <div class="field"><label>Require approval above (TZS)</label><input type="number" name="security[require_approval_above]" step="any" min="0" value="{{ $sec['require_approval_above'] ?? 1000000 }}" placeholder="1,000,000"></div>
                        <div class="field"><label>Session timeout (minutes)</label><input type="number" name="security[session_timeout_minutes]" step="1" min="1" value="{{ $sec['session_timeout_minutes'] ?? 30 }}"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save security settings</button>
                </form>
            @elseif ($pane === 'cashpoint')
                <h3>Cash Point</h3>
                @php
                    $agentConfigured = $agent !== null
                        && !empty($agent->code)
                        && !empty($agent->name)
                        && !empty($agent->phone)
                        && $agent->status === 'active';
                @endphp
                @if ($agentConfigured)
                    <p style="font-size:13px;color:var(--ink-soft);margin-bottom:16px;">The cash point is configured as <strong>{{ $agent->name }}</strong> ({{ $agent->code }}). You can update its details below.</p>
                @else
                    <p style="font-size:13px;color:var(--terracotta-600);margin-bottom:16px;">The cash point is not set up yet. Create it below to activate the system.</p>
                @endif
                <form method="POST" action="{{ route('cash-point.update') }}" data-cashpoint-form>
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    @if ($errors->any())
                        <div class="form-errors" style="color:var(--danger);margin-bottom:16px;">
                            @foreach ($errors->all() as $error)
                                <div>• {{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    @include('cash_point._fields')
                    <button type="submit" class="btn btn-primary">Save cash point</button>
                </form>
            @elseif ($pane === 'email')
                <h3>Email — OTP & Reports</h3>
                <p style="font-size:13px; color:var(--ink-soft); margin-bottom:16px;">Configure SMTP to send OTP codes and report emails. These settings override <code>.env</code> <code>MAIL_*</code> when saved.</p>
                <form method="POST" action="{{ route('settings.store') }}" data-settings-form>
                    @csrf
                    <div class="form-row">
                        <div class="field"><label>Mailer</label>
                            <select name="email[mail_mailer]" style="padding:12px 14px; border:1.5px solid var(--line); border-radius:8px; background:var(--white);">
                                <option value="smtp" {{ ($email['mail_mailer'] ?? 'smtp') === 'smtp' ? 'selected' : '' }}>smtp</option>
                                <option value="log" {{ ($email['mail_mailer'] ?? '') === 'log' ? 'selected' : '' }}>log (for testing)</option>
                                <option value="ses" {{ ($email['mail_mailer'] ?? '') === 'ses' ? 'selected' : '' }}>ses</option>
                            </select>
                        </div>
                        <div class="field"><label>Encryption</label>
                            <select name="email[mail_encryption]" style="padding:12px 14px; border:1.5px solid var(--line); border-radius:8px; background:var(--white);">
                                <option value="tls" {{ ($email['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>tls</option>
                                <option value="ssl" {{ ($email['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>ssl</option>
                                <option value="" {{ ($email['mail_encryption'] ?? '') === '' ? 'selected' : '' }}>none</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field"><label>SMTP Host</label><input type="text" name="email[mail_host]" value="{{ $email['mail_host'] ?? '' }}" placeholder="smtp.mailtrap.io"></div>
                        <div class="field"><label>Port</label><input type="number" name="email[mail_port]" value="{{ $email['mail_port'] ?? '' }}" placeholder="587"></div>
                    </div>
                    <div class="form-row">
                        <div class="field"><label>Username</label><input type="text" name="email[mail_username]" value="{{ $email['mail_username'] ?? '' }}" placeholder="your_username"></div>
                        <div class="field"><label>Password</label><input type="password" name="email[mail_password]" value="{{ $email['mail_password'] ?? '' }}" placeholder="••••••••"></div>
                    </div>
                    <div class="form-row">
                        <div class="field"><label>From Address</label><input type="email" name="email[mail_from_address]" value="{{ $email['mail_from_address'] ?? '' }}" placeholder="noreply@wakala.com"></div>
                        <div class="field"><label>From Name</label><input type="text" name="email[mail_from_name]" value="{{ $email['mail_from_name'] ?? '' }}" placeholder="Wakala Feedtan Store"></div>
                    </div>
                    <div style="height:1px; background:var(--line); margin:18px 0;"></div>
                    <div class="toggle-row">
                        <div class="toggle-text"><strong>OTP via Email</strong><span>Send OTP codes by email for 2FA and verification.</span></div>
                        <select name="email[otp_via_email]" style="padding:8px 10px; border:1.5px solid var(--line); border-radius:9px; font-weight:600; background:var(--white);">
                            <option value="1" {{ ($email['otp_via_email'] ?? '1') == 1 ? 'selected' : '' }}>On</option>
                            <option value="0" {{ ($email['otp_via_email'] ?? '1') == 0 ? 'selected' : '' }}>Off</option>
                        </select>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-text"><strong>OTP Enabled</strong><span>Master switch for OTP generation.</span></div>
                        <select name="email[otp_enabled]" style="padding:8px 10px; border:1.5px solid var(--line); border-radius:9px; font-weight:600; background:var(--white);">
                            <option value="1" {{ ($email['otp_enabled'] ?? '1') == 1 ? 'selected' : '' }}>On</option>
                            <option value="0" {{ ($email['otp_enabled'] ?? '1') == 0 ? 'selected' : '' }}>Off</option>
                        </select>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-text"><strong>Reports via Email</strong><span>Send daily/weekly/monthly reports by email.</span></div>
                        <select name="email[reports_via_email]" style="padding:8px 10px; border:1.5px solid var(--line); border-radius:9px; font-weight:600; background:var(--white);">
                            <option value="1" {{ ($email['reports_via_email'] ?? '1') == 1 ? 'selected' : '' }}>On</option>
                            <option value="0" {{ ($email['reports_via_email'] ?? '1') == 0 ? 'selected' : '' }}>Off</option>
                        </select>
                    </div>
                    <div class="field" style="margin-top:12px;"><label>Reports Recipients (comma separated emails)</label><input type="text" name="email[reports_recipients]" value="{{ $email['reports_recipients'] ?? '' }}" placeholder="manager@company.com, audit@company.com"></div>
                    <button type="submit" class="btn btn-primary" style="margin-top:16px;">Save email settings</button>
                    <span style="font-size:12px; color:var(--ink-soft); margin-left:8px;">Saved in database (<code>settings</code> <code>key=email</code>)</span>
                </form>
                <div style="margin-top:18px; padding:14px; background:var(--sand-50); border:1px solid var(--line); border-radius:10px;">
                    <strong style="font-size:13px;">Send test email (verify saved config)</strong>
                    <p style="font-size:12px; color:var(--ink-soft); margin:4px 0 8px;">Sends a test email using the saved database config (OTP & Reports SMTP). Check inbox/spam. Shows progress until sent.</p>
                    <form id="testEmailForm" action="{{ route('settings.email.test') }}" method="POST" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
                        @csrf
                        <div class="field" style="margin-bottom:0; flex:1; min-width:220px;">
                            <label>To email *</label>
                            <input type="email" name="to" required placeholder="test@example.com" value="{{ $email['mail_from_address'] ?? $gen['contact_email'] ?? '' }}" style="padding:10px 12px; border:1.5px solid var(--line); border-radius:8px; width:100%;">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" id="testEmailInlineBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            Send Test Email
                        </button>
                        <a href="{{ route('settings.email.test.page') }}" class="btn btn-ghost btn-sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            Open dedicated page with progress
                        </a>
                    </form>
                    <div id="testEmailInlineProgress" style="display:none; margin-top:10px; padding:10px; background:var(--white); border:1px solid var(--line); border-radius:8px;">
                        <div style="display:flex; align-items:center; gap:8px; font-size:13px;">
                            <div style="width:16px; height:16px; border:2px solid var(--line); border-top-color:var(--terracotta-600); border-radius:50%; animation:spin 0.8s linear infinite;"></div>
                            <span id="testEmailInlineProgressText">Sending…</span>
                        </div>
                        <div style="margin-top:8px; height:6px; background:var(--line); border-radius:3px; overflow:hidden;">
                            <div id="testEmailInlineBar" style="height:100%; width:0%; background:var(--terracotta-600); transition:width 0.4s ease;"></div>
                        </div>
                    </div>
                </div>
            @else
                <h3>Notifications</h3>
                <form method="POST" action="{{ route('settings.store') }}" data-settings-form>
                    @csrf
                    <div class="toggle-row">
                        <div class="toggle-text">
                            <strong>Daily summary email</strong>
                            <span>Receive a summary of volume and commissions every day.</span>
                        </div>
                        <select name="notifications[email_daily_summary]" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                            <option value="1" {{ ($notif['email_daily_summary'] ?? '1') == 1 ? 'selected' : '' }}>On</option>
                            <option value="0" {{ ($notif['email_daily_summary'] ?? '') == 0 ? 'selected' : '' }}>Off</option>
                        </select>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-text">
                            <strong>SMS float alerts</strong>
                            <span>Alert the owner when agent float drops below a threshold.</span>
                        </div>
                        <select name="notifications[sms_float_alerts]" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                            <option value="1" {{ ($notif['sms_float_alerts'] ?? '0') == 1 ? 'selected' : '' }}>On</option>
                            <option value="0" {{ ($notif['sms_float_alerts'] ?? '0') == 0 ? 'selected' : '' }}>Off</option>
                        </select>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-text">
                            <strong>WhatsApp weekly reports</strong>
                            <span>Weekly performance report delivered to WhatsApp.</span>
                        </div>
                        <select name="notifications[whatsapp_reports]" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                            <option value="1" {{ ($notif['whatsapp_reports'] ?? '0') == 1 ? 'selected' : '' }}>On</option>
                            <option value="0" {{ ($notif['whatsapp_reports'] ?? '0') == 0 ? 'selected' : '' }}>Off</option>
                        </select>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-text">
                            <strong>Email on every transaction</strong>
                            <span>Send an email to the cash point for every detected transaction.</span>
                        </div>
                        <select name="notifications[email_transactions]" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                            <option value="1" {{ ($notif['email_transactions'] ?? '1') == 1 ? 'selected' : '' }}>On</option>
                            <option value="0" {{ ($notif['email_transactions'] ?? '') == 0 ? 'selected' : '' }}>Off</option>
                        </select>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-text">
                            <strong>Failed transaction alerts</strong>
                            <span>Notify immediately when a transaction fails to process.</span>
                        </div>
                        <select name="notifications[email_failed_txns]" style="padding:8px 10px;border:1.5px solid var(--line);border-radius:9px;font-size:13px;font-weight:600;background:var(--white);color:var(--coffee-700);">
                            <option value="1" {{ ($notif['email_failed_txns'] ?? '1') == 1 ? 'selected' : '' }}>On</option>
                            <option value="0" {{ ($notif['email_failed_txns'] ?? '') == 0 ? 'selected' : '' }}>Off</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:18px;">Save notification settings</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-settings-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => toast('Settings saved successfully.', 'success') });
            });
        });

        document.querySelectorAll('[data-cashpoint-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'PUT', done: () => toast('Cash point saved successfully.', 'success') });
            });
        });

        (function() {
            const form = document.getElementById('testEmailForm');
            const progress = document.getElementById('testEmailInlineProgress');
            const bar = document.getElementById('testEmailInlineBar');
            const text = document.getElementById('testEmailInlineProgressText');
            const btn = document.getElementById('testEmailInlineBtn');
            let timer = null, val = 0;
            function startInline() {
                if (!progress) return;
                progress.style.display = 'block';
                bar.style.width = '0%';
                val = 0;
                text.textContent = 'Sending test email…';
                btn.disabled = true;
                btn.style.opacity = '0.6';
                timer = setInterval(() => {
                    val = Math.min(90, val + Math.random()*15);
                    bar.style.width = val + '%';
                    if (val > 40) text.textContent = 'Connecting to SMTP…';
                    if (val > 70) text.textContent = 'Sending…';
                }, 350);
            }
            function stopInline(success, msg) {
                clearInterval(timer);
                if (bar) bar.style.width = '100%';
                if (text) text.textContent = success ? 'Done!' : 'Failed';
                setTimeout(() => { if (progress) progress.style.display = 'none'; }, success ? 800 : 1500);
                if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                toast(msg || (success ? 'Test email sent' : 'Failed'), success ? 'success' : 'error');
            }
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!form.reportValidity()) return;
                startInline();
                try {
                    const resp = await fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: new FormData(form) });
                    const data = await resp.json().catch(() => ({}));
                    stopInline(resp.ok && data.success, data.message || (resp.ok ? 'Test email sent' : 'Failed'));
                } catch (err) {
                    stopInline(false, 'Network error');
                }
            });
        })();
    </script>
@endsection