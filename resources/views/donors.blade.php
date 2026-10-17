<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Donors · Donation System</title>
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
                        <input type="search" placeholder="Search donors by name, email, or phone..." aria-label="Search donors by name, email, or phone" data-donor-search>
                    </label>
                </div>
                @include('partials.account-menu')
            </header>

            <div class="dashboard-content donors-content">
                <section class="page-heading donors-page-heading" aria-labelledby="donors-heading">
                    <div>
                        <h1 id="donors-heading">Donors</h1>
                        <p>View and manage all donors who have supported your campaigns.</p>
                    </div>
                    <div class="donors-heading-actions">
                        <nav class="campaign-breadcrumb" aria-label="Breadcrumb">
                            <a href="{{ route('dashboard') }}">Home</a>
                            <span aria-hidden="true">/</span>
                            <strong>Donors</strong>
                        </nav>
                        <a class="campaign-primary-button donors-add-button" href="{{ route('donors.create') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M17 8v6M14 11h6"/></svg>
                            Add New Donor
                        </a>
                    </div>
                </section>

                @if (session('status'))
                    <p class="campaign-success-message" role="status">{{ session('status') }}</p>
                @endif
                <section class="donor-stats-grid" aria-label="Donor summary">
                    <article class="donor-stat-card donor-stat-blue">
                        <span class="donor-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg></span>
                        <div><span>Total Donors</span><strong>{{ number_format($donorCount) }}</strong><small>Registered donors</small></div>
                    </article>
                    <article class="donor-stat-card donor-stat-green">
                        <span class="donor-stat-icon" aria-hidden="true">$</span>
                        <div><span>Total Donations</span><strong>${{ number_format((float) $donationTotal, 2) }}</strong><small>From all donors</small></div>
                    </article>
                    <article class="donor-stat-card donor-stat-purple">
                        <span class="donor-stat-icon donor-stat-heart" aria-hidden="true">♥</span>
                        <div><span>Repeat Donors</span><strong>{{ number_format($repeatDonorCount) }}</strong><small>More than one donation</small></div>
                    </article>
                    <article class="donor-stat-card donor-stat-orange">
                        <span class="donor-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M17 8v6M14 11h6"/></svg></span>
                        <div><span>New Donors</span><strong>{{ number_format($newDonorCount) }}</strong><small>Added in the last 30 days</small></div>
                    </article>
                </section>

                <section class="panel donors-panel" aria-labelledby="donors-list-heading">
                    <div class="donor-filters">
                        <label class="donor-table-search">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                            <input type="search" placeholder="Search donors..." aria-label="Search donors" data-donor-search>
                        </label>
                        <label class="donor-filter-select">
                            <span class="visually-hidden">Filter by country</span>
                            <select aria-label="Filter by country" data-donor-country-filter>
                                <option value="">All Countries</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country }}">{{ $country }}</option>
                                @endforeach
                            </select>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </label>
                        <label class="donor-filter-select">
                            <span class="visually-hidden">Filter by campaign</span>
                            <select aria-label="Filter by campaign" data-donor-campaign-filter>
                                <option value="">All Campaigns</option>
                                @foreach ($campaigns as $campaign)
                                    <option value="{{ $campaign }}">{{ $campaign }}</option>
                                @endforeach
                            </select>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </label>
                        <label class="donor-filter-select">
                            <span class="visually-hidden">Filter by donor type</span>
                            <select aria-label="Filter by donor type" data-donor-type-filter>
                                <option value="">All Donor Types</option>
                                <option>Recurring</option>
                                <option>One-time</option>
                            </select>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </label>
                        <div class="donor-date-filter">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                            <label><span class="visually-hidden">Start date</span><input type="date" aria-label="Start date" data-donor-start-date></label>
                            <span aria-hidden="true">–</span>
                            <label><span class="visually-hidden">End date</span><input type="date" aria-label="End date" data-donor-end-date></label>
                        </div>
                        <button class="campaign-primary-button donor-filter-button" type="button" data-donor-filter-button>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6.5 7.5V19l-3 1v-7.5z"/></svg>
                            Filter
                        </button>
                    </div>

                    <div class="donor-table-heading">
                        <h2 id="donors-list-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg> Donors List</h2>
                        <span data-donor-range>Showing 1–{{ min(5, $donors->count()) }} of {{ $donors->count() }} donors</span>
                    </div>

                    <div class="table-scroll donor-table-scroll">
                        <table class="donor-table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Phone</th>
                                    <th scope="col">Total Donations</th>
                                    <th scope="col">Donation Count</th>
                                    <th scope="col">Last Donation</th>
                                    <th scope="col">Country</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody data-donor-rows>
                                @foreach ($donors as $index => $donor)
                                    @php
                                        $lastDonation = $donor->latestDonation;
                                        $donorCountry = $donor->country ?? '';
                                        $donorCampaign = $lastDonation?->campaign?->title ?? '';
                                        $donorDate = $lastDonation?->donated_at ?? $donor->created_at;
                                        $donorType = $donor->donations_count > 1 ? 'Recurring' : 'One-time';
                                    @endphp
                                    <tr
                                        data-donor-row
                                        data-donor="{{ strtolower($donor->name.' '.$donor->email.' '.($donor->phone ?? '')) }}"
                                        data-country="{{ $donorCountry }}"
                                        data-campaign="{{ $donorCampaign }}"
                                        data-type="{{ $donorType }}"
                                        data-date="{{ $donorDate?->toDateString() }}"
                                    >
                                        <td data-label="#">{{ $index + 1 }}</td>
                                        <td data-label="Name">
                                            <span class="donor-name-cell">
                                                <span class="donor-avatar donor-avatar-{{ ['rose', 'blue', 'cyan', 'teal', 'indigo', 'sky', 'slate'][$donor->id % 7] }}">{{ strtoupper(mb_substr($donor->name, 0, 2)) }}</span>
                                                <strong>{{ $donor->name }}</strong>
                                            </span>
                                        </td>
                                        <td data-label="Email"><a href="mailto:{{ $donor->email }}">{{ $donor->email }}</a></td>
                                        <td data-label="Phone">{{ $donor->phone ?: '—' }}</td>
                                        <td data-label="Total Donations">${{ number_format((float) ($donor->total_donations ?? 0), 2) }}</td>
                                        <td data-label="Donation Count">{{ $donor->donations_count }}</td>
                                        <td data-label="Last Donation"><span class="donor-last-date">{{ $lastDonation ? $donorDate->format('M d, Y') : '—' }}@if ($lastDonation)<small>{{ $donorDate->format('h:i A') }}</small>@endif</span></td>
                                        <td data-label="Country"><span class="donor-country">{{ $donorCountry ?: '—' }}</span></td>
                                        <td data-label="Type"><span class="donor-type donor-type-{{ strtolower(str_replace('-', '', $donorType)) }}">{{ $donorType }}</span></td>
                                        <td data-label="Actions"><button class="donation-row-action" type="button" aria-label="Actions for {{ $donor->name }}" title="More actions">···</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="donor-empty-state" data-donor-empty @if ($donors->isNotEmpty()) hidden @endif>
                            {{ $donors->isEmpty() ? 'No donors have been added yet.' : 'No donors match the selected filters.' }}
                        </p>
                    </div>

                    <div class="donor-table-footer">
                        <span data-donor-footer-range>Showing 1 to {{ min(5, $donors->count()) }} of {{ $donors->count() }} results</span>
                        <nav class="donation-pagination" aria-label="Donor pages" data-donor-pagination></nav>
                    </div>
                </section>

                <footer class="dashboard-footer" id="account">
                    <span>Donation System <span aria-hidden="true">·</span> {{ now()->year }}</span>
                    <span>Signed in as {{ auth()->user()->name }}</span>
                </footer>
            </div>
        </main>
    </div>
</body>
</html>
