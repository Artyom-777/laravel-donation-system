<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Donations · Donation System</title>
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
                <a class="nav-item nav-item-active" href="{{ route('donations') }}" aria-current="page">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8z"/></svg>
                    <span>Donations</span>
                </a>
                <a class="nav-item" href="{{ route('donors') }}">
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
                    <form class="topbar-search donations-topbar-search" method="GET" action="{{ route('donations') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search donations, donors, campaigns..." aria-label="Search donations, donors, and campaigns">
                    </form>
                </div>
                @include('partials.account-menu')
            </header>

            <div class="dashboard-content donations-content">
                <section class="page-heading donations-page-heading" aria-labelledby="donations-heading">
                    <div>
                        <h1 id="donations-heading">Donations</h1>
                        <p>Manage and view all donations received through your platform.</p>
                    </div>
                    <div class="donations-heading-actions">
                        <nav class="campaign-breadcrumb" aria-label="Breadcrumb">
                            <a href="{{ route('dashboard') }}">Home</a>
                            <span aria-hidden="true">/</span>
                            <strong>Donations</strong>
                        </nav>
                        <a class="campaign-primary-button donations-export-button" href="{{ route('donations.export', $filters) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4"/></svg>
                            Export
                        </a>
                    </div>
                </section>

                <section class="donation-stats-grid" aria-label="Donation summary">
                    <article class="donation-stat-card donation-stat-green">
                        <span class="donation-stat-icon" aria-hidden="true">$</span>
                        <div><span>Total Donations</span><strong>${{ number_format((float) $summary->total_amount, 2) }}</strong><small>{{ number_format($summary->total_count) }} donation records</small></div>
                    </article>
                    <article class="donation-stat-card donation-stat-blue">
                        <span class="donation-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg>
                        </span>
                        <div><span>Total Donors</span><strong>{{ number_format($summary->donor_count) }}</strong><small>Unique donors</small></div>
                    </article>
                    <article class="donation-stat-card donation-stat-orange">
                        <span class="donation-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                        </span>
                        <div><span>Completed</span><strong>{{ number_format($summary->completed_count) }}</strong><small>Successful payments</small></div>
                    </article>
                    <article class="donation-stat-card donation-stat-red">
                        <span class="donation-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        <div><span>Pending</span><strong>{{ number_format($summary->pending_count) }}</strong><small>Awaiting payment</small></div>
                    </article>
                </section>

                <section class="panel donations-panel" aria-labelledby="recent-donations-heading">
                    <form class="donation-filters" method="GET" action="{{ route('donations') }}">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <label class="donation-filter-select">
                            <span class="visually-hidden">Filter by campaign</span>
                            <select name="campaign_id" aria-label="Filter by campaign">
                                <option value="">All Campaigns</option>
                                @foreach ($campaigns as $campaign)
                                    <option value="{{ $campaign->id }}" @selected((string) ($filters['campaign_id'] ?? '') === (string) $campaign->id)>{{ $campaign->title }}</option>
                                @endforeach
                            </select>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </label>
                        <label class="donation-filter-select">
                            <span class="visually-hidden">Filter by status</span>
                            <select name="status" aria-label="Filter by status">
                                <option value="">All Statuses</option>
                                <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completed</option>
                                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                                <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed</option>
                            </select>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </label>
                        <div class="donation-date-filter">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                            <label><span class="visually-hidden">Start date</span><input type="date" name="start_date" aria-label="Start date" value="{{ $filters['start_date'] ?? '' }}"></label>
                            <span aria-hidden="true">–</span>
                            <label><span class="visually-hidden">End date</span><input type="date" name="end_date" aria-label="End date" value="{{ $filters['end_date'] ?? '' }}"></label>
                        </div>
                        <button class="campaign-primary-button donation-filter-button" type="submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6.5 7.5V19l-3 1v-7.5z"/></svg>
                            Filter
                        </button>
                    </form>

                    <div class="donation-table-heading">
                        <h2 id="recent-donations-heading"><span aria-hidden="true">✥</span> Recent Donations</h2>
                        <span>Showing {{ $donations->firstItem() ?? 0 }}–{{ $donations->lastItem() ?? 0 }} of {{ $donations->total() }} donations</span>
                    </div>

                    <div class="table-scroll donation-table-scroll">
                        <table class="donation-table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Donor <span aria-hidden="true">↕</span></th>
                                    <th scope="col">Campaign</th>
                                    <th scope="col">Amount <span aria-hidden="true">↕</span></th>
                                    <th scope="col">Date <span aria-hidden="true">↕</span></th>
                                    <th scope="col">Payment Method</th>
                                    <th scope="col">Status <span aria-hidden="true">↕</span></th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody data-donation-rows>
                                @foreach ($donations as $index => $donation)
                                    <tr
                                        data-donation-row
                                        data-donor="{{ strtolower($donation->donor->name.' '.$donation->donor->email) }}"
                                        data-campaign="{{ $donation->campaign->title }}"
                                        data-status="{{ $donation->status }}"
                                        data-date="{{ $donation->donated_at->toDateString() }}"
                                    >
                                        <td data-label="#">{{ $donations->firstItem() + $index }}</td>
                                        <td data-label="Donor">
                                            <span class="donor-cell">
                                                <span class="donor-avatar donor-avatar-{{ ['rose', 'blue', 'cyan', 'teal', 'indigo', 'sky', 'slate'][$donation->donor_id % 7] }}">{{ strtoupper(mb_substr($donation->donor->name, 0, 2)) }}</span>
                                                <span><strong>{{ $donation->donor->name }}</strong><small>{{ $donation->donor->email }}</small></span>
                                            </span>
                                        </td>
                                        <td data-label="Campaign"><a href="{{ route('campaigns') }}">{{ $donation->campaign->title }}</a></td>
                                        <td data-label="Amount">${{ number_format((float) $donation->amount, 2) }}</td>
                                        <td data-label="Date"><span class="donation-date-cell">{{ $donation->donated_at->format('M d, Y') }}<small>{{ $donation->donated_at->format('h:i A') }}</small></span></td>
                                        <td data-label="Payment Method">
                                            <span class="donation-payment-method">
                                                @if ($donation->payment_method === 'PayPal')
                                                    <span class="paypal-mark" aria-hidden="true">P</span>
                                                @elseif ($donation->payment_method === 'Bank Transfer')
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10h18M5 10V20M10 10V20M14 10V20M19 10V20M3 20h18M12 3 3 8v2h18V8z"/></svg>
                                                @else
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                                                @endif
                                                {{ $donation->payment_method }}
                                            </span>
                                        </td>
                                        <td data-label="Status"><span class="donation-status donation-status-{{ strtolower($donation->status) }}">{{ ucfirst($donation->status) }}</span></td>
                                        <td data-label="Actions"><button class="donation-row-action" type="button" aria-label="Actions for {{ $donation->donor->name }}" title="More actions">···</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if ($donations->isEmpty())
                            <p class="donation-empty-state" role="status">No donations match the selected filters.</p>
                        @endif
                    </div>

                    <div class="donation-table-footer">
                        <span>Showing {{ $donations->firstItem() ?? 0 }} to {{ $donations->lastItem() ?? 0 }} of {{ $donations->total() }} results</span>
                        @if ($donations->hasPages())
                            <nav class="donation-pagination" aria-label="Donation pages">
                                @if ($donations->onFirstPage())
                                    <span class="donation-page-button" aria-disabled="true">‹</span>
                                @else
                                    <a class="donation-page-button" href="{{ $donations->previousPageUrl() }}" aria-label="Previous page">‹</a>
                                @endif
                                @foreach ($donations->getUrlRange(1, $donations->lastPage()) as $page => $url)
                                    <a class="donation-page-button{{ $page === $donations->currentPage() ? ' is-current' : '' }}" href="{{ $url }}" @if ($page === $donations->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                                @endforeach
                                @if ($donations->hasMorePages())
                                    <a class="donation-page-button" href="{{ $donations->nextPageUrl() }}" aria-label="Next page">›</a>
                                @else
                                    <span class="donation-page-button" aria-disabled="true">›</span>
                                @endif
                            </nav>
                        @endif
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
