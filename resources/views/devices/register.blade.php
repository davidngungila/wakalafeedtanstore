@extends('layouts.app')

@section('title', 'Register device')

@section('content')
    <div class="view-head">
        <div>
            <h2>Register device</h2>
            <p class="sub">Three steps: enter the details, connect the phone, then authorize it.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('devices.index') }}" class="btn btn-ghost">← Back to devices</a>
        </div>
    </div>

    @if ($resume)
        <div class="table-card" style="margin-bottom:18px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="font-size:13.5px;color:var(--coffee-700);">
                <b>Resuming registration</b> — <span id="resumeName">{{ $resume['name'] }}</span>
                @if ($resume['status'] === 'active')
                    (already active)
                @elseif ($resume['connected'])
                    (phone connected)
                @else
                    (waiting for phone)
                @endif
            </div>
            <a href="{{ route('devices.register') }}" class="btn btn-ghost btn-sm">Start a new registration</a>
        </div>
    @endif

    <div class="table-card" style="padding:22px 26px;">
        <div class="wizard-steps" style="display:flex;align-items:center;justify-content:center;gap:0;">
            <div class="wstep" data-step="1">
                <div class="wstep-num">1</div>
                <span>Details</span>
            </div>
            <div class="wstep-line"></div>
            <div class="wstep" data-step="2">
                <div class="wstep-num">2</div>
                <span>Connect</span>
            </div>
            <div class="wstep-line"></div>
            <div class="wstep" data-step="3">
                <div class="wstep-num">3</div>
                <span>Authorize</span>
            </div>
        </div>

        <hr style="border:none;border-top:1.5px solid var(--line);margin:18px 0 0;">

        {{-- Step 1: details --}}
        <div class="wizard-panel" id="stepPanel1">
            <div style="padding:24px 0 6px;">
                <h3 style="margin:0 0 6px;">Phone details</h3>
                <p style="margin:0;font-size:13px;color:var(--ink-soft);">The technical details (model, Android &amp; app version) will be filled in automatically by the app the moment the phone connects.</p>
            </div>
            <form id="wizardForm" data-device-form action="{{ route('devices.store') }}">
                <div style="padding:18px 0 4px;">
                    <div class="form-row">
                        @include('devices.partials.network-picker', ['networks' => $networks, 'pickerKey' => 'reg'])
                        <div class="field">
                            <label>Device name</label>
                            <input type="text" name="name" placeholder="e.g. Samsung A15" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Phone number</label>
                            <input type="text" name="phone_number" placeholder="0712345678">
                        </div>
                        <div class="field">
                            <label>SIM number (ICCID)</label>
                            <input type="text" name="sim_number" placeholder="SIM ICCID or label">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>Branch</label>
                            <input type="text" name="branch" placeholder="e.g. Moshi">
                        </div>
                    </div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:14px;border-top:1.5px solid var(--line);">
                    <a href="{{ route('devices.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Continue to connect</button>
                </div>
            </form>
        </div>

        {{-- Step 2: connect --}}
        <div class="wizard-panel" id="stepPanel2" hidden>
            <div style="padding:24px 0 6px;">
                <h3 style="margin:0 0 6px;">Connect the phone</h3>
                <p style="margin:0;font-size:13px;color:var(--ink-soft);">Open the <b>MobiControl</b> app on the phone, tap <b>Scan QR</b> and point it at the code below — or type the device code manually. The phone <b>must</b> connect using this device code before it can be authorized.</p>
            </div>
            <div style="display:flex;gap:34px;align-items:center;justify-content:center;flex-wrap:wrap;padding:24px 0 6px;">
                <div style="text-align:center;">
                    <img id="connectQr" alt="Device QR code" style="width:196px;height:196px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px;">
                </div>
                <div style="max-width:340px;flex:1;min-width:240px;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--ink-soft);margin-bottom:8px;">Device code</div>
                    <div style="display:flex;gap:10px;align-items:center;margin-bottom:16px;">
                        <code id="connectCode" style="background:#fff;border:1.5px solid var(--line);border-radius:10px;padding:10px 16px;font-size:19px;font-weight:800;letter-spacing:4px;color:var(--coffee-800);">······</code>
                        <button class="btn btn-ghost btn-sm" onclick="copyConnectCode()">Copy</button>
                    </div>
                    <div id="connectStatus" class="empty-state" style="border:1.5px dashed var(--line);border-radius:12px;padding:16px;text-align:left;">
                        <b style="font-size:14px;color:var(--coffee-800);">Waiting for the phone…</b>
                        <p style="margin:6px 0 0;font-size:13px;color:var(--ink-soft);">As soon as the app scans the QR (or enters the code), its model and version details will appear below.</p>
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;gap:10px;padding-top:14px;border-top:1.5px solid var(--line);">
                <button type="button" class="btn btn-ghost" onclick="goStep(1)">← Back to details</button>
                <button type="button" class="btn btn-primary" id="toStep3" onclick="goStep(3)" disabled>Continue to authorize</button>
            </div>
        </div>

        {{-- Step 3: authorize --}}
        <div class="wizard-panel" id="stepPanel3" hidden>
            <div style="padding:24px 0 6px;">
                <h3 style="margin:0 0 6px;">Authorize the device</h3>
                <p style="margin:0;font-size:13px;color:var(--ink-soft);">The phone reported the details below. Once authorized it becomes <b>active</b> and starts pushing SMS automatically.</p>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;padding:18px 0 8px;">
                <div>
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--ink-soft);margin-bottom:8px;">Phone (from the app)</div>
                    <div class="receipt" id="phoneReceipt"></div>
                </div>
                <div>
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--ink-soft);margin-bottom:8px;">Device record</div>
                    <div class="receipt" id="deviceReceipt"></div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;gap:10px;padding-top:14px;border-top:1.5px solid var(--line);">
                <button type="button" class="btn btn-ghost" onclick="goStep(2)">← Back to connect</button>
                <span>
                    <button type="button" class="btn btn-primary" id="authorizeBtn" onclick="authorizeDevice()">Authorize &amp; activate</button>
                    <a href="{{ route('devices.index') }}" class="btn btn-ghost" id="goDeviceBtn" hidden>Go to devices</a>
                </span>
            </div>
        </div>
    </div>

    <style>
        .wstep { display:flex; flex-direction:column; align-items:center; gap:8px; font-size:13px; font-weight:700; color:var(--ink-soft); }
        .wstep .wstep-num { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; border:2px solid var(--line); color:var(--ink-soft); background:var(--white); transition:all .2s; }
        .wstep.active { color:var(--terracotta-700); }
        .wstep.active .wstep-num { border-color:var(--terracotta-600); background:var(--terracotta-600); color:#fff; }
        .wstep.done .wstep-num { border-color:var(--acacia-600); background:var(--acacia-600); color:#fff; }
        .wstep-line { width:72px; height:2px; background:var(--line); margin:0 12px 24px; }
        .wstep-line.done { background:var(--acacia-500); }
        .wizard-panel { max-width:860px; margin:0 auto; }
    </style>
@endsection

@section('scripts')
    @php $resumeJson = $resume ?? null; @endphp
    <script>
        const RESUME = {{ $resumeJson ? json_encode($resumeJson) : 'null' }};

        const step = { current: 1, connected: false };
        let device = { id: null, code: null, connectStatusUrl: null, approveUrl: null, devicePageUrl: null };
        let pollTimer = null;

        function copyConnectCode() {
            const code = device.code || (RESUME ? RESUME.device_code : '');
            navigator.clipboard.writeText(code).then(() => toast('Device code copied.', 'success'));
        }

        function goStep(n) {
            step.current = n;
            document.querySelectorAll('.wizard-panel').forEach(p => p.hidden = true);
            document.getElementById('stepPanel' + n).hidden = false;

            document.querySelectorAll('.wstep').forEach(w => {
                const s = parseInt(w.dataset.step, 10);
                w.classList.toggle('active', s === n);
                w.classList.toggle('done', s < n);
            });
            document.querySelectorAll('.wstep-line').forEach((l, i) => {
                l.classList.toggle('done', i + 2 <= n);
            });

            if (n === 2) {
                startPolling();
            } else {
                stopPolling();
            }
        }

        function renderConnect(code, host) {
            document.getElementById('connectCode').textContent = code;
            const link = 'mobicontrol://connect?code=' + encodeURIComponent(code) + '&host=' + encodeURIComponent(host);
            document.getElementById('connectQr').src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(link);
        }

        function startPolling() {
            stopPolling();
            pollTimer = setInterval(pollConnectStatus, 4000);
            pollConnectStatus();
        }

        function stopPolling() {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }

        async function pollConnectStatus() {
            if (!device.connectStatusUrl) return;
            try {
                const response = await fetch(device.connectStatusUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await response.json();
                if (data.connected) {
                    step.connected = true;
                    stopPolling();
                    markConnected(data);
                } else {
                    document.getElementById('connectStatus').innerHTML =
                        '<b style="font-size:14px;color:var(--coffee-800);">Waiting for the phone…</b>' +
                        '<p style="margin:6px 0 0;font-size:13px;color:var(--ink-soft);">Open MobiControl and scan the QR (code <b>' + device.code + '</b>) on the phone.</p>';
                }
            } catch (err) {
                /* transient — next poll will retry */
            }
        }

        function markConnected(data) {
            const p = data.phone || {};
            const model = p.model || data.device.model || 'Unknown model';
            document.getElementById('connectStatus').innerHTML =
                '<b style="font-size:14px;color:var(--acacia-700);">✔ Phone connected</b>' +
                '<p style="margin:6px 0 0;font-size:13px;color:var(--ink-soft);">' + escapeHtml(model) + ' paired with device code <b>' + escapeHtml(device.code) + '</b>. You can now authorize it.</p>';
            document.getElementById('toStep3').disabled = false;
            toast('Phone connected. Review the details and authorize.', 'success');
            // Auto-advance to Authorize step after being connected (user requested: after being connected must auto Details -> Connect -> Authorize)
            setTimeout(() => {
                fillReceipts(data);
                goStep(3);
            }, 600);
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function receiptRow(label, value) {
            const row = document.createElement('div');
            row.className = 'receipt-row';
            const s = document.createElement('span');
            s.textContent = label;
            const b = document.createElement('b');
            b.textContent = (value === null || value === undefined || value === '') ? '—' : value;
            row.appendChild(s);
            row.appendChild(b);
            return row;
        }

        function fillReceipts(data) {
            const deviceEl = document.getElementById('deviceReceipt');
            deviceEl.innerHTML = '';
            deviceEl.appendChild(receiptRow('Device name', data.device.name));
            deviceEl.appendChild(receiptRow('Device code', data.device.device_code));
            deviceEl.appendChild(receiptRow('Status', data.device.status));
            deviceEl.appendChild(receiptRow('Model', data.device.model || '—'));

            const phoneEl = document.getElementById('phoneReceipt');
            phoneEl.innerHTML = '';
            const p = data.phone;
            if (p) {
                phoneEl.appendChild(receiptRow('Model', p.model));
                phoneEl.appendChild(receiptRow('Device UID', p.device_uid));
                phoneEl.appendChild(receiptRow('Android version', p.android_version));
                phoneEl.appendChild(receiptRow('App version', p.app_version));
                phoneEl.appendChild(receiptRow('IP', p.ip));
                phoneEl.appendChild(receiptRow('Last seen', p.last_seen_at ? new Date(p.last_seen_at).toLocaleString() : '—'));
            } else {
                phoneEl.appendChild(receiptRow('', 'No phone details yet.'));
            }
        }

        async function authorizeDevice() {
            if (!device.approveUrl) return;
            const btn = document.getElementById('authorizeBtn');
            btn.disabled = true;
            try {
                const response = await fetch(device.approveUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    toast('Device authorized and activated.', 'success');
                    document.getElementById('authorizeBtn').hidden = true;
                    document.getElementById('goDeviceBtn').href = device.devicePageUrl;
                    document.getElementById('goDeviceBtn').hidden = false;
                    document.getElementById('goDeviceBtn').textContent = 'Go to device';
                } else {
                    toast(data.message || 'Failed to authorize device.', 'error');
                    btn.disabled = false;
                }
            } catch (err) {
                toast('Failed to authorize device.', 'error');
                btn.disabled = false;
            }
        }

        function beginStepForResume() {
            const r = RESUME;
            device = {
                id: r.id,
                code: r.device_code,
                connectStatusUrl: r.connect_status_url,
                approveUrl: r.approve_url,
                devicePageUrl: r.device_page_url,
            };
            renderConnect(r.device_code, window.location.origin);

            if (r.status === 'active') {
                document.getElementById('toStep3').disabled = false;
                goStep(3);
                fillReceipts({ device: { name: r.name, device_code: r.device_code, status: r.status, model: '' }, phone: null });
                document.getElementById('phoneReceipt').innerHTML = '';
                document.getElementById('phoneReceipt').appendChild(receiptRow('', 'Device already authorized.'));
                document.getElementById('authorizeBtn').hidden = true;
                document.getElementById('goDeviceBtn').href = r.device_page_url;
                document.getElementById('goDeviceBtn').hidden = false;
                document.getElementById('goDeviceBtn').textContent = 'Go to device';
                return;
            }

            if (r.connected) {
                stopPolling();
                pollConnectStatus();
                goStep(3);
            } else {
                goStep(2);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (RESUME) {
                beginStepForResume();
                return;
            }

            document.querySelectorAll('[data-device-form]').forEach(form => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    submitForm(form, {
                        done: (data) => {
                            device = {
                                id: data.device_id,
                                code: data.device_code,
                                connectStatusUrl: data.connect_status_url,
                                approveUrl: data.approve_url,
                                devicePageUrl: data.device_page_url,
                            };
                            renderConnect(data.device_code, window.location.origin);
                            goStep(2);
                            pollConnectStatus();
                        },
                    });
                });
            });

            goStep(1);
        });
    </script>
@endsection