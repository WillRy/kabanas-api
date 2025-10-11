@extends('layouts.base')

@section('content')
    <p>Hello, {{ $user->name }}!</p>
    <p>Need to reset your password? No problems</p>
    <p>Your password reset code is:</p>
    <h2 style="text-align: center;">{{ $code }}</h2>
    <p>This code will expire in {{ $expiresIn }} minutes.</p>
@endsection
