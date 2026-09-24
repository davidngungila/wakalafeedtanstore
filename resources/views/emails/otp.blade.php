@extends('emails.layout')

@section('title', 'Your OTP Code')

@section('content')
    <div style="text-align:center; margin-bottom:20px;">
        <div style="display:inline-block; background:linear-gradient(135deg,#C2592B,#D4A24C); color:#fff; width:56px; height:56px; border-radius:14px; line-height:56px; font-size:22px; font-weight:700;">OTP</div>
        <h2 style="margin:12px 0 4px; font-family:'Raleway',sans-serif; color:#2A1B10; font-size:20px; letter-spacing:-0.01em;">Your OTP Code</h2>
        <p style="margin:0; color:#6B5A48; font-size:13px;">For <strong>{{ $email }}</strong> — valid for {{ $minutes }} minutes</p>
    </div>

    <div style="text-align:center; margin:22px 0;">
        <div style="display:inline-block; background:#FBF7EF; border:2px dashed #E4D7C2; border-radius:14px; padding:18px 28px;">
            <div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; color:#7A5C42; font-weight:700; margin-bottom:6px;">One-Time Password</div>
            <div style="font-size:32px; letter-spacing:0.28em; font-weight:800; color:#C2592B; font-family:'Courier New',ui-monospace,monospace;">{{ $code }}</div>
            <div style="font-size:11px; color:#A98968; margin-top:6px;">Enter this code to continue signing in</div>
        </div>
    </div>

    <div style="background:#F4ECDC; border:1px solid #E5DDD0; border-radius:10px; padding:12px 14px; font-size:12.5px; color:#6B5A48; line-height:1.6;">
        <strong style="color:#2A1B10;">Wakala Feedtan Store</strong><br>
        Your OTP code for account verification. If you did not request this, please ignore this email.
    </div>

    <p style="margin:16px 0 0; font-size:11.5px; color:#A98968; text-align:center;">
        Need help? Reply to this email or contact <strong>{{ \App\Models\Setting::where('key','general')->value('value')['contact_email'] ?? 'your administrator' }}</strong>
    </p>

    <p style="margin:8px 0 0; font-size:11.5px; color:#A98968; text-align:center;">
        This code is valid for <strong>{{ $minutes }}</strong> minutes. Our support team is available during business hours.
    </p>
@endsection
