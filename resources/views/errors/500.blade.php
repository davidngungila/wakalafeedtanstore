@php
    $code = '500';
    $title = 'Something went wrong';
    $tone = 'danger';
@endphp

@extends(auth()->check() ? 'layouts.app' : 'errors.standalone')

@section('title', $code . ' — ' . $title)

@section('content')
    @include('errors.partials.plain', [
        'code' => $code,
        'title' => $title,
        'message' => 'An unexpected error occurred on our servers. Please try again in a moment.',
        'tone' => $tone,
    ])
@endsection