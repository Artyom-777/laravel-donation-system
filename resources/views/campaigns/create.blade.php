<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add New Campaign · Donation System</title>
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
                    <label class="topbar-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m16 16 4.2 4.2"/></svg>
                        <input type="search" placeholder="Search campaigns, donors..." aria-label="Search campaigns and donors">
                    </label>
                </div>
                @include('partials.account-menu')
            </header>

            <div class="dashboard-content campaign-create-content">
                <section class="page-heading campaign-page-heading" aria-labelledby="create-campaign-heading">
                    <div>
                        <h1 id="create-campaign-heading">Add New Campaign</h1>
                        <p>Create a new donation campaign to raise funds for a cause.</p>
                    </div>
                    <nav class="campaign-breadcrumb" aria-label="Breadcrumb">
                        <a href="{{ route('dashboard') }}">Home</a>
                        <span aria-hidden="true">/</span>
                        <a href="{{ route('campaigns') }}">Campaigns</a>
                        <span aria-hidden="true">/</span>
                        <strong>Add New</strong>
                    </nav>
                </section>

                <form class="campaign-create-form" method="POST" action="{{ route('campaigns.store') }}" enctype="multipart/form-data">
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

                    <section class="panel campaign-form-panel" aria-labelledby="campaign-information-heading">
                        <h2 class="campaign-form-heading" id="campaign-information-heading">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h7M9 9h1"/></svg>
                            Campaign Information
                        </h2>

                        <div class="campaign-form-field">
                            <label for="campaign-title">Title <span aria-hidden="true">*</span></label>
                            <input id="campaign-title" name="title" type="text" value="{{ old('title') }}" placeholder="Enter campaign title (e.g. Build a School)" required>
                        </div>

                        <div class="campaign-form-field">
                            <label for="campaign-short-description">Short Description <span aria-hidden="true">*</span></label>
                            <textarea id="campaign-short-description" name="short_description" rows="3" placeholder="Enter a short description for the campaign..." required>{{ old('short_description') }}</textarea>
                        </div>

                        <div class="campaign-form-field">
                            <label for="campaign-description">Full Description</label>
                            <div class="campaign-editor">
                                <div class="campaign-editor-toolbar" role="group" aria-label="Description formatting">
                                    <button type="button" aria-label="Bold" data-editor-command="bold"><strong>B</strong></button>
                                    <button type="button" aria-label="Italic" data-editor-command="italic"><em>I</em></button>
                                    <button type="button" aria-label="Underline" data-editor-command="underline"><span class="campaign-underline">U</span></button>
                                    <span class="campaign-editor-divider"></span>
                                    <button type="button" aria-label="Bulleted list" data-editor-command="insertUnorderedList"><span aria-hidden="true">☷</span></button>
                                    <button type="button" aria-label="Numbered list" data-editor-command="insertOrderedList"><span aria-hidden="true">☰</span></button>
                                    <button type="button" aria-label="Insert link" data-editor-command="createLink"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 11a5 5 0 0 0-7.1 0l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1"/></svg></button>
                                    <span class="campaign-editor-spacer"></span>
                                    <button type="button" aria-label="Expand editor" data-editor-expand>
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M3 16v5h5M21 16v5h-5"/></svg>
                                    </button>
                                </div>
                                <div
                                    class="campaign-rich-editor"
                                    id="campaign-description"
                                    contenteditable="true"
                                    role="textbox"
                                    aria-label="Full campaign description"
                                    aria-multiline="true"
                                    data-campaign-editor
                                    data-placeholder="Enter detailed description about the campaign..."
                                >{{ old('description') }}</div>
                                <input type="hidden" id="campaign-description-value" name="description" value="{{ old('description') }}" data-campaign-description>
                            </div>
                        </div>

                        <div class="campaign-form-fields-grid">
                            <div class="campaign-form-field">
                                <label for="campaign-goal">Goal Amount <span aria-hidden="true">*</span></label>
                                <div class="campaign-input-prefix">
                                    <span aria-hidden="true">$</span>
                                    <input id="campaign-goal" name="goal_amount" type="number" min="0.01" step="0.01" value="{{ old('goal_amount') }}" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="campaign-form-field">
                                <label for="campaign-start-date">Start Date <span aria-hidden="true">*</span></label>
                                <input id="campaign-start-date" name="start_date" type="date" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="campaign-form-field">
                                <label for="campaign-end-date">End Date <span aria-hidden="true">*</span></label>
                                <input id="campaign-end-date" name="end_date" type="date" value="{{ old('end_date') }}" required>
                            </div>
                            <div class="campaign-form-field">
                                <label for="campaign-category">Category <span aria-hidden="true">*</span></label>
                                <select id="campaign-category" name="category" required>
                                    <option value="" selected disabled>Select a category</option>
                                    <option value="education" @selected(old('category') === 'education')>Education</option>
                                    <option value="health" @selected(old('category') === 'health')>Health</option>
                                    <option value="food" @selected(old('category') === 'food')>Food &amp; Hunger</option>
                                    <option value="environment" @selected(old('category') === 'environment')>Environment</option>
                                    <option value="community" @selected(old('category') === 'community')>Community</option>
                                    <option value="other" @selected(old('category') === 'other')>Other</option>
                                </select>
                            </div>
                            <div class="campaign-form-field">
                                <label for="campaign-status">Status <span aria-hidden="true">*</span></label>
                                <select id="campaign-status" name="status" required>
                                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                    <option value="upcoming" @selected(old('status') === 'upcoming')>Upcoming</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <aside class="campaign-create-aside">
                        <section class="panel campaign-image-panel" aria-labelledby="campaign-image-heading">
                            <h2 class="campaign-form-heading" id="campaign-image-heading">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                                Campaign Image
                            </h2>
                            <input class="campaign-image-input" type="file" id="campaign-image" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-campaign-image-input>
                            <button class="campaign-upload-zone" type="button" data-campaign-upload aria-describedby="campaign-image-help">
                                <img class="campaign-upload-preview" alt="Selected campaign image preview" data-campaign-image-preview hidden>
                                <span class="campaign-upload-placeholder" data-campaign-upload-placeholder>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                                    <strong>Drag &amp; drop an image here</strong>
                                    <span>or click to browse</span>
                                </span>
                                <span class="campaign-upload-file" data-campaign-file-name></span>
                                <span class="campaign-upload-help" id="campaign-image-help">Supports JPG, PNG, WEBP (Max 2MB)</span>
                            </button>
                        </section>

                        <section class="campaign-tips-panel" aria-labelledby="campaign-tips-heading">
                            <h2 id="campaign-tips-heading">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6M10 22h4M8.5 14.5A7 7 0 1 1 15.5 14.5C14.5 15.3 14 16 14 18h-4c0-2-.5-2.7-1.5-3.5z"/></svg>
                                Tips for a successful campaign
                            </h2>
                            <ol>
                                <li><span>1</span><p>Use a clear and compelling title</p></li>
                                <li><span>2</span><p>Add a high-quality image</p></li>
                                <li><span>3</span><p>Set a realistic goal amount</p></li>
                                <li><span>4</span><p>Provide detailed information about the cause</p></li>
                                <li><span>5</span><p>Keep the campaign active and share it with your community</p></li>
                            </ol>
                        </section>
                    </aside>

                    <div class="campaign-form-actions">
                        <a class="campaign-cancel-button" href="{{ route('campaigns') }}">Cancel</a>
                        <button class="campaign-primary-button campaign-save-button" type="submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h12l4 4v14H3V3z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/></svg>
                            Save Campaign
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
