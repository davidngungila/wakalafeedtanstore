@extends('emails.layout')

@section('title', 'Daily Report — ' . $dayStr)

@section('content')
    <div style="text-align:center; margin-bottom:16px;">
        <div style="display:inline-block; background:linear-gradient(135deg,#C2592B,#D4A24C); color:#fff; width:48px; height:48px; border-radius:12px; line-height:48px; font-size:18px; font-weight:700;">DR</div>
        <h2 style="margin:10px 0 4px; font-family:'Raleway',sans-serif; color:#2A1B10; font-size:18px;">Daily Report — {{ $day->format('l, d M Y') }}</h2>
        <p style="margin:0; color:#6B5A48; font-size:12.5px;">Attached PDF with full opening, transactions, float and reconciliation for {{ $dayStr }}</p>
    </div>

    <div style="background:#F4ECDC; border:1px solid #E5DDD0; border-radius:10px; padding:14px; font-size:12.5px; color:#2A1B10; line-height:1.6;">
        <p style="margin:0 0 8px;">Daily report for <strong>{{ $dayStr }}</strong> — <strong>{{ $business['name'] }}</strong></p>
        <p style="margin:0; color:#6B5A48; font-size:12px;">The PDF attached contains opening cash/float, customer transactions, float movements and reconciliation for the day. System format — colors Terracotta <span style="display:inline-block; width:10px; height:10px; background:#C2592B; border-radius:2px; vertical-align:middle;"></span>, Acacia <span style="display:inline-block; width:10px; height:10px; background:#5E6E3F; border-radius:2px; vertical-align:middle;"></span>, Gold <span style="display:inline-block; width:10px; height:10px; background:#D4A24C; border-radius:2px; vertical-align:middle;"></span> — font <strong>Raleway</strong>.</p>
    </div>

    <p style="margin:14px 0 0; font-size:11px; color:#A98968; text-align:center;">This report was sent automatically at 23:59 from {{ $business['name'] }} — {{ $business['address'] }}</p>
@endsection
