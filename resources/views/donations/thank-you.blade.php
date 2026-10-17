<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you for your donation · Donation System</title>
    @vite(['resources/css/dashboard.css', 'resources/js/app.js'])
</head>
<body class="donation-thank-you-page">
    <main class="donation-thank-you-card">
        <div class="donation-thank-you-icon" aria-hidden="true">
            <svg viewBox="0 0 64 64" fill="none">
                <circle cx="32" cy="32" r="29" />
                <path d="m19 33 9 9 18-20" />
            </svg>
        </div>
        <p class="donation-thank-you-eyebrow">Payment confirmed</p>
        <h1>Thank you for making a difference!</h1>
        <p class="donation-thank-you-copy">
            Your contribution is helping <strong>{{ $donation->campaign->title }}</strong> move closer to its goal.
        </p>
        <div class="donation-thank-you-amount">
            <span>Your donation</span>
            <strong>${{ number_format((float) $donation->amount, 2) }}</strong>
        </div>
        <p class="donation-thank-you-note">Your card payment was securely processed by Stripe.</p>
        <a class="donation-thank-you-link" href="{{ route('campaigns') }}">Back to campaigns</a>
    </main>
</body>
</html>
