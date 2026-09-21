@extends(auth()->check() ? 'layouts.app' : 'layouts.error')

@section('title', '404 — Page not found')

@if(auth()->check())
    @section('content')
        <div style="display:flex; align-items:center; justify-content:center; min-height:60vh; padding:24px;">
            <div style="text-align:center; max-width:560px; width:100%; background:#fff; border:1px solid var(--line); border-radius:20px; padding:56px 40px; box-shadow:0 20px 48px rgba(42,27,16,.12);">
                <div style="display:inline-flex; align-items:center; justify-content:center; width:92px; height:92px; border-radius:26px; background:var(--terracotta-100); color:var(--terracotta-600); font-size:40px; font-weight:800; letter-spacing:1px;">404</div>
                <h1 style="margin:24px 0 8px; font-size:24px; color:var(--coffee-800);">Page not found</h1>
                <p style="margin:0; color:var(--ink-soft); font-size:15px; line-height:1.6;">The page you were looking for could not be found. It may have been moved or removed.</p>
                <div style="margin-top:32px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                    <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" class="btn btn-primary">Go to dashboard</a>
                    <a href="javascript:history.length > 1 ? history.back() : window.location.href = '/' " class="btn btn-ghost">Go back</a>
                </div>
                <div style="margin-top:28px; font-size:12px; color:var(--coffee-300);">Wakala Feedtan Store</div>
            </div>
        </div>
    @endsection
@else
    @section('code', '404')
    @section('title', 'Page not found')
    @section('message', 'The page you were looking for could not be found. It may have been moved or removed.')
@endif