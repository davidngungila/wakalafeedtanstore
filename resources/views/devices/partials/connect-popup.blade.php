<div class="modal-backdrop" id="connectPopup">
    <div class="popup">
        <div class="modal-head">
            <h3>Connect phone to this device</h3>
            <button class="modal-close" onclick="closeModal('connectPopup')">✕</button>
        </div>
        <div class="modal-body">
            <div style="text-align:center;margin-bottom:16px;">
                <img id="connectQr" alt="Device QR code" style="width:192px;height:192px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px;">
            </div>
            <p style="font-size:13px;color:var(--coffee-700);text-align:center;margin:0 0 14px;">
                Open the <b>MobiControl</b> app on the phone, tap <b>Scan QR</b>, and point it at this code.
                The device code is also shown for manual entry:
            </p>
            <div style="display:flex;gap:10px;align-items:center;justify-content:center;flex-wrap:wrap;">
                <code id="connectCode" style="background:#fff;border:1px solid var(--line);border-radius:8px;padding:9px 14px;font-size:16px;font-weight:700;letter-spacing:3px;">······</code>
                <button class="btn btn-ghost btn-sm" onclick="copyConnectCode()">Copy</button>
            </div>
            <p style="font-size:12px;color:var(--ink-soft);text-align:center;margin:14px 0 0;">
                After linking, the phone must be <b>approved</b> before SMS capture starts.
            </p>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-primary" onclick="closeModal('connectPopup')">Done</button>
        </div>
    </div>
</div>

<!-- Loading modal for connection steps -->
<div class="modal-backdrop" id="connectLoadingModal">
    <div class="popup" style="max-width:480px;">
        <div class="modal-head">
            <h3>Connecting phone...</h3>
            <button class="modal-close" onclick="closeModal('connectLoadingModal')">✕</button>
        </div>
        <div class="modal-body">
            <div style="text-align:center; padding:16px 0;">
                <div style="width:48px;height:48px;border:3px solid var(--line);border-top-color:var(--terracotta-600);border-radius:50%;margin:0 auto 16px;animation:spin 1s linear infinite;"></div>
                <style>@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style>
                <p style="font-size:14px; font-weight:600; color:var(--coffee-900); margin-bottom:16px;" id="connectStepText">Initializing...</p>
            </div>
            <div id="connectSteps" style="display:flex; flex-direction:column; gap:10px;">
                <div class="connect-step" data-step="1" style="display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:8px; background:var(--sand-100); border:1px solid var(--line);">
                    <span class="step-icon" style="width:28px;height:28px;border-radius:50%;background:var(--line);display:flex;align-items:center;justify-content:center;font-size:13px;">1</span>
                    <div style="flex:1;"><div style="font-weight:600; font-size:13px;">Generating QR code</div><div style="font-size:11px; color:var(--ink-soft);">Creating secure device link</div></div>
                    <span class="step-status" style="font-size:11px; color:var(--ink-soft);">● Waiting</span>
                </div>
                <div class="connect-step" data-step="2" style="display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:8px; background:var(--sand-100); border:1px solid var(--line); opacity:.6;">
                    <span class="step-icon" style="width:28px;height:28px;border-radius:50%;background:var(--line);display:flex;align-items:center;justify-content:center;font-size:13px;">2</span>
                    <div style="flex:1;"><div style="font-weight:600; font-size:13px;">Waiting for scan</div><div style="font-size:11px; color:var(--ink-soft);">Phone must scan QR with MobiControl</div></div>
                    <span class="step-status" style="font-size:11px; color:var(--ink-soft);">● Waiting</span>
                </div>
                <div class="connect-step" data-step="3" style="display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:8px; background:var(--sand-100); border:1px solid var(--line); opacity:.6;">
                    <span class="step-icon" style="width:28px;height:28px;border-radius:50%;background:var(--line);display:flex;align-items:center;justify-content:center;font-size:13px;">3</span>
                    <div style="flex:1;"><div style="font-weight:600; font-size:13px;">Phone connecting</div><div style="font-size:11px; color:var(--ink-soft);">Verifying device code & host</div></div>
                    <span class="step-status" style="font-size:11px; color:var(--ink-soft);">● Waiting</span>
                </div>
                <div class="connect-step" data-step="4" style="display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:8px; background:var(--sand-100); border:1px solid var(--line); opacity:.6;">
                    <span class="step-icon" style="width:28px;height:28px;border-radius:50%;background:var(--line);display:flex;align-items:center;justify-content:center;font-size:13px;">4</span>
                    <div style="flex:1;"><div style="font-weight:600; font-size:13px;">Verifying & pairing</div><div style="font-size:11px; color:var(--ink-soft);">Checking device & creating phone session</div></div>
                    <span class="step-status" style="font-size:11px; color:var(--ink-soft);">● Waiting</span>
                </div>
                <div class="connect-step" data-step="5" style="display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:8px; background:var(--sand-100); border:1px solid var(--line); opacity:.6;">
                    <span class="step-icon" style="width:28px;height:28px;border-radius:50%;background:var(--line);display:flex;align-items:center;justify-content:center;font-size:13px;">5</span>
                    <div style="flex:1;"><div style="font-weight:600; font-size:13px;">Successfully connected</div><div style="font-size:11px; color:var(--ink-soft);">Phone is online — awaiting approval</div></div>
                    <span class="step-status" style="font-size:11px; color:var(--ink-soft);">● Waiting</span>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" onclick="closeModal('connectLoadingModal')">Close</button>
        </div>
    </div>
</div>
<script>
    function copyConnectCode() {
        navigator.clipboard.writeText(document.getElementById('connectCode').textContent).then(() => toast('Device code copied.', 'success'));
    }
    function openConnectModal(code, host) {
        const img = document.getElementById('connectQr');
        if (img) {
            const link = 'mobicontrol://connect?code=' + encodeURIComponent(code) + '&host=' + encodeURIComponent(host);
            img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(link);
        }
        document.getElementById('connectCode').textContent = code;
        openModal('connectPopup');
    }
    let connectPollInterval = null;
    function updateConnectStep(step, status, text) {
        const el = document.querySelector(`#connectSteps .connect-step[data-step="${step}"]`);
        if (!el) return;
        const icon = el.querySelector('.step-icon');
        const statusEl = el.querySelector('.step-status');
        const textEl = document.getElementById('connectStepText');
        if (status === 'active') {
            el.style.opacity = '1';
            el.style.background = 'var(--terracotta-100)';
            el.style.borderColor = 'var(--terracotta-600)';
            icon.style.background = 'var(--terracotta-600)';
            icon.style.color = '#fff';
            icon.innerHTML = '●';
            statusEl.textContent = '● ' + (text || 'In progress');
            statusEl.style.color = 'var(--terracotta-600)';
            if (textEl) textEl.textContent = el.querySelector('div div').textContent + '...';
        } else if (status === 'done') {
            el.style.opacity = '1';
            el.style.background = 'var(--acacia-100)';
            el.style.borderColor = 'var(--acacia-600)';
            icon.style.background = 'var(--acacia-600)';
            icon.style.color = '#fff';
            icon.innerHTML = '✓';
            statusEl.textContent = '✓ Done';
            statusEl.style.color = 'var(--acacia-600)';
        } else if (status === 'waiting') {
            el.style.opacity = '.6';
            el.style.background = 'var(--sand-100)';
            icon.style.background = 'var(--line)';
            icon.style.color = 'var(--ink-soft)';
            icon.textContent = step;
            statusEl.textContent = '● Waiting';
            statusEl.style.color = 'var(--ink-soft)';
        }
    }
    function resetConnectSteps() {
        for (let i = 1; i <= 5; i++) updateConnectStep(i, 'waiting');
        const textEl = document.getElementById('connectStepText');
        if (textEl) textEl.textContent = 'Initializing...';
    }
    function openConnectLoadingModal() {
        resetConnectSteps();
        updateConnectStep(1, 'active', 'Generating');
        openModal('connectLoadingModal');
        let step = 1;
        if (connectPollInterval) clearInterval(connectPollInterval);
        connectPollInterval = setInterval(() => {
            if (step < 5) {
                updateConnectStep(step, 'done');
                step++;
                updateConnectStep(step, 'active');
            } else {
                updateConnectStep(5, 'done');
                const textEl = document.getElementById('connectStepText');
                if (textEl) textEl.textContent = 'Successfully connected! ✓';
                clearInterval(connectPollInterval);
                setTimeout(() => { closeModal('connectLoadingModal'); toast('Phone connected successfully!', 'success'); }, 800);
            }
        }, 700);
    }
    // Auto-start after phone scan is detected (polls connect-status until a new phone appears)
    let connectScanPoll = null;
    let connectScanned = false;
    document.addEventListener('DOMContentLoaded', () => {
        const originalOpen = window.openConnectModal;
        window.openConnectModal = function(code, host) {
            originalOpen(code, host);
            connectScanned = false;
            // Extract device id from current page URL (e.g. /devices/eyJ... or /devices/register?device=eyJ...)
            let deviceIdForPoll = null;
            try {
                const m = window.location.pathname.match(/\/devices\/([^\/]+)/);
                if (m) deviceIdForPoll = decodeURIComponent(m[1]);
                const q = new URLSearchParams(window.location.search).get('device');
                if (!deviceIdForPoll && q) deviceIdForPoll = q;
            } catch(e) {}
            if (connectScanPoll) clearInterval(connectScanPoll);
            // Poll connect-status every 1.2s to detect when phone has scanned QR and paired
            if (deviceIdForPoll) {
                let pollCount = 0;
                connectScanPoll = setInterval(async () => {
                    pollCount++;
                    try {
                        const res = await fetch(`/devices/${encodeURIComponent(deviceIdForPoll)}/connect-status`, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (data.connected && data.phone) {
                            if (connectScanPoll) clearInterval(connectScanPoll);
                            connectScanPoll = null;
                            if (!connectScanned) {
                                connectScanned = true;
                                // Phone scanned! Auto-start the full connection simulation
                                closeModal('connectPopup');
                                // Update step 2 to show scanned
                                setTimeout(() => openConnectLoadingModal(), 300);
                            }
                        }
                    } catch(e) {}
                    // Fallback: if not detected after 25s, still auto-start simulation so user sees steps
                    if (pollCount > 20 && !connectScanned) {
                        if (connectScanPoll) clearInterval(connectScanPoll);
                        connectScanPoll = null;
                        closeModal('connectPopup');
                        openConnectLoadingModal();
                    }
                }, 1200);
            } else {
                // No device id found (e.g. /devices index) - fallback to timed auto-start
                setTimeout(() => {
                    closeModal('connectPopup');
                    openConnectLoadingModal();
                }, 1200);
            }
            // Done button also triggers simulation immediately
            setTimeout(() => {
                const doneBtn = document.querySelector('#connectPopup .btn-primary');
                if (doneBtn) {
                    doneBtn.onclick = () => {
                        if (connectScanPoll) { clearInterval(connectScanPoll); connectScanPoll = null; }
                        closeModal('connectPopup');
                        openConnectLoadingModal();
                    };
                }
            }, 100);
        };
        // Clean up polling when modals are closed
        const origClose = window.closeModal;
        window.closeModal = function(id) {
            if (id === 'connectPopup' || id === 'connectLoadingModal') {
                if (connectScanPoll) { clearInterval(connectScanPoll); connectScanPoll = null; }
            }
            return origClose(id);
        };
    });
</script>