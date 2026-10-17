@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <div class="form-heading">
        <span class="eyebrow">Welcome back</span>
        <h2>Sign in to your account</h2>
        <p>Pick up right where you left off.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            @error('email')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <div class="field-label-row">
                <label for="password">Password</label>
            </div>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            @error('password')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <label class="check-row" for="remember">
            <input id="remember" name="remember" type="checkbox" value="1">
            <span>Keep me signed in</span>
        </label>

        <button class="button button-primary" type="submit">Sign in <span aria-hidden="true">→</span></button>
    </form>

    <p class="form-switch">New to Northstar? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
