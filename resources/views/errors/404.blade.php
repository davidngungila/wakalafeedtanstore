@php
    $code = '404';
    $title = 'Page not found';
    $tone = 'terracotta';
@endphp

@extends(auth()->check() ? 'layouts.app' : 'errors.standalone')

@section('title', $code . ' — ' . $title)

@section('content')
    @include('errors.partials.plain', [
        'code' => $code,
        'title' => $title,
        'message' => 'The page you were looking for could not be found. It may have been moved or removed.',
        'tone' => $tone,
    ])
@endsection