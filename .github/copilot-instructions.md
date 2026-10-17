# Copilot Instructions

## Build, test, and lint

- Install PHP dependencies with `composer install` and frontend dependencies with `npm install`.
- Run the Laravel app with `php artisan serve`; run Vite in another terminal with `npm run dev`.
- Build frontend assets with `npm run build`.
- Run the full PHPUnit suite with `php artisan test`.
- Run one test file with `php artisan test tests/Feature/DashboardTest.php`.
- Run a single test with `php artisan test tests/Feature/DashboardTest.php --filter=test_authenticated_user_sees_campaign_listing`.
- Format PHP with `vendor/bin/pint --dirty`. There is no separate lint script in `package.json`.

## Architecture

- `routes/web.php` owns the web routes. Login and registration are grouped behind guest middleware; the named `dashboard` route and logout are behind auth middleware.
- Authentication and registration are handled by the controllers under `app/Http/Controllers/Auth`. `User` is the authenticated account model; `Campaign`, `Donor`, and `Donation` are separate domain models connected by Eloquent relationships.
- The authenticated dashboard, campaigns, donations, donors, and users pages each have separate Blade views. Campaigns, donations, and donors are database-backed through their resource controllers; active campaigns accept pending pledges without charging a payment method, and donor creation stores the donor profile fields. The dashboard and users page still use sample data.
- Frontend assets are loaded with Vite from Blade. Dashboard styling lives in `resources/css/dashboard.css`, and the shared responsive sidebar and dashboard interactions live in `resources/js/app.js`. Keep additions consistent with these files rather than introducing another frontend stack.
- Feature tests use PHPUnit under `tests/Feature` and extend the project `Tests\TestCase`.

## Repository conventions

- Keep dashboard markup in Blade and use escaped Blade output for displayed values. The existing dashboard uses inline SVG icons, semantic sections, and `data-*` attributes as JavaScript hooks.
- Keep interactive dashboard behavior in `resources/js/app.js`; use the existing `data-*` hooks and update accessibility state such as `aria-expanded` or `aria-pressed` when controls change.
- Dashboard presentation uses the existing custom CSS classes and responsive breakpoints in `resources/css/dashboard.css`. Tailwind is installed but is not currently used by these views.
- Use named routes for Laravel-generated URLs and preserve the existing authentication middleware boundaries.
- Match the installed Laravel/PHP/package versions and existing conventions documented in `CLAUDE.md`. Do not add or change dependencies without approval.
