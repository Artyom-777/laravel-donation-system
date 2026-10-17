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
                    <label class="topbar-search users-topbar-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                        <input type="search" placeholder="Search users by name, email, or role..." aria-label="Search users by name, email, or role" data-user-search>
                    </label>
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
                        <button class="campaign-primary-button users-add-button" type="button">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Add New User
                        </button>
                    </div>
                </section>

                @php
                    $users = [
                        ['name' => 'John Smith', 'email' => 'john@example.com', 'role' => 'Admin', 'status' => 'Active', 'joined' => '2024-12-12', 'last_login' => '2024-12-15', 'last_login_time' => '09:30 AM', 'color' => 'rose'],
                        ['name' => 'Anna Brown', 'email' => 'anna@example.com', 'role' => 'User', 'status' => 'Active', 'joined' => '2024-12-11', 'last_login' => '2024-12-14', 'last_login_time' => '11:20 AM', 'color' => 'blue'],
                        ['name' => 'Michael Lee', 'email' => 'michael@example.com', 'role' => 'User', 'status' => 'Inactive', 'joined' => '2024-12-10', 'last_login' => '2024-12-10', 'last_login_time' => '02:30 PM', 'color' => 'purple'],
                        ['name' => 'Sarah Wilson', 'email' => 'sarah@example.com', 'role' => 'Editor', 'status' => 'Active', 'joined' => '2024-12-09', 'last_login' => '2024-12-15', 'last_login_time' => '08:45 AM', 'color' => 'cyan'],
                        ['name' => 'David Kim', 'email' => 'david@example.com', 'role' => 'User', 'status' => 'Active', 'joined' => '2024-12-08', 'last_login' => '2024-12-14', 'last_login_time' => '04:10 PM', 'color' => 'orange'],
                        ['name' => 'Emma Davis', 'email' => 'emma@example.com', 'role' => 'Admin', 'status' => 'Active', 'joined' => '2024-12-07', 'last_login' => '2024-12-15', 'last_login_time' => '10:00 AM', 'color' => 'sky'],
                        ['name' => 'Robert Johnson', 'email' => 'robert@example.com', 'role' => 'User', 'status' => 'Inactive', 'joined' => '2024-12-06', 'last_login' => '2024-12-06', 'last_login_time' => '01:15 PM', 'color' => 'teal'],
                        ['name' => 'Linda Perez', 'email' => 'linda@example.com', 'role' => 'Editor', 'status' => 'Active', 'joined' => '2024-12-05', 'last_login' => '2024-12-15', 'last_login_time' => '12:15 PM', 'color' => 'red'],
                        ['name' => 'William Chen', 'email' => 'william@example.com', 'role' => 'User', 'status' => 'Active', 'joined' => '2024-12-04', 'last_login' => '2024-12-14', 'last_login_time' => '09:40 AM', 'color' => 'indigo'],
                        ['name' => 'Ashley Scott', 'email' => 'ashley@example.com', 'role' => 'User', 'status' => 'Active', 'joined' => '2024-12-03', 'last_login' => '2024-12-14', 'last_login_time' => '02:30 PM', 'color' => 'purple'],
                    ];
                @endphp

                <section class="user-stats-grid" aria-label="User summary">
                    <article class="user-stat-card user-stat-blue">
                        <span class="user-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg></span>
                        <div><span>Total Users</span><strong>124</strong><small>↑ 12% from last month</small></div>
                    </article>
                    <article class="user-stat-card user-stat-green">
                        <span class="user-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                        <div><span>Active Users</span><strong>108</strong><small>↑ 8% from last month</small></div>
                    </article>
                    <article class="user-stat-card user-stat-purple">
                        <span class="user-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg></span>
                        <div><span>Admins</span><strong>12</strong><small>↑ 20% from last month</small></div>
                    </article>
                    <article class="user-stat-card user-stat-orange">
                        <span class="user-stat-icon user-stat-add" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M17 8v6M14 11h6"/></svg></span>
                        <div><span>Inactive Users</span><strong>16</strong><small class="user-stat-decrease">↑ 5% from last month</small></div>
                    </article>
                </section>

                <section class="panel users-panel" aria-labelledby="users-list-heading">
                    <div class="user-filters">
                        <label class="user-table-search">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                            <input type="search" placeholder="Search users..." aria-label="Search users" data-user-search>
                        </label>
                        <label class="user-filter-select">
                            <span class="visually-hidden">Filter by role</span>
                            <select aria-label="Filter by role" data-user-role-filter>
                                <option value="">All Roles</option>
                                <option>Admin</option>
                                <option>Editor</option>
                                <option>User</option>
                            </select>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </label>
                        <label class="user-filter-select">
                            <span class="visually-hidden">Filter by status</span>
                            <select aria-label="Filter by status" data-user-status-filter>
                                <option value="">All Statuses</option>
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </label>
                        <div class="user-date-filter">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                            <label><span class="visually-hidden">Joined from</span><input type="date" aria-label="Joined from" data-user-start-date></label>
                            <span aria-hidden="true">–</span>
                            <label><span class="visually-hidden">Joined to</span><input type="date" aria-label="Joined to" data-user-end-date></label>
                        </div>
                        <button class="campaign-primary-button user-filter-button" type="button" data-user-filter-button>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6.5 7.5V19l-3 1v-7.5z"/></svg>
                            Filter
                        </button>
                    </div>

                    <div class="user-table-heading">
                        <h2 id="users-list-heading"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1h-3"/></svg> Users List</h2>
                        <span data-user-range>Showing 1–10 of 10 users</span>
                    </div>

                    <div class="table-scroll user-table-scroll">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name <span aria-hidden="true">⌃</span></th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Joined Date</th>
                                    <th scope="col">Last Login</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody data-user-rows>
                                @foreach ($users as $index => $user)
                                    <tr
                                        data-user-row
                                        data-user="{{ strtolower($user['name'].' '.$user['email'].' '.$user['role']) }}"
                                        data-role="{{ $user['role'] }}"
                                        data-status="{{ $user['status'] }}"
                                        data-joined="{{ $user['joined'] }}"
                                    >
                                        <td data-label="#">{{ $index + 1 }}</td>
                                        <td data-label="Name">
                                            <span class="user-name-cell">
                                                <span class="user-row-avatar user-row-avatar-{{ $user['color'] }}">{{ strtoupper(mb_substr($user['name'], 0, 2)) }}</span>
                                                <strong>{{ $user['name'] }}</strong>
                                            </span>
                                        </td>
                                        <td data-label="Email"><a href="mailto:{{ $user['email'] }}">{{ $user['email'] }}</a></td>
                                        <td data-label="Role"><span class="user-role user-role-{{ strtolower($user['role']) }}">{{ $user['role'] }}</span></td>
                                        <td data-label="Status"><span class="user-status user-status-{{ strtolower($user['status']) }}">{{ $user['status'] }}</span></td>
                                        <td data-label="Joined Date"><span class="user-date-cell">{{ \Carbon\Carbon::parse($user['joined'])->format('M j, Y') }}<small>10:24 AM</small></span></td>
                                        <td data-label="Last Login"><span class="user-date-cell">{{ \Carbon\Carbon::parse($user['last_login'])->format('M j, Y') }}<small>{{ $user['last_login_time'] }}</small></span></td>
                                        <td data-label="Actions"><button class="donation-row-action" type="button" aria-label="Actions for {{ $user['name'] }}" title="More actions">···</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="user-empty-state" data-user-empty hidden>No users match the selected filters.</p>
                    </div>

                    <div class="user-table-footer">
                        <span data-user-footer-range>Showing 1 to 10 of 10 results</span>
                        <nav class="donation-pagination" aria-label="User pages" data-user-pagination></nav>
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
