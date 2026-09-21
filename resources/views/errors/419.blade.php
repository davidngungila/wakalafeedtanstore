@php
    $code = '419';
    $title = 'Page expired';
    $tone = 'gold';
@endphp

@extends(auth()->check() ? 'layouts.app' : 'errors.standalone')

@section('title', $code . ' — ' . $title)

@section('content')
    @include('errors.partials.plain', [
        'code' => $code,
        'title' => $title,
        'message' => 'Your session has expired. Please refresh the page and try again.',
        'tone' => $tone,
    ])
@endsection