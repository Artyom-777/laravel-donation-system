@extends('layouts.auth')

@section('title', 'Create an account')

@section('content')
    <div class="form-heading">
        <span class="eyebrow">Your next chapter starts here</span>
        <h2>Create your account</h2>
        <p>Join us and make space for your best work.</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="auth-form">
        @csrf

        <div class="field">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
            @error('name')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="3" required>
            <span class="field-hint">Use at least 3 characters.</span>
            @error('password')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="3" required>
        </div>

        <button class="button button-primary" type="submit">Create account <span aria-hidden="true">→</span></button>
    </form>

    <p class="form-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
