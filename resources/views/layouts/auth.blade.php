<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Northstar</title>
    @vite('resources/css/auth.css')
</head>
<body class="auth-page">
    <main class="auth-shell">
        <a class="brand" href="{{ url('/') }}" aria-label="Northstar home">
            <span class="brand-mark" aria-hidden="true">N</span>
            <span>northstar</span>
        </a>

        <section class="auth-content">
            <div class="auth-card">
                @yield('content')
            </div>
            <p class="legal-copy">By continuing, you agree to our <a href="#">Terms</a> and <a href="#">Privacy Policy</a>.</p>
        </section>
    </main>
</body>
</html>
