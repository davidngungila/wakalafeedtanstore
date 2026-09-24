@extends('layouts.app')

@section('title', 'Send Test Email')

@section('content')
    <div class="view-head">
        <div>
            <h2>Send Test Email</h2>
            <p class="sub">Verify saved database config (OTP & Reports SMTP) — shows progress until sent.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('settings.index', ['pane' => 'email']) }}" class="btn btn-ghost">← Back to Email Settings</a>
        </div>
    </div>

    <div class="panel" style="max-width:640px;">
        <div class="panel-head">
            <h3>Test Email — Verify Saved Config</h3>
            <span class="tag tag-terracotta">Saved in database</span>
        </div>
        <div class="panel-body">
            <p style="font-size:13px; color:var(--ink-soft); margin-bottom:16px;">Sends a test email using the <strong>saved database config</strong> (key <code>email</code> in <code>settings</code> table) for OTP & Reports. Check inbox/spam. Shows progress until complete.</p>

            <form id="testEmailPageForm" action="{{ route('settings.email.test') }}" method="POST" style="display:flex; flex-direction:column; gap:12px;">
                @csrf
                <div class="field" style="margin-bottom:0;">
                    <label>To email *</label>
                    <input type="email" name="to" required placeholder="test@example.com" value="{{ $prefill ?? '' }}" style="padding:12px 14px; border:1.5px solid var(--line); border-radius:8px; width:100%; font-size:14px;">
                </div>

                <div id="testProgress" style="display:none; padding:12px; background:var(--sand-100); border:1px solid var(--line); border-radius:8px; font-size:13px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:18px; height:18px; border:2px solid var(--line); border-top-color:var(--terracotta-600); border-radius:50%; animation:spin 0.8s linear infinite;"></div>
                        <span id="testProgressText">Sending test email… please wait</span>
                    </div>
                    <div style="margin-top:10px; height:6px; background:var(--line); border-radius:3px; overflow:hidden;">
                        <div id="testProgressBar" style="height:100%; width:0%; background:var(--terracotta-600); transition:width 0.4s ease;"></div>
                    </div>
                    <div style="font-size:11px; color:var(--ink-soft); margin-top:6px;" id="testProgressSub">Using saved database config (OTP & Reports SMTP) — do not close</div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary" id="testSendBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        Send Test Email
                    </button>
                    <a href="{{ route('settings.index', ['pane' => 'email']) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>

            <div id="testResult" style="display:none; margin-top:16px; padding:12px; border-radius:8px; font-size:13px;"></div>
        </div>
    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
@endsection

@section('scripts')
    <script>
        const testForm = document.getElementById('testEmailPageForm');
        const progress = document.getElementById('testProgress');
        const progressBar = document.getElementById('testProgressBar');
        const progressText = document.getElementById('testProgressText');
        const resultBox = document.getElementById('testResult');
        const sendBtn = document.getElementById('testSendBtn');
        let progressTimer = null;
        let progressValue = 0;

        function startProgress() {
            progress.style.display = 'block';
            progressBar.style.width = '0%';
            progressValue = 0;
            progressText.textContent = 'Sending test email… please wait';
            resultBox.style.display = 'none';
            sendBtn.disabled = true;
            sendBtn.style.opacity = '0.6';
            progressTimer = setInterval(() => {
                progressValue = Math.min(90, progressValue + Math.random() * 18);
                progressBar.style.width = progressValue + '%';
                if (progressValue > 30) progressText.textContent = 'Connecting to SMTP (' + (document.querySelector('input[name="to"]').value || '') + ')…';
                if (progressValue > 60) progressText.textContent = 'Sending via saved database config…';
                if (progressValue > 80) progressText.textContent = 'Almost done…';
            }, 400);
        }
        function stopProgress(success, message) {
            clearInterval(progressTimer);
            progressBar.style.width = '100%';
            progressText.textContent = success ? 'Done!' : 'Failed';
            setTimeout(() => { progress.style.display = 'none'; }, success ? 800 : 1500);
            sendBtn.disabled = false;
            sendBtn.style.opacity = '1';
            resultBox.style.display = 'block';
            resultBox.style.background = success ? 'var(--acacia-100)' : 'var(--danger-100)';
            resultBox.style.border = '1px solid ' + (success ? 'var(--acacia-600)' : 'var(--danger)');
            resultBox.style.color = success ? 'var(--acacia-600)' : 'var(--danger)';
            resultBox.textContent = message;
        }

        testForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!testForm.reportValidity()) return;
            const formData = new FormData(testForm);
            startProgress();
            try {
                const resp = await fetch(testForm.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.success) {
                    stopProgress(true, data.message || 'Test email sent — check inbox/spam. Config is saved in database.');
                    toast(data.message || 'Test email sent', 'success');
                } else {
                    stopProgress(false, data.message || 'Failed to send test email');
                    toast(data.message || 'Failed', 'error');
                }
            } catch (err) {
                stopProgress(false, 'Network error: ' + (err.message || 'failed'));
                toast('Network error', 'error');
            }
        });
    </script>
@endsection
