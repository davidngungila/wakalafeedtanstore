@php
    $code = '401';
    $title = 'Unauthorised';
    $tone = 'gold';
@endphp

@extends(auth()->check() ? 'layouts.app' : 'errors.standalone')

@section('title', $code . ' — ' . $title)

@section('content')
    @include('errors.partials.plain', [
        'code' => $code,
        'title' => $title,
        'message' => 'You are not authorised to view this page. Please sign in and try again.',
        'tone' => $tone,
    ])
@endsection