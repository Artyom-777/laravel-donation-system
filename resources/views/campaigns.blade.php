<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Campaigns · Donation System</title>
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
                <a class="nav-item nav-item-active" href="{{ route('campaigns') }}" aria-current="page">
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

        <main class="dashboard-main" id="dashboard">
            <header class="topbar">
                <div class="topbar-start">
                    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle navigation" aria-expanded="true" aria-controls="dashboard-navigation">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <form class="topbar-search" method="GET" action="{{ route('campaigns') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search campaigns..." aria-label="Search campaigns" data-campaign-search>
                        @if (! empty($filters['status']))
                            <input type="hidden" name="status" value="{{ $filters['status'] }}">
                        @endif
                        @if (! empty($filters['sort']))
                            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                        @endif
                    </form>
                </div>
                @include('partials.account-menu')
            </header>

            <div class="dashboard-content">
                @if (session('status'))
                    <div class="campaign-success-message" role="status">{{ session('status') }}</div>
                @endif
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

                <section class="page-heading campaign-page-heading" aria-labelledby="campaigns-heading">
                    <div>
                        <h1 id="campaigns-heading">Campaigns</h1>
                        <p>Browse and support our active donation campaigns</p>
                    </div>
                    <div class="campaign-heading-actions">
                        <div class="campaign-breadcrumb"><span>Home</span><span aria-hidden="true">/</span><strong>Campaigns</strong></div>
                        <a class="campaign-primary-button campaign-create-button" href="{{ route('campaigns.create') }}">
                            <span aria-hidden="true">+</span> Add New Campaign
                        </a>
                    </div>
                </section>

                <section class="campaign-listing" id="campaigns" aria-label="Donation campaigns">
                    <div class="campaign-toolbar">
                        <nav
                            class="campaign-filters"
                            aria-label="Filter campaigns by status"
                            data-campaign-status-filters
                            data-active-status="{{ $filters['status'] ?? '' }}"
                            data-count-all="{{ $campaignCounts->sum() }}"
                            data-count-active="{{ $campaignCounts->get('active', 0) }}"
                            data-count-completed="{{ $campaignCounts->get('completed', 0) }}"
                            data-count-upcoming="{{ $campaignCounts->get('upcoming', 0) }}"
                            data-search="{{ $filters['search'] ?? '' }}"
                            data-sort="{{ $filters['sort'] ?? '' }}"
                        >
                            <a class="campaign-filter{{ empty($filters['status']) ? ' is-selected' : '' }}" href="{{ route('campaigns', array_filter(['search' => $filters['search'] ?? null, 'sort' => $filters['sort'] ?? null])) }}" @if (empty($filters['status'])) aria-current="page" @endif>
                                All Campaigns <span>{{ $campaignCounts->sum() }}</span>
                            </a>
                            @foreach (['active' => 'Active', 'completed' => 'Completed', 'upcoming' => 'Upcoming'] as $statusKey => $statusLabel)
                                <a
                                    class="campaign-filter{{ ($filters['status'] ?? '') === $statusKey ? ' is-selected' : '' }}"
                                    href="{{ route('campaigns', array_filter(['status' => $statusKey, 'search' => $filters['search'] ?? null, 'sort' => $filters['sort'] ?? null])) }}"
                                    @if (($filters['status'] ?? '') === $statusKey) aria-current="page" @endif
                                >
                                    {{ $statusLabel }} <span>{{ $campaignCounts->get($statusKey, 0) }}</span>
                                </a>
                            @endforeach
                        </nav>
                        <form class="campaign-tools campaign-query-form" method="GET" action="{{ route('campaigns') }}">
                            @if (! empty($filters['status']))
                                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            @endif
                            <label class="campaign-search">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search campaigns..." aria-label="Search campaigns" data-campaign-search>
                            </label>
                            <label class="campaign-sort">
                                <span>Sort by:</span>
                                <select name="sort" aria-label="Sort campaigns" data-campaign-sort>
                                    <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Latest</option>
                                    <option value="goal-high" @selected(($filters['sort'] ?? '') === 'goal-high')>Goal: highest</option>
                                    <option value="goal-low" @selected(($filters['sort'] ?? '') === 'goal-low')>Goal: lowest</option>
                                </select>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                            </label>
                            <button class="campaign-query-submit" type="submit">Apply</button>
                        </form>
                    </div>

                    <div class="campaign-grid" data-campaign-list>
                        @foreach ($campaigns as $campaign)
                            @php
                                $raised = (float) ($campaign->raised_amount ?? 0);
                                $goal = (float) $campaign->goal_amount;
                                $progress = min((int) round(($raised / $goal) * 100), 100);
                                $statusKey = strtolower($campaign->status);
                                $isAcceptingDonations = $statusKey === 'active'
                                    && ! $campaign->start_date->isAfter(today())
                                    && ! $campaign->end_date->isBefore(today());
                            @endphp
                            <article
                                class="campaign-card"
                                data-campaign-card
                                data-campaign-name="{{ strtolower($campaign->title) }}"
                                data-status="{{ $statusKey }}"
                                data-goal="{{ $goal }}"
                            >
                                <div class="campaign-image-wrap">
                                    @if ($campaign->image_path)
                                        <img
                                            class="campaign-image"
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($campaign->image_path) }}"
                                            alt=""
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="campaign-image-placeholder" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M12 21s-8-4.4-8-11a4.5 4.5 0 0 1 8-2.9A4.5 4.5 0 0 1 20 10c0 6.6-8 11-8 11z"/></svg>
                                        </div>
                                    @endif
                                    <span class="campaign-status campaign-status-{{ $statusKey }}">{{ ucfirst($campaign->status) }}</span>
                                </div>
                                <div class="campaign-card-content">
                                    <h2>{{ $campaign->title }}</h2>
                                    <p class="campaign-description">{{ $campaign->short_description }}</p>
                                    <div class="campaign-fundraising">
                                        <strong>${{ number_format($raised, 2) }} <span>raised</span></strong>
                                        <span>${{ number_format($goal, 2) }} goal</span>
                                    </div>
                                    <div class="campaign-progress-row">
                                        <div
                                            class="campaign-progress"
                                            role="progressbar"
                                            aria-label="{{ $campaign->title }} fundraising progress"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-valuenow="{{ $progress }}"
                                        >
                                            <span style="width: {{ $progress }}%"></span>
                                        </div>
                                        <span class="campaign-progress-label">{{ $progress }}%</span>
                                    </div>
                                    <div class="campaign-meta">
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg>
                                            {{ $campaign->donors_count }} {{ \Illuminate\Support\Str::plural('donor', $campaign->donors_count) }}
                                        </span>
                                        <span class="campaign-meta-divider" aria-hidden="true"></span>
                                        <span>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h2M14 14h2"/></svg>
                                            @if ($statusKey === 'upcoming')
                                                Starts {{ $campaign->start_date->format('M j, Y') }}
                                            @elseif ($statusKey === 'completed')
                                                Ended {{ $campaign->end_date->format('M j, Y') }}
                                            @else
                                                Ends {{ $campaign->end_date->format('M j, Y') }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="campaign-actions">
                                        @if ($statusKey === 'completed')
                                            <div class="campaign-react-button-root" data-campaign-react-button data-action="status" data-campaign-id="{{ $campaign->id }}">
                                                <button class="campaign-action-button campaign-completed-button" type="button" disabled>Completed</button>
                                            </div>
                                        @elseif ($statusKey === 'upcoming')
                                            <div class="campaign-react-button-root" data-campaign-react-button data-action="notify" data-campaign-id="{{ $campaign->id }}">
                                                <button class="campaign-action-button campaign-notify-button" type="button">Notify Me</button>
                                            </div>
                                        @elseif (! $isAcceptingDonations)
                                            <div class="campaign-react-button-root" data-campaign-react-button data-action="status" data-campaign-id="{{ $campaign->id }}">
                                                <button class="campaign-action-button campaign-completed-button" type="button" disabled>Not Accepting Donations</button>
                                            </div>
                                        @else
                                            @php($donationPanelId = 'campaign-donation-form-'.$campaign->id)
                                            <div class="campaign-donation-details">
                                                <div
                                                    class="campaign-react-button-root"
                                                    data-campaign-react-button
                                                    data-action="donate"
                                                    data-campaign-id="{{ $campaign->id }}"
                                                    data-panel-id="{{ $donationPanelId }}"
                                                    data-expanded="{{ (string) old('campaign_id') === (string) $campaign->id ? 'true' : 'false' }}"
                                                >
                                                    <button class="campaign-action-button campaign-primary-button" type="button" aria-expanded="{{ (string) old('campaign_id') === (string) $campaign->id ? 'true' : 'false' }}" aria-controls="{{ $donationPanelId }}">Donate Now</button>
                                                </div>
                                                <div
                                                    class="campaign-donation-form-panel"
                                                    id="{{ $donationPanelId }}"
                                                    data-campaign-donation-panel
                                                    @if ((string) old('campaign_id') !== (string) $campaign->id) hidden @endif
                                                >
                                                <form
                                                    class="campaign-donation-form"
                                                    method="POST"
                                                    action="{{ route('campaigns.donations.store', $campaign) }}"
                                                    data-stripe-donation-form
                                                    data-publishable-key="{{ config('services.stripe.key') }}"
                                                    data-confirm-url-template="{{ route('donations.confirm', ['donation' => '__DONATION_ID__']) }}"
                                                >
                                                    @csrf
                                                    <p class="campaign-pledge-notice">Your card is processed securely by Stripe. Card details are sent directly to Stripe and never stored on this site.</p>
                                                    <label>
                                                        <span>Signed-in account</span>
                                                        <input type="text" name="donor_name" value="{{ auth()->user()->name }}" autocomplete="name" readonly required>
                                                    </label>
                                                    <label>
                                                        <span>Receipt email</span>
                                                        <input type="email" name="donor_email" value="{{ auth()->user()->email }}" autocomplete="email" readonly required>
                                                    </label>
                                                    <label>
                                                        <span>Phone number <small>(optional)</small></span>
                                                        <input type="tel" name="donor_phone" value="{{ old('campaign_id') == $campaign->id ? old('donor_phone') : '' }}" maxlength="32" autocomplete="tel">
                                                    </label>
                                                    <label>
                                                        <span>Amount ($)</span>
                                                        <input type="number" name="amount" value="{{ old('campaign_id') == $campaign->id ? old('amount') : '' }}" min="0.50" max="999999.99" step="0.01" required>
                                                    </label>
                                                    @if (filled(config('services.stripe.key')) && filled(config('services.stripe.secret')))
                                                        <label class="campaign-card-label">
                                                            <span>Card details</span>
                                                            <span class="campaign-stripe-card" data-stripe-card></span>
                                                        </label>
                                                        <p class="campaign-payment-message" data-stripe-message role="status" aria-live="polite"></p>
                                                        <div class="campaign-react-button-root" data-campaign-react-button data-action="submit" data-campaign-id="{{ $campaign->id }}">
                                                            <button class="campaign-action-button campaign-primary-button campaign-pay-button" type="submit">Donate securely with card</button>
                                                        </div>
                                                    @else
                                                        <p class="campaign-payment-unavailable" role="status">Card payments are not configured yet. Please contact the site administrator.</p>
                                                        <div class="campaign-react-button-root" data-campaign-react-button data-action="submit" data-campaign-id="{{ $campaign->id }}">
                                                            <button class="campaign-action-button campaign-primary-button campaign-pay-button" type="submit" disabled>Donations unavailable</button>
                                                        </div>
                                                    @endif
                                                </form>
                                                </div>
                                            </div>
                                        @endif
                                        <div
                                            class="campaign-react-button-root"
                                            data-campaign-react-button
                                            data-action="details"
                                            data-campaign-id="{{ $campaign->id }}"
                                            data-campaign-title="{{ $campaign->title }}"
                                            data-campaign-status="{{ $statusKey }}"
                                            data-campaign-description="{{ $campaign->description ?: $campaign->short_description }}"
                                        >
                                            <button class="campaign-action-button campaign-details-button" type="button" aria-haspopup="dialog">View Details</button>
                                        </div>
                                        @if ((int) $campaign->created_by === (int) auth()->id())
                                            <form
                                                class="campaign-delete-form"
                                                method="POST"
                                                action="{{ route('campaigns.destroy', $campaign) }}"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <div
                                                    class="campaign-react-button-root"
                                                    data-campaign-react-button
                                                    data-action="delete"
                                                    data-campaign-id="{{ $campaign->id }}"
                                                    data-confirm-message="Delete &quot;{{ $campaign->title }}&quot;? This action cannot be undone."
                                                >
                                                    <button class="campaign-action-button campaign-delete-button" type="submit">Delete</button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if ($campaigns->isEmpty())
                        <p class="campaign-empty-state" role="status">No campaigns found. Try a different search or add a campaign.</p>
                    @endif
                    @if ($campaigns->hasPages())
                        <nav class="campaign-pagination" aria-label="Campaign pages">
                            @if ($campaigns->onFirstPage())
                                <span class="campaign-page-link is-disabled" aria-disabled="true">&lsaquo;</span>
                            @else
                                <a class="campaign-page-link" href="{{ $campaigns->previousPageUrl() }}" aria-label="Previous page">&lsaquo;</a>
                            @endif

                            @if ($campaigns->currentPage() > 3)
                                <a class="campaign-page-link" href="{{ $campaigns->url(1) }}">1</a>
                                <span class="campaign-page-ellipsis" aria-hidden="true">…</span>
                            @endif

                            @foreach ($campaigns->getUrlRange(max(1, $campaigns->currentPage() - 2), min($campaigns->lastPage(), $campaigns->currentPage() + 2)) as $page => $url)
                                <a class="campaign-page-link{{ $page === $campaigns->currentPage() ? ' is-current' : '' }}" href="{{ $url }}" @if ($page === $campaigns->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                            @endforeach

                            @if ($campaigns->currentPage() < $campaigns->lastPage() - 2)
                                <span class="campaign-page-ellipsis" aria-hidden="true">…</span>
                                <a class="campaign-page-link" href="{{ $campaigns->url($campaigns->lastPage()) }}">{{ $campaigns->lastPage() }}</a>
                            @endif

                            @if ($campaigns->hasMorePages())
                                <a class="campaign-page-link" href="{{ $campaigns->nextPageUrl() }}" aria-label="Next page">&rsaquo;</a>
                            @else
                                <span class="campaign-page-link is-disabled" aria-disabled="true">&rsaquo;</span>
                            @endif
                        </nav>
                    @endif
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
