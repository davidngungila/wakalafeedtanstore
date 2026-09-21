@php
    $code = '429';
    $title = 'Too many requests';
    $tone = 'danger';
@endphp

@extends(auth()->check() ? 'layouts.app' : 'errors.standalone')

@section('title', $code . ' — ' . $title)

@section('content')
    @include('errors.partials.plain', [
        'code' => $code,
        'title' => $title,
        'message' => 'You have made too many requests in a short time. Please wait a moment and try again.',
        'tone' => $tone,
    ])
@endsection