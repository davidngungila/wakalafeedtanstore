@if ($credentialsFlash)
    <div class="modal-backdrop" id="credentialsModal">
        <div class="popup">
            <div class="modal-head">
                <h3>Device code</h3>
                <button class="modal-close" onclick="closeModal('credentialsModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:var(--coffee-700);margin:0 0 12px 0;">Copy it now — shown only once.</p>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Code</span>
                    <code id="credFlashCode" style="background:#fff;border:1px solid var(--line);border-radius:8px;padding:8px 12px;font-size:13px;letter-spacing:2px;">{{ $credentialsFlash['device_code'] }}</code>
                    <button class="btn btn-ghost btn-sm" onclick="copyFlash('credFlashCode', 'Device code copied.')">Copy</button>
                </div>
                <p style="font-size:12px;color:var(--ink-soft);margin:12px 0 0 0;">Enter this code in the MobiControl app on the phone. The device starts ingesting SMS once approved.</p>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-primary" onclick="closeModal('credentialsModal')">Got it</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => openModal('credentialsModal'));
    </script>
@endif