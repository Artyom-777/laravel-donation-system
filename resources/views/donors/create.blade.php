<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add New Donor · Donation System</title>
    @viteReactRefresh
    @vite(['resources/css/dashboard.css', 'resources/js/app.js'])
</head>
<body class="dashboard-page">
    <div class="dashboard-layout">
        <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation"></button>

        <aside class="dashboard-sidebar" data-dashboard-sidebar>
            <a class="dashboard-brand" href="{{ route('dashboard') }}" aria-label="Donation System dashboard">
                <span class="dashboard-brand-mark" aria-hidden="true">♥</span>
                <span>Donation System</span>
            </a>

            <nav class="sidebar-nav" id="dashboard-navigation" aria-label="Main navigation">
                <a class="nav-item" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
                    <span>Dashboard</span>
                </a>
                <a class="nav-item" href="{{ route('campaigns') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>
                    <span>Campaigns</span>
                </a>
                <a class="nav-item" href="{{ route('donations') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8z"/></svg>
                    <span>Donations</span>
                </a>
                <a class="nav-item nav-item-active" href="{{ route('donors') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg>
                    <span>Donors</span>
                </a>
                <a class="nav-item" href="{{ route('users') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Users</span>
                </a>
                <a class="nav-item" href="{{ route('dashboard') }}#statistics">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V11h4v9zM10 20V5h4v15zM16 20V8h4v12z"/></svg>
                    <span>Reports</span>
                </a>
                <a class="nav-item" href="{{ route('dashboard') }}#account">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 1.2 2.1 2.4.5 1.9-1 2 2-1 1.9.5 2.4L21 12l-2.1 1.2-.5 2.4 1 1.9-2 2-1.9-1-2.4.5L12 21l-1.2-2.1-2.4-.5-1.9 1-2-2 1-1.9-.5-2.4L3 12l2.1-1.2.5-2.4-.5-2.4 2-2 1.9 1 2.4-.5z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Settings</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="sidebar-user">
                    <div class="user-avatar" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="user-details">
                        <span class="user-name">{{ auth()->user()->name }}</span>
                        <span class="user-email">{{ auth()->user()->email }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="logout-button" type="submit" aria-label="Sign out" title="Sign out">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5.5A1.5 1.5 0 0 0 4 6.5v11A1.5 1.5 0 0 0 5.5 19H10"/><path d="m14 16 4-4-4-4M18 12H9"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="topbar">
                <div class="topbar-start">
                    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle navigation" aria-expanded="true" aria-controls="dashboard-navigation">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <label class="topbar-search donors-topbar-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                        <input type="search" placeholder="Search donors by name, email, or phone..." aria-label="Search donors by name, email, or phone">
                    </label>
                </div>
                @include('partials.account-menu')
            </header>

            <div class="dashboard-content donor-create-content">
                <section class="page-heading donor-create-page-heading" aria-labelledby="create-donor-heading">
                    <div>
                        <h1 id="create-donor-heading">Add New Donor</h1>
                    </div>
                    <nav class="campaign-breadcrumb" aria-label="Breadcrumb">
                        <a href="{{ route('dashboard') }}">Home</a>
                        <span aria-hidden="true">/</span>
                        <a href="{{ route('donors') }}">Donors</a>
                        <span aria-hidden="true">/</span>
                        <strong>Add New Donor</strong>
                    </nav>
                </section>

                <form class="panel donor-create-form" method="POST" action="{{ route('donors.store') }}" aria-label="Create donor form">
                    @csrf
                    @if ($errors->any())
                        <div class="campaign-form-errors" role="alert">
                            <strong>Please correct the following errors:</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <section class="donor-create-section" aria-labelledby="donor-information-heading">
                        <div class="donor-create-section-heading">
                            <span class="donor-create-section-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                            </span>
                            <span>
                                <h2 id="donor-information-heading">Donor Information</h2>
                                <p>Enter the donor's personal details.</p>
                            </span>
                        </div>

                        <div class="donor-create-fields">
                            <div class="campaign-form-field">
                                <label for="donor-name">Full Name <span aria-hidden="true">*</span></label>
                                <input id="donor-name" name="name" type="text" value="{{ old('name') }}" placeholder="Enter full name" autocomplete="name" required>
                            </div>
                            <div class="campaign-form-field">
                                <label for="donor-email">Email Address <span aria-hidden="true">*</span></label>
                                <input id="donor-email" name="email" type="email" value="{{ old('email') }}" placeholder="Enter email address" autocomplete="email" required>
                            </div>
                            <div class="campaign-form-field">
                                <label for="donor-phone">Phone Number</label>
                                <input id="donor-phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="Enter phone number" autocomplete="tel">
                            </div>
                            <div class="campaign-form-field">
                                <label for="donor-country">Country</label>
                                <select id="donor-country" name="country" autocomplete="country-name">
                                    <option value="">Select country</option>
                                    @foreach (['Australia', 'Canada', 'United Kingdom', 'United States'] as $country)
                                        <option value="{{ $country }}" @selected(old('country') === $country)>{{ $country }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="campaign-form-field">
                                <label for="donor-city">City</label>
                                <input id="donor-city" name="city" type="text" value="{{ old('city') }}" placeholder="Enter city" autocomplete="address-level2">
                            </div>
                            <div class="campaign-form-field">
                                <label for="donor-address">Address</label>
                                <textarea id="donor-address" name="address" rows="2" placeholder="Enter full address" autocomplete="street-address">{{ old('address') }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="donor-create-section donor-additional-information" aria-labelledby="donor-additional-heading">
                        <div class="donor-create-section-heading">
                            <span class="donor-create-section-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h7"/></svg>
                            </span>
                            <span>
                                <h2 id="donor-additional-heading">Additional Information</h2>
                                <p>Optional details about the donor.</p>
                            </span>
                        </div>
                        <div class="campaign-form-field">
                            <label for="donor-notes">Notes</label>
                            <textarea id="donor-notes" name="notes" rows="3" placeholder="Enter notes about the donor (optional)">{{ old('notes') }}</textarea>
                        </div>
                    </section>

                    <div class="campaign-form-actions">
                        <a class="campaign-cancel-button" href="{{ route('donors') }}">Cancel</a>
                        <button class="campaign-primary-button campaign-save-button" type="submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h12l4 4v14H3V3z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/></svg>
                            Save Donor
                        </button>
                    </div>
                </form>

                <footer class="dashboard-footer" id="account">
                    <span>Donation System <span aria-hidden="true">·</span> {{ now()->year }}</span>
                    <span>Signed in as {{ auth()->user()->name }}</span>
                </footer>
            </div>
        </main>
    </div>
</body>
</html>
