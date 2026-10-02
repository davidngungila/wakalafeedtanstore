@if ($errors->any())
    <div id="authErrorModal" role="alertdialog" aria-modal="true" aria-labelledby="authErrorTitle" style="position:fixed;inset:0;background:rgba(36,20,8,.55);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;padding:24px;z-index:1000;">
        <div style="width:100%;max-width:420px;background:var(--sand-50);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);overflow:hidden;">
            <div style="display:flex;align-items:center;gap:12px;padding:22px 26px;border-bottom:1px solid var(--line);">
                <span style="width:38px;height:38px;border-radius:11px;flex:none;display:flex;align-items:center;justify-content:center;background:var(--danger-100);color:var(--danger);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:20px;height:20px;"><circle cx="12" cy="12" r="9"></circle><line x1="12" y1="7.5" x2="12" y2="12.5"></line><line x1="12" y1="16" x2="12" y2="16.01"></line></svg>
                </span>
                <h2 id="authErrorTitle" style="margin:0;font-size:17px;color:var(--coffee-900);">Sign-in problem</h2>
            </div>
            <div style="padding:22px 26px;">
                <ul style="margin:0;padding-left:18px;font-size:13.5px;line-height:1.8;color:var(--ink);">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <div style="display:flex;justify-content:flex-end;padding:16px 26px;border-top:1px solid var(--line);">
                <button type="button" id="authErrorClose" class="btn" style="width:auto;margin:0;">Try again</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const modal = document.getElementById('authErrorModal');
            const dismiss = () => modal?.remove();
            document.getElementById('authErrorClose')?.addEventListener('click', dismiss);
            modal?.addEventListener('click', function (e) { if (e.target === modal) dismiss(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') dismiss(); });
        })();
    </script>
@endif