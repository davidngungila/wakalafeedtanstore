@extends('layouts.app')

@section('title', 'Send SMS')

@section('content')
    <div class="view-head">
        <div>
            <h2>Send SMS</h2>
            <p class="sub">Send single or bulk messages using the saved SMS provider settings.</p>
        </div>
        <div class="view-actions">
            <a href="{{ route('settings.sms') }}" class="btn btn-ghost">SMS settings</a>
        </div>
    </div>

    <div class="settings-layout">
        <div class="settings-panel" style="grid-column:1 / -1;">
            <div style="padding:18px; background:var(--sand-50); border:1px solid var(--line); border-radius:10px;">
                <h3>Send a single test SMS</h3>
                <p style="font-size:13px; color:var(--ink-soft); margin:4px 0 16px;">Uses the saved sender ID and token. Test sends may be charged by the provider.</p>
                <form data-sms-test-form method="POST" action="{{ route('settings.sms.send') }}">
                    @csrf
                    <div class="form-row">
                        <div class="field"><label>Phone number *</label><input type="text" name="to" required maxlength="30" placeholder="255716718040" inputmode="tel"></div>
                        <div class="field"><label>Message *</label><textarea name="text" required maxlength="1000" rows="3" placeholder="Write a test message…"></textarea></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Send test SMS</button>
                </form>
            </div>

            <div style="margin-top:20px; padding:18px; background:var(--sand-50); border:1px solid var(--line); border-radius:10px;">
                <h3>Send a bulk test SMS</h3>
                <p style="font-size:13px; color:var(--ink-soft); margin:4px 0 16px;">Separate up to {{ config('sms.outbound.max_bulk_recipients', 100) }} recipients with commas, spaces, or line breaks. The same message is sent to each recipient.</p>
                <form data-sms-test-form method="POST" action="{{ route('settings.sms.send-bulk') }}">
                    @csrf
                    <div class="field"><label>Recipients *</label><textarea name="recipients" required rows="2" placeholder="255716718040, 0716718041"></textarea></div>
                    <div class="field" style="margin-top:12px;"><label>Message *</label><textarea name="text" required maxlength="1000" rows="3" placeholder="Write a test message…"></textarea></div>
                    <button type="submit" class="btn btn-primary" style="margin-top:12px;">Send bulk test SMS</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-sms-test-form]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitForm(form, { method: 'POST', done: () => form.reset() });
            });
        });
    </script>
@endsection
