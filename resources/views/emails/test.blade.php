@extends('emails.layout')

@section('title', 'Test Email')

@section('content')
    <div style="text-align:center; margin-bottom:18px;">
        <div style="display:inline-block; background:linear-gradient(135deg,#5E6E3F,#7A8450); color:#fff; width:52px; height:52px; border-radius:12px; line-height:52px; text-align:center;">
            <span style="font-size:20px;">✓</span>
        </div>
        <h2 style="margin:10px 0 4px; font-family:'Raleway',sans-serif; color:#2A1B10; font-size:18px;">Test Email — Success</h2>
        <p style="margin:0; color:#6B5A48; font-size:13px;">Your email settings are working</p>
    </div>

    <div style="background:#F4ECDC; border:1px solid #E5DDD0; border-radius:10px; padding:14px; font-size:13px; color:#2A1B10; line-height:1.6;">
        <p style="margin:0 0 8px;">This is a <strong>test email</strong> from <strong>{{ config('app.name', 'Wakala Feedtan Store') }}</strong>.</p>
        <p style="margin:0 0 8px; color:#6B5A48;">If you received this at <strong>{{ $to }}</strong>, your saved database config (<code>settings</code> <code>key=email</code> — SMTP for <strong>OTP</strong> &amp; <strong>Reports</strong>) is correct.</p>
        <p style="margin:0; font-size:12px; color:#7A5C42;">Sent at {{ now()->format('Y-m-d H:i:s') }} — from {{ config('mail.from.address') }} ({{ config('mail.from.name') }}) via {{ config('mail.mailers.smtp.host') }}:{{ config('mail.mailers.smtp.port') }}</p>
    </div>

    <div style="margin-top:14px; background:#E2E7D4; border:1px solid #CBD5B8; border-radius:8px; padding:10px 12px; font-size:12px; color:#5E6E3F;">
        <strong>System format</strong> — Colors <span style="display:inline-block; width:12px; height:12px; background:#C2592B; border-radius:2px; vertical-align:middle;"></span> Terracotta, <span style="display:inline-block; width:12px; height:12px; background:#5E6E3F; border-radius:2px; vertical-align:middle;"></span> Acacia, <span style="display:inline-block; width:12px; height:12px; background:#D4A24C; border-radius:2px; vertical-align:middle;"></span> Gold — Font <strong>Raleway</strong> — as used across dashboard, float and reconciliation.
    </div>
@endsection
