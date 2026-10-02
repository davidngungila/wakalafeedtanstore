@if ($twoFactorAlert)
    <div class="modal-backdrop" id="twoFactorAlertModal">
        <div class="popup" style="max-width:440px; width:100%; margin:auto;">
            <div class="modal-head">
                <h3>{{ ($twoFactorAlert['issue'] ?? null) === 'misconfigured' ? 'Two-factor authentication misconfigured' : 'Two-factor setup required' }}</h3>
                <button class="modal-close" onclick="closeModal('twoFactorAlertModal')">✕</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13.5px; color:var(--ink-soft); line-height:1.7; margin:0 0 14px;">{{ $twoFactorAlert['message'] ?? '' }}</p>
                <ul style="margin:0; padding-left:20px; font-size:13.5px; line-height:1.8; color:var(--ink-soft);">
                    <li><strong>Authenticator App</strong> — TOTP codes from your phone. Works without an SMS provider.</li>
                    <li><strong>SMS OTP</strong> — needs a verified mobile number and a configured SMS provider.</li>
                </ul>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" onclick="closeModal('twoFactorAlertModal')">Remind me later</button>
                <button type="button" class="btn btn-primary" data-two-factor-alert-setup>Set it up now</button>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('twoFactorAlertModal')?.classList.add('show');
        document.querySelector('[data-two-factor-alert-setup]')?.addEventListener('click', function () {
            closeModal('twoFactorAlertModal');
            openModal('chooseTwoFactorModal');
        });
    </script>
@endif