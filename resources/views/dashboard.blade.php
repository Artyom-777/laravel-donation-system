<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard · Donation System</title>
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
                <a class="nav-item nav-item-active" href="{{ route('dashboard') }}" aria-current="page">
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
                <a class="nav-item" href="{{ route('donors') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg>
                    <span>Donors</span>
                </a>
                <a class="nav-item" href="{{ route('users') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Users</span>
                </a>
                <a class="nav-item" href="#statistics">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V11h4v9zM10 20V5h4v15zM16 20V8h4v12z"/></svg>
                    <span>Reports</span>
                </a>
                <a class="nav-item" href="#account">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 1.2 2.1 2.4.5 1.9-1 2 2-1 1.9.5 2.4L21 12l-2.1 1.2-.5 2.4 1 1.9-2 2-1.9-1-2.4.5L12 21l-1.2-2.1-2.4-.5-1.9 1-2-2 1-1.9-.5-2.4L3 12l2.1-1.2.5-2.4.5-2.4 2-2 1.9 1 2.4-.5z"/><circle cx="12" cy="12" r="3"/></svg>
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

        <main class="dashboard-main" id="dashboard">
            <header class="topbar">
                <div class="topbar-start">
                    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle navigation" aria-expanded="true" aria-controls="dashboard-navigation">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="breadcrumb"><span>Home</span><span aria-hidden="true">/</span><strong>Dashboard</strong></div>
                </div>
                @include('partials.account-menu')
            </header>

            <div class="dashboard-content">
                <section class="page-heading" aria-labelledby="dashboard-heading">
                    <div>
                        <h1 id="dashboard-heading">Dashboard</h1>
                        <p>Welcome back! Here's an overview of your donation system.</p>
                    </div>
                    <div class="page-date">{{ now()->format('F j, Y') }}</div>
                </section>

                <section class="stats-grid" aria-label="Donation system overview">
                    <article class="stat-card stat-card-green">
                        <div class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 20s-8-4.4-8-10a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.6-8 10-8 10z"/><path d="M5 15h4l2-3 2 4 2-3h4"/></svg></div>
                        <div class="stat-copy"><span class="stat-label">Total Donations</span><strong class="stat-value">${{ number_format($stats['totalAmount'], 2) }}</strong><span class="stat-trend">Completed donations</span></div>
                    </article>
                    <article class="stat-card stat-card-blue">
                        <div class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg></div>
                        <div class="stat-copy"><span class="stat-label">Total Donors</span><strong class="stat-value">{{ number_format($stats['totalDonors']) }}</strong><span class="stat-trend">Unique completed donors</span></div>
                    </article>
                    <article class="stat-card stat-card-orange">
                        <div class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><path d="m10 12 1.5 1.5L16 9"/></svg></div>
                        <div class="stat-copy"><span class="stat-label">Active Campaigns</span><strong class="stat-value">{{ number_format($stats['activeCampaigns']) }}</strong><span class="stat-trend">Currently accepting donations</span></div>
                    </article>
                    <article class="stat-card stat-card-red" id="users">
                        <div class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h2M14 14h2M8 17h2"/></svg></div>
                        <div class="stat-copy"><span class="stat-label">Total Users</span><strong class="stat-value">{{ number_format($stats['totalUsers']) }}</strong><span class="stat-trend">Registered accounts</span></div>
                    </article>
                </section>

                <section class="charts-grid" aria-label="Donation analytics">
                    <article class="panel statistics-panel" id="statistics">
                        <div class="panel-heading">
                            <h2><span class="heading-icon" aria-hidden="true">▮</span> Donation Statistics</h2>
                            <span class="period-select" aria-label="Showing donations for {{ $year }}">This Year <span aria-hidden="true">⌄</span></span>
                        </div>
                        <div class="line-chart" role="img" aria-label="Monthly completed donations for {{ $year }}: {{ implode(', ', $monthlyDonationSummary) }}.">
                            <svg viewBox="0 0 600 220" preserveAspectRatio="none" aria-hidden="true">
                                <defs><linearGradient id="donation-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#2dbb78" stop-opacity=".2"/><stop offset="100%" stop-color="#2dbb78" stop-opacity=".02"/></linearGradient></defs>
                                <g class="chart-grid">
                                    <path d="M48 26H590M48 62H590M48 98H590M48 134H590M48 170H590"/>
                                    @foreach ($monthlyDonationGridCoordinates as $chartX)
                                        <path d="M{{ $chartX }} 26V170"/>
                                    @endforeach
                                </g>
                                <g class="chart-axis">
                                    @foreach ($chartAxisLabels as $index => $axisLabel)
                                        <text x="5" y="{{ 30 + ($index * 36) }}">{{ $axisLabel }}</text>
                                    @endforeach
                                    @foreach ($monthlyDonationLabels as $index => $monthLabel)
                                        <text x="{{ 42 + ($index * 540 / 11) }}" y="193">{{ $monthLabel }}</text>
                                    @endforeach
                                </g>
                                <path class="chart-area" d="M48 170L{{ $monthlyDonationPoints }}L588 170Z"/>
                                <path class="chart-line" d="M{{ $monthlyDonationPoints }}"/>
                                <g class="chart-points">
                                    @foreach ($monthlyDonationChartPoints as $point)
                                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5"/>
                                    @endforeach
                                </g>
                            </svg>
                        </div>
                    </article>

                    <article class="panel campaign-chart-panel">
                        <div class="panel-heading"><h2><span class="heading-icon" aria-hidden="true">◕</span> Donations by Campaign</h2></div>
                        <div class="donut-layout">
                            <div class="donut-chart" style="background: {{ $campaignChartGradient }}" role="img" aria-label="Completed donations by campaign, totaling ${{ number_format($stats['totalAmount'], 2) }}.">
                                <div class="donut-total"><strong>${{ number_format($stats['totalAmount'], 2) }}</strong><span>Total</span></div>
                            </div>
                            <ul class="campaign-legend">
                                @if (count($campaignBreakdown) > 0)
                                    @foreach ($campaignBreakdown as $campaignShare)
                                    <li>
                                        <span class="legend-dot" style="background: {{ $campaignShare['color'] }}"></span>
                                        <span>{{ $campaignShare['title'] }}</span>
                                        <strong>{{ $campaignShare['percentage'] }}%</strong>
                                    </li>
                                    @endforeach
                                @else
                                    <li><span>No completed donations yet.</span></li>
                                @endif
                            </ul>
                        </div>
                    </article>
                </section>

                <section class="tables-grid" aria-label="Recent activity">
                    <article class="panel table-panel" id="donations">
                        <div class="panel-heading"><h2 id="donors"><span class="heading-heart" aria-hidden="true">♥</span> Recent Donations</h2><a class="view-all-link" href="{{ route('donations') }}">View All</a></div>
                        <div class="table-scroll">
                            <table>
                                <thead><tr><th scope="col">#</th><th scope="col">Donor</th><th scope="col">Campaign</th><th scope="col">Amount</th><th scope="col">Date</th><th scope="col">Status</th></tr></thead>
                                <tbody>
                                    @if ($recentDonations->isNotEmpty())
                                        @foreach ($recentDonations as $index => $donation)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $donation->donor?->name ?? 'Unknown donor' }}</td>
                                            <td>
                                                @if ($donation->campaign)
                                                    <a href="{{ route('campaigns') }}">{{ $donation->campaign->title }}</a>
                                                @else
                                                    Campaign unavailable
                                                @endif
                                            </td>
                                            <td>${{ number_format((float) $donation->amount, 2) }}</td>
                                            <td>{{ $donation->donated_at->format('M j, Y') }}</td>
                                            <td><span class="status-badge status-badge-{{ strtolower($donation->status) }}">{{ ucfirst($donation->status) }}</span></td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="6">No donation records yet.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </article>
                    <article class="panel table-panel" id="campaigns">
                        <div class="panel-heading"><h2><span class="target-icon" aria-hidden="true">◎</span> Active Campaigns</h2><a class="view-all-link" href="{{ route('campaigns') }}">View All</a></div>
                        <div class="table-scroll">
                            <table>
                                <thead><tr><th scope="col">#</th><th scope="col">Campaign</th><th scope="col">Goal</th><th scope="col">Raised</th><th scope="col">Progress</th><th scope="col">Status</th></tr></thead>
                                <tbody>
                                    @if ($activeCampaigns->isNotEmpty())
                                        @foreach ($activeCampaigns as $index => $campaign)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><a href="{{ route('campaigns') }}">{{ $campaign->title }}</a></td>
                                            <td>${{ number_format((float) $campaign->goal_amount, 2) }}</td>
                                            <td>${{ number_format((float) ($campaign->raised_amount ?? 0), 2) }}</td>
                                            <td>
                                                <div class="progress-cell">
                                                    <span class="progress-track"><span class="progress-fill progress-{{ $campaign->progress_color }}" style="width: {{ $campaign->progress_percentage }}%"></span></span>
                                                    <span>{{ $campaign->progress_percentage }}%</span>
                                                </div>
                                            </td>
                                            <td><span class="status-badge status-badge-active">Active</span></td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="6">No active campaigns right now.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </article>
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
