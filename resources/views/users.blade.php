<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Users · Donation System</title>
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
                <a class="nav-item" href="{{ route('donors') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg>
                    <span>Donors</span>
                </a>
                <a class="nav-item nav-item-active" href="{{ route('users') }}" aria-current="page">
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
                    <form class="topbar-search users-topbar-search" method="GET" action="{{ route('users') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search users by name, email, or role..." aria-label="Search users by name, email, or role">
                        @if (! empty($filters['role']))
                            <input type="hidden" name="role" value="{{ $filters['role'] }}">
                        @endif
                    </form>
                </div>
                @include('partials.account-menu')
            </header>

            <div class="dashboard-content users-content">
                <section class="page-heading users-page-heading" aria-labelledby="users-heading">
                    <div>
                        <h1 id="users-heading">Users</h1>
                        <p>Manage all users who have access to the donation system.</p>
                    </div>
                    <div class="users-heading-actions">
                        <nav class="campaign-breadcrumb" aria-label="Breadcrumb">
                            <a href="{{ route('dashboard') }}">Home</a>
                            <span aria-hidden="true">/</span>
                            <strong>Users</strong>
                        </nav>

                    </div>
                </section>

                <section class="user-stats-grid" aria-label="User summary">
                    <article class="user-stat-card user-stat-blue">
                        <span class="user-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg></span>
                        <div><span>Total Users</span><strong>{{ number_format($userCount) }}</strong><small>Registered accounts</small></div>
                    </article>
                    <article class="user-stat-card user-stat-green">
                        <span class="user-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                        <div><span>Members</span><strong>{{ number_format($roleCounts->get('member', 0)) }}</strong><small>Member accounts</small></div>
                    </article>
                    <article class="user-stat-card user-stat-purple">
                        <span class="user-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg></span>
                        <div><span>Admins</span><strong>{{ number_format($roleCounts->get('admin', 0)) }}</strong><small>Administrator accounts</small></div>
                    </article>
                    <article class="user-stat-card user-stat-orange">
                        <span class="user-stat-icon user-stat-add" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M17 8v6M14 11h6"/></svg></span>
                        <div><span>Guests</span><strong>{{ number_format($roleCounts->get('guest', 0)) }}</strong><small>Guest accounts</small></div>
                    </article>
                </section>

                <section class="panel users-panel" aria-labelledby="users-list-heading">
                    <form class="user-filters" method="GET" action="{{ route('users') }}">
                        <label class="user-table-search">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search users..." aria-label="Search users by name, email, or role">
                        </label>
                        <label class="user-filter-select">
                            <span class="visually-hidden">Filter by role</span>
                            <select name="role" aria-label="Filter by role">
                                <option value="">All Roles</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ ucfirst($role->value) }}</option>
                                @endforeach
                            </select>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </label>
                        <button class="campaign-primary-button user-filter-button" type="submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6.5 7.5V19l-3 1v-7.5z"/></svg>
                            Filter
                        </button>
                    </form>

                    <div class="user-table-heading">
                        <h2 id="users-list-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg> Users List</h2>
                        <span>Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users</span>
                    </div>

                    <div class="table-scroll user-table-scroll">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Account ID</th>
                                    <th scope="col">Campaigns</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    @php($role = $user->roleAssignment?->role)
                                    <tr>
                                        <td data-label="#">{{ ($users->firstItem() ?? 1) + $loop->index }}</td>
                                        <td data-label="Name">
                                            <span class="user-name-cell">
                                                <span class="user-row-avatar user-row-avatar-{{ ['blue', 'purple', 'cyan', 'orange', 'teal'][$user->id % 5] }}">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span>
                                                <strong>{{ $user->name }}</strong>
                                            </span>
                                        </td>
                                        <td data-label="Email"><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td>
                                        <td data-label="Role"><span class="user-role user-role-{{ $role?->value ?? 'unknown' }}">{{ ucfirst($role?->value ?? 'unknown') }}</span></td>
                                        <td data-label="Account ID">#{{ $user->id }}</td>
                                        <td data-label="Campaigns">{{ $user->campaigns_count }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="user-empty-state">No users match the selected filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="user-table-footer">
                        <span>Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} results</span>
                        @if ($users->hasPages())
                            <nav class="donation-pagination" aria-label="User pages">
                                @if ($users->onFirstPage())
                                    <span class="donation-page-button" aria-disabled="true">&lsaquo;</span>
                                @else
                                    <a class="donation-page-button" href="{{ $users->previousPageUrl() }}" aria-label="Previous page">&lsaquo;</a>
                                @endif
                                @foreach ($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                                    <a class="donation-page-button{{ $page === $users->currentPage() ? ' is-current' : '' }}" href="{{ $url }}" @if ($page === $users->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                                @endforeach
                                @if ($users->hasMorePages())
                                    <a class="donation-page-button" href="{{ $users->nextPageUrl() }}" aria-label="Next page">&rsaquo;</a>
                                @else
                                    <span class="donation-page-button" aria-disabled="true">&rsaquo;</span>
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
