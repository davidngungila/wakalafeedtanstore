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
                Scan the QR with the <b>MobiControl</b> app on the phone, or type the device code manually:
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
<script>
    function copyConnectCode() {
        navigator.clipboard.writeText(document.getElementById('connectCode').textContent).then(() => toast('Device code copied.', 'success'));
    }
    function openConnectModal(code) {
        const img = document.getElementById('connectQr');
        if (img) {
            img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(code);
        }
        document.getElementById('connectCode').textContent = code;
        openModal('connectPopup');
    }
</script>